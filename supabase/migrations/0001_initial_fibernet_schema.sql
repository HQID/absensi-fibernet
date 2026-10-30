-- ============================================================================
-- Migration: 0001_initial_fibernet_schema.sql
-- Target: Supabase PostgreSQL 17 (PT Fiber Networks Indonesia Branch Palu)
-- ============================================================================

create schema if not exists private;
revoke all on schema private from public;
grant usage on schema private to authenticated, service_role;

create or replace function private.set_updated_at()
returns trigger
language plpgsql
set search_path = ''
as $$
begin
    new.updated_at := pg_catalog.now();
    return new;
end;
$$;

-- 1. Profiles (1-to-1 with auth.users)
create table public.profiles (
    id uuid primary key references auth.users(id) on delete cascade,
    name text not null,
    email text not null unique,
    role text not null default 'student' check (role in ('admin', 'student')),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create trigger trg_profiles_updated_at
before update on public.profiles
for each row execute function private.set_updated_at();

-- 2. Students (PKL participant metadata)
create table public.students (
    id bigint generated always as identity primary key,
    user_id uuid not null unique references public.profiles(id) on delete cascade,
    student_code text not null unique,
    school text not null,
    major text not null,
    start_date date not null,
    end_date date not null check (end_date >= start_date),
    status text not null default 'active' check (status in ('active', 'inactive')),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create index idx_students_school_norm on public.students (lower(btrim(school)));
create index idx_students_status on public.students (status);

create trigger trg_students_updated_at
before update on public.students
for each row execute function private.set_updated_at();

-- 3. Office Locations (Dynamic Geofence configuration)
create table public.office_locations (
    id bigint generated always as identity primary key,
    name text not null,
    latitude numeric(10, 8) not null,
    longitude numeric(11, 8) not null,
    radius_meters integer not null check (radius_meters >= 10),
    max_accuracy_meters integer not null check (max_accuracy_meters >= 5),
    active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create trigger trg_office_locations_updated_at
before update on public.office_locations
for each row execute function private.set_updated_at();

-- 4. Attendances
create table public.attendances (
    id bigint generated always as identity primary key,
    student_id bigint not null references public.students(id) on delete cascade,
    attendance_date date not null,
    check_in_at timestamptz null,
    check_in_latitude numeric(10, 8) null,
    check_in_longitude numeric(11, 8) null,
    check_in_accuracy numeric(8, 2) null,
    check_in_distance numeric(8, 2) null,
    check_in_photo_path text null,
    check_out_at timestamptz null,
    check_out_latitude numeric(10, 8) null,
    check_out_longitude numeric(11, 8) null,
    check_out_accuracy numeric(8, 2) null,
    check_out_distance numeric(8, 2) null,
    status text not null check (status in ('present', 'late', 'absent')),
    location_type text not null default 'inside' check (location_type in ('inside', 'outside')),
    reason text null,
    is_approved boolean null,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    unique (student_id, attendance_date)
);

create index idx_attendances_date on public.attendances (attendance_date desc);
create index idx_attendances_photo_path on public.attendances (ltrim(check_in_photo_path, '/'))
    where check_in_photo_path is not null;

create trigger trg_attendances_updated_at
before update on public.attendances
for each row execute function private.set_updated_at();

-- Internal non-exposed admin lookup helper
create or replace function private.is_admin()
returns boolean
language sql
stable
security definer
set search_path = ''
as $$
    select exists (
        select 1
        from public.profiles
        where id = (select auth.uid())
          and role = 'admin'
    );
$$;

revoke all on function private.is_admin() from public, anon;
grant execute on function private.is_admin() to authenticated, service_role;

-- Explicit Data API grants
grant usage on schema public to anon, authenticated, service_role;
grant select, insert, update, delete on
    public.profiles,
    public.students,
    public.office_locations,
    public.attendances
to authenticated, service_role;
grant usage, select on all sequences in schema public to authenticated, service_role;

-- Row Level Security
alter table public.profiles enable row level security;
alter table public.students enable row level security;
alter table public.office_locations enable row level security;
alter table public.attendances enable row level security;

create policy profiles_select_policy on public.profiles
    for select to authenticated
    using (id = (select auth.uid()) or (select private.is_admin()));

create policy profiles_insert_policy on public.profiles
    for insert to authenticated
    with check ((select private.is_admin()));

create policy profiles_update_policy on public.profiles
    for update to authenticated
    using (id = (select auth.uid()) or (select private.is_admin()))
    with check ((select private.is_admin()) or (id = (select auth.uid()) and role = 'student'));

create policy profiles_delete_policy on public.profiles
    for delete to authenticated
    using ((select private.is_admin()));

create policy students_select_policy on public.students
    for select to authenticated
    using (user_id = (select auth.uid()) or (select private.is_admin()));

create policy students_insert_policy on public.students
    for insert to authenticated
    with check ((select private.is_admin()));

create policy students_update_policy on public.students
    for update to authenticated
    using ((select private.is_admin()))
    with check ((select private.is_admin()));

create policy students_delete_policy on public.students
    for delete to authenticated
    using ((select private.is_admin()));

create policy office_locations_select_policy on public.office_locations
    for select to authenticated
    using (active = true or (select private.is_admin()));

create policy office_locations_insert_policy on public.office_locations
    for insert to authenticated
    with check ((select private.is_admin()));

create policy office_locations_update_policy on public.office_locations
    for update to authenticated
    using ((select private.is_admin()))
    with check ((select private.is_admin()));

create policy office_locations_delete_policy on public.office_locations
    for delete to authenticated
    using ((select private.is_admin()));

create policy attendances_select_policy on public.attendances
    for select to authenticated
    using (
        (select private.is_admin())
        or exists (
            select 1
            from public.students s
            where s.id = attendances.student_id
              and s.user_id = (select auth.uid())
        )
    );

create policy attendances_insert_policy on public.attendances
    for insert to authenticated
    with check ((select private.is_admin()));

create policy attendances_update_policy on public.attendances
    for update to authenticated
    using ((select private.is_admin()))
    with check ((select private.is_admin()));

create policy attendances_delete_policy on public.attendances
    for delete to authenticated
    using ((select private.is_admin()));

-- Private Storage bucket 'photos' (FR-14)
insert into storage.buckets (id, name, public)
values ('photos', 'photos', false)
on conflict (id) do update set public = false;

create policy photos_admin_select_policy on storage.objects
    for select to authenticated
    using (bucket_id = 'photos' and (select private.is_admin()));

create policy photos_admin_delete_policy on storage.objects
    for delete to authenticated
    using (bucket_id = 'photos' and (select private.is_admin()));

-- Storage quota & orphan/temp breakdown RPC (Service Role only)
create or replace function public.get_bucket_storage_stats(p_bucket text default 'photos')
returns jsonb
language plpgsql
security definer
set search_path = ''
as $$
declare
    v_cutoff timestamptz := pg_catalog.now() - interval '15 minutes';
    v_result jsonb;
begin
    if current_user not in ('service_role', 'postgres', 'supabase_admin')
       and not (select private.is_admin()) then
        raise exception 'Forbidden: admin access required' using errcode = '42501';
    end if;

    with objects_cte as (
        select
            pg_catalog.ltrim(o.name, '/') as path,
            coalesce((o.metadata->>'size')::bigint, 0) as size_bytes,
            coalesce(o.updated_at, o.created_at) as last_modified_at,
            exists (
                select 1
                from public.attendances a
                where pg_catalog.ltrim(a.check_in_photo_path, '/') = pg_catalog.ltrim(o.name, '/')
            ) as is_referenced
        from storage.objects o
        where o.bucket_id = p_bucket
          and o.name is not null
          and pg_catalog.left(pg_catalog.split_part(o.name, '/', -1), 1) <> '.'
    )
    select jsonb_build_object(
        'total_bytes', coalesce(sum(size_bytes), 0),
        'active_count', count(*) filter (where is_referenced and path not like 'livewire-tmp/%'),
        'active_bytes', coalesce(sum(size_bytes) filter (where is_referenced and path not like 'livewire-tmp/%'), 0),
        'temp_count', count(*) filter (where path like 'livewire-tmp/%'),
        'temp_bytes', coalesce(sum(size_bytes) filter (where path like 'livewire-tmp/%'), 0),
        'orphan_count', count(*) filter (where path like 'attendance/%' and not is_referenced),
        'orphan_bytes', coalesce(sum(size_bytes) filter (where path like 'attendance/%' and not is_referenced), 0),
        'cleanable_count', count(*) filter (
            where (path like 'livewire-tmp/%' or (path like 'attendance/%' and not is_referenced))
              and (last_modified_at is null or last_modified_at <= v_cutoff)
        ),
        'cleanable_bytes', coalesce(sum(size_bytes) filter (
            where (path like 'livewire-tmp/%' or (path like 'attendance/%' and not is_referenced))
              and (last_modified_at is null or last_modified_at <= v_cutoff)
        ), 0)
    )
    into v_result
    from objects_cte;

    return v_result;
end;
$$;

revoke all on function public.get_bucket_storage_stats(text) from public, anon, authenticated;
grant execute on function public.get_bucket_storage_stats(text) to service_role;

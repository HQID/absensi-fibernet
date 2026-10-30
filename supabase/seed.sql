-- ============================================================================
-- Seed: Initial Office Location & Default Administrator Account
-- Matches Laravel DatabaseSeeder.php 1:1
-- ============================================================================

do $$
declare
    v_admin_id uuid;
begin
    if not exists (
        select 1 from public.office_locations
        where name = 'PT Fiber Networks Indonesia Branch Palu'
    ) then
        insert into public.office_locations (
            name,
            latitude,
            longitude,
            radius_meters,
            max_accuracy_meters,
            active
        ) values (
            'PT Fiber Networks Indonesia Branch Palu',
            -0.874971,
            119.886639,
            100,
            50,
            true
        );
    end if;

    select id into v_admin_id
    from auth.users
    where email = 'adminabsensi@fibernet.id'
    limit 1;

    if v_admin_id is null then
        v_admin_id := extensions.gen_random_uuid();

        insert into auth.users (
            instance_id,
            id,
            aud,
            role,
            email,
            encrypted_password,
            email_confirmed_at,
            raw_app_meta_data,
            raw_user_meta_data,
            created_at,
            updated_at,
            confirmation_token,
            email_change,
            email_change_token_new,
            recovery_token,
            is_sso_user,
            is_anonymous
        ) values (
            '00000000-0000-0000-0000-000000000000',
            v_admin_id,
            'authenticated',
            'authenticated',
            'adminabsensi@fibernet.id',
            extensions.crypt('fibernetpalupcs2026', extensions.gen_salt('bf', 12)),
            now(),
            '{"provider":"email","providers":["email"]}'::jsonb,
            '{"name":"Administrator"}'::jsonb,
            now(),
            now(),
            '',
            '',
            '',
            '',
            false,
            false
        );

        insert into auth.identities (
            id,
            user_id,
            identity_data,
            provider,
            provider_id,
            last_sign_in_at,
            created_at,
            updated_at
        ) values (
            extensions.gen_random_uuid(),
            v_admin_id,
            jsonb_build_object(
                'sub', v_admin_id::text,
                'email', 'adminabsensi@fibernet.id',
                'email_verified', true,
                'phone_verified', false
            ),
            'email',
            v_admin_id::text,
            now(),
            now(),
            now()
        );
    end if;

    insert into public.profiles (id, name, email, role)
    values (v_admin_id, 'Administrator', 'adminabsensi@fibernet.id', 'admin')
    on conflict (id) do update
        set name = excluded.name,
            email = excluded.email,
            role = 'admin';
end;
$$;

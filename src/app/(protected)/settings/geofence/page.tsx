import type { Metadata } from "next"
import { redirect } from "next/navigation"
import {
  GeofenceSettingsForm,
  SettingsCardHeader,
} from "@/components/settings/settings-ui"
import { requireAdmin } from "@/lib/admin-helpers"

export const metadata: Metadata = {
  title: "Pengaturan Geofence",
}

export default async function GeofenceSettingsPage() {
  const admin = await requireAdmin()
  if (!admin.ok) {
    if (admin.status === 401) redirect("/login")
    redirect("/dashboard")
  }

  const { data: location } = await admin.supabaseAdmin
    .from("office_locations")
    .select("*")
    .order("id", { ascending: true })
    .limit(1)
    .maybeSingle()

  return (
    <SettingsCardHeader
      heading="Pengaturan Geofence"
      subheading="Tentukan titik koordinat kantor, radius area absensi, dan batas akurasi GPS yang diizinkan."
    >
      <GeofenceSettingsForm
        locationId={location?.id ?? null}
        initialName={location?.name ?? ""}
        initialLatitude={location ? String(location.latitude) : ""}
        initialLongitude={location ? String(location.longitude) : ""}
        initialRadiusMeters={location?.radius_meters ?? 100}
        initialMaxAccuracyMeters={location?.max_accuracy_meters ?? 50}
        initialActive={location?.active ?? true}
      />
    </SettingsCardHeader>
  )
}

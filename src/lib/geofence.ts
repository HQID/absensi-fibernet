import type { OfficeLocation } from "@/types/database.types"

export type GeofenceValidationResult = {
  valid: boolean
  message: string
  distance: number | null
}

function deg2rad(deg: number): number {
  return (deg * Math.PI) / 180
}

function round2(value: number): number {
  return Math.round(value * 100) / 100
}

export function calculateDistance(
  lat1: number,
  lon1: number,
  lat2: number,
  lon2: number
): number {
  const earthRadius = 6_371_000

  const latFrom = deg2rad(lat1)
  const lonFrom = deg2rad(lon1)
  const latTo = deg2rad(lat2)
  const lonTo = deg2rad(lon2)

  const latDelta = latTo - latFrom
  const lonDelta = lonTo - lonFrom

  const a =
    Math.sin(latDelta / 2) ** 2 +
    Math.cos(latFrom) * Math.cos(latTo) * Math.sin(lonDelta / 2) ** 2

  const angle = 2 * Math.asin(Math.min(1, Math.sqrt(a)))

  return angle * earthRadius
}

export function validateLocation(
  latitude: number,
  longitude: number,
  accuracy: number,
  office: Pick<
    OfficeLocation,
    "latitude" | "longitude" | "radius_meters" | "max_accuracy_meters"
  >
): GeofenceValidationResult {
  const roundedAccuracy = round2(accuracy)

  if (accuracy > office.max_accuracy_meters) {
    return {
      valid: false,
      message: `Akurasi lokasi terlalu rendah (${roundedAccuracy}m). Maksimal yang diizinkan adalah ${office.max_accuracy_meters}m. Coba aktifkan GPS dan ulangi.`,
      distance: null,
    }
  }

  const rawDistance = calculateDistance(
    latitude,
    longitude,
    Number(office.latitude),
    Number(office.longitude)
  )
  const roundedDistance = round2(rawDistance)

  if (rawDistance > office.radius_meters) {
    return {
      valid: false,
      message: `Anda berada di luar area presensi. Jarak Anda ${roundedDistance}m dari kantor (Maks: ${office.radius_meters}m).`,
      distance: roundedDistance,
    }
  }

  return {
    valid: true,
    message: "Lokasi valid.",
    distance: roundedDistance,
  }
}

<?php

namespace App\Services;

use App\Models\OfficeLocation;

class GeofenceService
{
    /**
     * Calculate the great-circle distance between two points on the Earth surface using Haversine formula.
     *
     * @param float $lat1 Latitude of point 1
     * @param float $lon1 Longitude of point 1
     * @param float $lat2 Latitude of point 2
     * @param float $lon2 Longitude of point 2
     * @return float Distance in meters
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * Validate if the given location is within the office geofence.
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @param float $accuracy User's GPS accuracy
     * @param OfficeLocation $office Active office location
     * @return array{valid: bool, message: string, distance: float|null}
     */
    public function validateLocation(float $latitude, float $longitude, float $accuracy, OfficeLocation $office): array
    {
        // Check GPS accuracy
        if ($accuracy > $office->max_accuracy_meters) {
            return [
                'valid' => false,
                'message' => "Akurasi lokasi terlalu rendah ({$accuracy}m). Maksimal yang diizinkan adalah {$office->max_accuracy_meters}m. Coba aktifkan GPS dan ulangi.",
                'distance' => null,
            ];
        }

        // Calculate distance
        $distance = $this->calculateDistance($latitude, $longitude, $office->latitude, $office->longitude);

        // Check if within radius
        if ($distance > $office->radius_meters) {
            return [
                'valid' => false,
                'message' => "Anda berada di luar area presensi. Jarak Anda {$distance}m dari kantor (Maks: {$office->radius_meters}m).",
                'distance' => round($distance, 2),
            ];
        }

        return [
            'valid' => true,
            'message' => 'Lokasi valid.',
            'distance' => round($distance, 2),
        ];
    }
}


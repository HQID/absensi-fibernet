<?php

namespace App\Services;

use App\Models\OfficeLocation;

class GeofenceService
{
    /**
     * Calculate the great-circle distance between two points on the Earth surface using Haversine formula.
     * Used for circular baseline and distance calculation.
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
     * Determine whether a coordinate is inside a polygon using the Ray Casting Algorithm (Jordan Curve Theorem).
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @param array $polygon Array of polygon vertices [['lat' => ..., 'lng' => ...], ...]
     * @return bool True if point is inside polygon, false otherwise
     */
    public function isInsidePolygon(float $latitude, float $longitude, array $polygon): bool
    {
        // Normalize vertices to ensure consistent ['lat', 'lng'] format
        $vertices = [];
        foreach ($polygon as $point) {
            if (is_array($point)) {
                $lat = $point['lat'] ?? $point['latitude'] ?? ($point[0] ?? null);
                $lng = $point['lng'] ?? $point['longitude'] ?? ($point[1] ?? null);
                if ($lat !== null && $lng !== null) {
                    $vertices[] = [
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                    ];
                }
            }
        }

        $numPoints = count($vertices);
        if ($numPoints < 3) {
            return false;
        }

        $inside = false;

        // Cast ray horizontally from test point towards positive infinity (east)
        for ($i = 0, $j = $numPoints - 1; $i < $numPoints; $j = $i++) {
            $latI = $vertices[$i]['lat'];
            $lngI = $vertices[$i]['lng'];
            $latJ = $vertices[$j]['lat'];
            $lngJ = $vertices[$j]['lng'];

            // Check if the ray intersects the edge between vertex i and vertex j
            $intersects = (($latI > $latitude) !== ($latJ > $latitude)) &&
                ($longitude < ($lngJ - $lngI) * ($latitude - $latI) / ($latJ - $latI) + $lngI);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Validate if the given location is within the office geofence.
     * Uses Polygon Geofencing (Ray Casting) if polygon_coordinates is configured,
     * otherwise falls back to Circular Geofencing (Haversine).
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @param float $accuracy User's GPS accuracy
     * @param OfficeLocation $office Active office location
     * @return array{valid: bool, method: string, is_inside_polygon: bool, message: string, distance: float|null}
     */
    public function validateLocation(float $latitude, float $longitude, float $accuracy, OfficeLocation $office): array
    {
        // 1. Check GPS accuracy threshold
        if ($accuracy > $office->max_accuracy_meters) {
            return [
                'valid' => false,
                'method' => !empty($office->polygon_coordinates) ? 'polygon' : 'circular',
                'is_inside_polygon' => false,
                'message' => "Akurasi lokasi terlalu rendah ({$accuracy}m). Maksimal yang diizinkan adalah {$office->max_accuracy_meters}m. Coba aktifkan GPS dan ulangi.",
                'distance' => null,
            ];
        }

        // Determine center point for distance calculation
        $centerLat = $office->center_latitude ?? $office->latitude;
        $centerLng = $office->center_longitude ?? $office->longitude;
        $distance = round($this->calculateDistance($latitude, $longitude, $centerLat, $centerLng), 2);

        // 2. Validate using Polygon Geofencing if polygon coordinates exist
        if (!empty($office->polygon_coordinates) && is_array($office->polygon_coordinates) && count($office->polygon_coordinates) >= 3) {
            $isInside = $this->isInsidePolygon($latitude, $longitude, $office->polygon_coordinates);

            if (!$isInside) {
                return [
                    'valid' => false,
                    'method' => 'polygon',
                    'is_inside_polygon' => false,
                    'message' => "Anda berada di luar batas poligon kantor PT FNI Branch Palu (Jarak dari titik tengah: {$distance}m).",
                    'distance' => $distance,
                ];
            }

            return [
                'valid' => true,
                'method' => 'polygon',
                'is_inside_polygon' => true,
                'message' => 'Lokasi valid berada di dalam poligon kantor.',
                'distance' => $distance,
            ];
        }

        // 3. Fallback: Baseline Circular Geofencing (Haversine)
        if ($distance > $office->radius_meters) {
            return [
                'valid' => false,
                'method' => 'circular',
                'is_inside_polygon' => false,
                'message' => "Anda berada di luar area presensi. Jarak Anda {$distance}m dari kantor (Maks: {$office->radius_meters}m).",
                'distance' => $distance,
            ];
        }

        return [
            'valid' => true,
            'method' => 'circular',
            'is_inside_polygon' => false,
            'message' => 'Lokasi valid (berdasarkan radius lingkaran kantor).',
            'distance' => $distance,
        ];
    }
}

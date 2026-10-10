<?php

use App\Models\OfficeLocation;
use App\Services\GeofenceService;

test('ray casting algorithm correctly identifies points inside and outside polygon', function () {
    $service = new GeofenceService();

    // Defined a square polygon around PT FNI Palu
    // Vertex coordinates (Square around -0.9008, 119.8778)
    $polygon = [
        ['lat' => -0.9000, 'lng' => 119.8770], // Top-Left
        ['lat' => -0.9000, 'lng' => 119.8790], // Top-Right
        ['lat' => -0.9020, 'lng' => 119.8790], // Bottom-Right
        ['lat' => -0.9020, 'lng' => 119.8770], // Bottom-Left
    ];

    // 1. Point definitely INSIDE the polygon
    $insideLat = -0.9010;
    $insideLng = 119.8780;
    expect($service->isInsidePolygon($insideLat, $insideLng, $polygon))->toBeTrue();

    // 2. Point definitely OUTSIDE the polygon (to the north)
    $outsideLatNorth = -0.8990;
    $outsideLngNorth = 119.8780;
    expect($service->isInsidePolygon($outsideLatNorth, $outsideLngNorth, $polygon))->toBeFalse();

    // 3. Point definitely OUTSIDE the polygon (to the east)
    $outsideLatEast = -0.9010;
    $outsideLngEast = 119.8800;
    expect($service->isInsidePolygon($outsideLatEast, $outsideLngEast, $polygon))->toBeFalse();

    // 4. Point definitely OUTSIDE the polygon (far away)
    $farOutsideLat = -1.0000;
    $farOutsideLng = 120.0000;
    expect($service->isInsidePolygon($farOutsideLat, $farOutsideLng, $polygon))->toBeFalse();
});

test('validateLocation rejects if GPS accuracy exceeds threshold', function () {
    $service = new GeofenceService();

    $office = new OfficeLocation([
        'name' => 'PT FNI Branch Palu',
        'latitude' => -0.9008985,
        'longitude' => 119.8778007,
        'radius_meters' => 100,
        'max_accuracy_meters' => 50,
        'active' => true,
    ]);

    $result = $service->validateLocation(-0.9008985, 119.8778007, 100.0, $office);

    expect($result['valid'])->toBeFalse();
    expect($result['message'])->toContain('Akurasi lokasi terlalu rendah');
});

test('validateLocation uses polygon geofencing when polygon coordinates are provided', function () {
    $service = new GeofenceService();

    $polygon = [
        ['lat' => -0.9000, 'lng' => 119.8770],
        ['lat' => -0.9000, 'lng' => 119.8790],
        ['lat' => -0.9020, 'lng' => 119.8790],
        ['lat' => -0.9020, 'lng' => 119.8770],
    ];

    $office = new OfficeLocation([
        'name' => 'PT FNI Branch Palu',
        'latitude' => -0.9008985,
        'longitude' => 119.8778007,
        'polygon_coordinates' => $polygon,
        'radius_meters' => 100,
        'max_accuracy_meters' => 50,
        'active' => true,
    ]);

    // Test point inside
    $insideResult = $service->validateLocation(-0.9010, 119.8780, 20.0, $office);
    expect($insideResult['valid'])->toBeTrue();
    expect($insideResult['method'])->toBe('polygon');
    expect($insideResult['is_inside_polygon'])->toBeTrue();

    // Test point outside
    $outsideResult = $service->validateLocation(-0.8990, 119.8780, 20.0, $office);
    expect($outsideResult['valid'])->toBeFalse();
    expect($outsideResult['method'])->toBe('polygon');
    expect($outsideResult['is_inside_polygon'])->toBeFalse();
});

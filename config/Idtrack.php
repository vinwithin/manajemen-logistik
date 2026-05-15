<?php

return [
    'idtrack' => [
        'base_url' => env('IDTRACK_BASE_URL'),
        'username' => env('IDTRACK_USERNAME'),
        'password' => env('IDTRACK_PASSWORD'),
        /** Marker POI gudang asal (Padang) — dipakai sebagai PickupMarkerID SPJ jika tidak di-override */
        'pickup_marker_id' => env('IDTRACK_PICKUP_MARKER_ID'),
    ],
];

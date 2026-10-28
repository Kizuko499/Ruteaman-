<?php
return [
    'featured_rotate_seconds'=>(int)env('FEATURED_ROTATE_SECONDS',120),
    'default_school_slug'=>env('DEFAULT_SCHOOL_SLUG'),
    'resolved_hours'=>(int)env('CONDITION_RESOLVED_HOURS',24),
    'public_hazard_indexable'=>(bool)env('PUBLIC_HAZARD_INDEXABLE',false),
    'routing' => [
        'provider' => env('ROUTING_PROVIDER', 'openrouteservice'),
        'base_url' => env('ROUTING_BASE_URL', 'https://api.openrouteservice.org'),
        'api_key' => env('ROUTING_API_KEY'),
        'profile' => env('ROUTING_PROFILE', 'foot-walking'),
        'timeout' => (int) env('ROUTING_TIMEOUT', 12),
        'alternatives' => (int) env('ROUTING_ALTERNATIVES', 3),
        'cache_seconds' => (int) env('ROUTING_CACHE_SECONDS', 60),
    ],
    'route_buffer_meters' => (int) env('ROUTE_BUFFER_METERS', 100),
    'route_hazard_limit' => (int) env('ROUTE_HAZARD_LIMIT', 100),
    'hazard_merge_distance_meters' => (int) env('HAZARD_MERGE_DISTANCE_METERS', 30),
    'hazard_saturation' => (float) env('HAZARD_SATURATION', 9),
];
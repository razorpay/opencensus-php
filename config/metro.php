<?php

return [
    'apiEndpoint' => env('API_METRO_ENDPOINT', 'https://metro-web.concierge.stage.razorpay.in'),
    'projectId'   => env('API_METRO_PROJECTID', 'stage-api'),
    'username'    => env('API_METRO_USERNAME', 'username'),
    'password'    => env('API_METRO_PASSWORD', 'password'),
    'mock'        => env('API_METRO_MOCK', true),
];

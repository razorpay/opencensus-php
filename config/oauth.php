<?php

return [
    'auth_user' =>  'rzp_auth',
    'auth_pass' =>  env('AUTH_SERVICE_PASS'),
    'mock'      =>  env('OAUTH_MOCK', false),

    // Used for CORS validation
    'auth_service_url'  =>  env('AUTH_SERVICE_URL'),
];

<?php

return [
    'base_url'              =>  env('UCS_BASE_URL'),
    'admin_auth_user'       =>  'rzp_admin_ucs',
    'admin_auth_pass'       =>  env('UCS_ADMIN_AUTH_PASS'),
    'request_timeout'       =>  env('UCS_REQUEST_TIME_OUT', 600), // 10 minutes
];

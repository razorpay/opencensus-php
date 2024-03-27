<?php

return array(

    /**
     * This url is used when sending requests directly to rzp backend
     * without using rzp-php api.
     * Mostly used for endpoints not exposed by rzp-php
     */
    'url'                   =>  env('API_URL'),
    'checkout_url'          =>  env('CHECKOUT_API_URL'),
    'public_api_url'        =>  env('PUBLIC_API_URL'),
    'edge_url'              =>  env('EDGE_URL'),
    'auth_user'             =>  'rzp_api',
    'auth_pass'             =>  env('API_AUTH_PASS'),
    'admin_auth_pass'       =>  env('API_ADMIN_AUTH_PASS'),
    'auth_guest_pass'       =>  env('API_GUEST_AUTH_PASS'),
    'auth_internal_pass'    =>  env('API_INTERNAL_AUTH_PASS'),
    'mock'                  =>  env('API_MOCK'),
    'request_timeout'       =>  600, // 10 minutes
);

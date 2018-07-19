<?php

return array(

    /**
     * This url is used when sending requests directly to rzp backend
     * without using rzp-php api.
     * Mostly used for endpoints not exposed by rzp-php
     */
    'url'                   =>  env('API_URL'),
    'auth_user'             =>  'rzp_api',
    'auth_pass'             =>  env('API_AUTH_PASS'),
    'auth_guest_pass'       =>  env('API_GUEST_AUTH_PASS'),
    'auth_internal_pass'    =>  env('API_INTERNAL_AUTH_PASS'),
    'mock'                  =>  env('API_MOCK')
);

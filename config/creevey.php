<?php
/**
 * Creevey is the screenshot service
 * See https://github.com/razorpay/creevey
 */
return [
    'root'  =>  env('CREEVEY_URL', 'http://rzp.cloudapp.net:8080'),
    'token' =>  env('CREEVEY_TOKEN', 'invalid_token'),
    'mock'  =>  env('CREEVEY_MOCK', false)
];

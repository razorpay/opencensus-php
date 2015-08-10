<?php
/**
 * Creevey is the screenshot service
 * See https://github.com/razorpay/creevey
 */
return [
    'root'  =>  'http://rzp.cloudapp.net:8080',
    'token' =>  $_ENV['CREEVEY_TOKEN'],
    'mock'  =>  getenv('CREEVEY_MOCK')||false
];

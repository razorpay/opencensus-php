<?php

return array(

    /**
     * This url is used when sending requests directly to rzp backend
     * without using rzp-php api.
     * Mostly used for endpoints not exposed by rzp-php
     */
    'url'       =>  $_ENV['API_URL'],
    'ip'        =>  '10.0.0.140',
    'auth_pass' =>  $_ENV['API_AUTH_PASS'],
    'mock'      =>  $_ENV['API_MOCK']
);
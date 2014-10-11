<?php

    return array(

    /*
    |--------------------------------------------------------------------------
    | Database Credentials
    |--------------------------------------------------------------------------
    |
    | Provide MYSQL DB Creddentials here
    |
    */
    'DB_MYSQL_HOST'      => 'localhost',
    'DB_MYSQL_PORT'      => '3306',
    'DB_MYSQL_DATABASE'  => 'dashboard',
    'DB_MYSQL_USERNAME'  => 'root',
    'DB_MYSQL_PASSWORD'  => '',

    /**
     * API Details
     */
    'API_URL'           => 'http://api.razorpay.com/',
    'API_AUTH_PASS'     => 'secret',
    'API_MOCK'          => true,

    /**
     *  QUEUE System Configuration
     * Queue driver can be set to 'sync' in development, in that case AWS credentials can be left blank
     */
    'QUEUE_DRIVER'  => 'sync',
    'AWS_QUEUE_URL' => '',

    /**
     * AWS Bucket for storing activation documents uploaded
     */
    'AWS_ACTIVATION_BUCKET' =>'activation_test',

    /*
    |--------------------------------------------------------------------------
    | Your AWS Credentials
    |--------------------------------------------------------------------------
    |
    | In order to communicate with an AWS service, you must provide your AWS
    | credentials including your AWS Access Key ID and your AWS Secret Key
    | Used for SQS and S3 file uploads
    |
    | Make them null when running on AWS using roles
    |
    */
    'AWS_KEY_ID'        => '', // Your AWS Access Key ID
    'AWS_KEY_SECRET'    => '', // Your AWS Secret Access Key
    'AWS_REGION'        => 'us-east-1',

    /**
     * Should mail be faked, set true in testing/development See mail.php for details
     */
    'MAIL_PRETEND'      => true
    );
?>
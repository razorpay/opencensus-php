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
    'DB_MYSQL_HOST'      => getenv('WERCKER_MYSQL_HOST'),
    'DB_MYSQL_PORT'      => getenv('WERCKER_MYSQL_PORT'),
    'DB_MYSQL_DATABASE'  => getenv('WERCKER_MYSQL_DATABASE'),
    'DB_MYSQL_USERNAME'  => getenv('WERCKER_MYSQL_USERNAME'),
    'DB_MYSQL_PASSWORD'  => getenv('WERCKER_MYSQL_PASSWORD'),

    /**
     * API Details
     */
    'API_URL'           => 'https://beta.razorpay.com/v1/',
    'API_AUTH_PASS'     => getenv('API_AUTH_PASS'),
    'API_MOCK'          => false,

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

    //Should mail be faked, set true in testing/development See mail.php for details
    'MAIL_PRETEND'      => true,

    'SLACK_ENABLE'      => false,
    'SLACK_KEY'         => '',

    'NOCAPTCHA_SECRET'  => '',
    'CONTEXT'           => 'testing',

    // Creevey is the creenshot service
    'CREEVEY_TOKEN'     => 'token_for_creevey'
    );
?>

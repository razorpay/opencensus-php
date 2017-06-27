<?php


return [

    'default'   => 'guzzle6',


    'adapters' => [

        /**
         * @link https://github.com/php-http/guzzle6-adapter
         */
        'guzzle6' => [
            'allow_redirects' => [
                'max' => 3,
                'strict' => false,
            ],
            'timeout' => 10,
            'connect_timeout' => 10,
        ],

        /**
         * @link https://github.com/php-http/guzzle5-adapter
         */
        'guzzle5'   => [

        ],

        /**
         * @link https://github.com/php-http/curl-client
         */
        'curl'  => [

        ],

        /**
         * @link https://github.com/php-http/socket-client
         */
        'socket'    => [

        ],

        /**
         * @link https://github.com/php-http/buzz-adapter
         */
        'buzz'   => [
            'resolver'  => [
              'timeout'     => 5,
              'verify_peer' => true,
              'verify_host' => 2,
              'proxy'       => null,
            ],
        ],

        /**
         * @link https://github.com/php-http/react-adapter
         */
        'react' => [

        ],

    ],

];

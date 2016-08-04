<?php

/*
 | ----------------------------------------------------------------------------------
 | Detect The Application Environment
 | ----------------------------------------------------------------------------------
 |
 */
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

$app->useEnvironmentPath(__DIR__.'/../environment');

$app->detectEnvironment(function() use ($app) {
    $env = 'production';

    if (env('APP_ENV') === 'testing')
    {
        $env = 'testing';
    }
    else if (file_exists($file = __DIR__ . '/../environment/env.php'))
    {
        $env = require $file;
    }

    $file = $app->environmentFile().($env==='production'?'':'.'.$env);

    if (file_exists($app->environmentPath().'/'.$file))
    {
        $app->loadEnvironmentFrom($file);
    }

    putenv("APP_ENV=$env");

    return $env;
});

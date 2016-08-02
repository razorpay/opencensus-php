<?php

$app->useEnvironmentPath(__DIR__.'/../environment');

$app->detectEnvironment(function() use ($app) {
    $env = 'production';

    $envLocation = __DIR__ . '/../environment/env.php';

    if (env('APP_ENV') === 'testing')
    {
        $env = 'testing';
    }

    else if (file_exists($envLocation))
    {
        $env = require($envLocation);
    }

    $envSuffix = ($env==='production') ? '' : ".$env";

    $file = $app->environmentFile().$envSuffix;

    // sd($file);
    //
    // sd($app->environmentPath());

    if (file_exists($app->environmentPath().'/'.$file))
    {
        $app->loadEnvironmentFrom($file);
    }
});

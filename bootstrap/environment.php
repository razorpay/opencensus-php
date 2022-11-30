<?php

/*
 | ----------------------------------------------------------------------------------
 | Detect The Application Environment
 | ----------------------------------------------------------------------------------
 |
 */
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;

$envDir = __DIR__.'/../environment';
$app->useEnvironmentPath($envDir);

//
// By default we assume environment is prod.
// During testing, laravel sets APP_ENV to 'testing'
// Otherwise, we get the environement from the file
// environment/env.php
//
$env = 'production';

$appEnvironment = env('APP_ENV');

$dockerEnvironment = false;

if (empty($appEnvironment) === false and str_contains($appEnvironment, 'testing'))
{
    $env = 'testing';

    if (str_contains($appEnvironment, 'docker'))
    {
        $dockerEnvironment = true;
    }
}
else if (file_exists($file = __DIR__ . '/../environment/env.php'))
{
    $appEnvironment = require $file;

    if (empty($appEnvironment) === false and str_contains($appEnvironment, 'dev'))
    {
        $env = 'dev';

        if (str_contains($appEnvironment, 'docker'))
        {
            $dockerEnvironment = true;
        }
    }
    else
    {
        $env = $appEnvironment;
    }
}

putenv("APP_ENV=$env");

$cascadingEnvFile = '.env.' . $env;

if ($dockerEnvironment === true)
{
    $cascadingEnvFile .= '_docker';
}

//
// Environment variable files are loaded in the order
// * Vault env file
// * Cascaded environment based env file
// * Default env file
//
// Note that of the above 3, last two are committed in git
// while first one comes into the folder when baking amis via brahma
//

if (! function_exists('read_env_file'))
{
    // Ref the link to understand the reason behind adding the check
    // https://github.com/vlucas/phpdotenv#putenv-and-getenv
    if ($env !== 'production')
    {
        function read_env_file($envDir, $fileName)
        {
            $file = $envDir . '/' . $fileName;

            if (file_exists($file) === false)
            {
                return;
            }

            $dotenv = Dotenv::createUnsafeImmutable($envDir, $fileName);

            $dotenv->load();
        }
    }
    else
    {
        function read_env_file($envDir, $fileName)
        {
            $file = $envDir . '/' . $fileName;

            if (file_exists($file) === false)
            {
                return;
            }

            $dotenv = Dotenv::createImmutable($envDir, $fileName);

            $dotenv->load();
        }
    }
}

if ($env !== 'testing')
{
    read_env_file($envDir, '.env.vault');
}

read_env_file($envDir, $cascadingEnvFile);
read_env_file($envDir, '.env.defaults');

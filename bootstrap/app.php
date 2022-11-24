<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new RZP\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
 |-------------------------------------------------------------------------
 | Load Environment Configuration
 |-------------------------------------------------------------------------
 */

require __DIR__ . '/environment.php';

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    RZP\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    RZP\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    \RZP\Exception\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

require_once __DIR__.'/../app/lib/utility.php';
require_once __DIR__.'/../app/lib/utility2.php';
require_once __DIR__.'/../app/lib/PhoneBook.php';
require_once __DIR__.'/../app/lib/CRC16.php';
require_once __DIR__ . '/../app/lib/Gstin.php';
require_once __DIR__.'/../app/lib/Formatters/Xml.php';

return $app;

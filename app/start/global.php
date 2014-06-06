<?php

use Whoops\Handler\PrettyPageHandler;

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/dashboard',
	app_path().'/trace',
	app_path().'/controllers',
	app_path().'/gateway',
	app_path().'/models',
	app_path().'/database/seeds',
	app_path().'/lib',
	app_path().'/exceptions',
    app_path().'/constants',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/


Log::useFiles(storage_path().'/logs/laravel.log');

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Exception $exception, $code)
{	
    //d($exception);
	if(strpos($exception->getMessage(), 'Transaction Exception:')!=False)
	{
		$trace = new Trace\TransactionTrace();
		$trace->error(Trace\TraceEvent::TRANSACTION_EXCEPTION, array('message'=>$exception->getMessage(), 'file'=>$exception->getFile()));
	}

	if(strpos($exception->getMessage(), 'Gateway Exception:')!=False)
	{
		$trace = new Trace\GatewayTrace();
		$trace->error(Trace\TraceEvent::GATEWAY_EXCEPTION, array('message'=>$exception->getMessage(), 'file'=>$exception->getFile()));
	}

	Log::error($exception);

    // Use the Laravel IoC container to get the Whoops\Run instance, if whoops
    // is available (which will be the case, by default, in the dev
    // environment)

    if((App::bound('whoops')) and
       (Config::get('app.debug'))) 
    {
        // Retrieve the whoops handler in charge of displaying exceptions:
        $whoopsDisplayHandler = App::make("whoops.handler");
     
        // Laravel will use the PrettyPageHandler by default, unless this
        // is an AJAX request, in which case it'll use the JsonResponseHandler:
        if($whoopsDisplayHandler instanceof PrettyPageHandler) 
        {
     
            // Set a custom page title for our error page:
            $whoopsDisplayHandler->setPageTitle("Mayday! Mayday! Don't push the code!");
     
            // Set the "open:" link for files to our editor of choice:
            $whoopsDisplayHandler->setEditor("sublime");

            $records = Trace\Trace::getInstance()->getFlattenedRecordsForScreen();
            
            $whoopsDisplayHandler->addDataTable('Trace', $records);
        }
    }

});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';
require_once app_path().'/lib//utility.php';
require_once app_path().'/lib//utility2.php';
require app_path().'/lib/validation.php';

Models\DAL\Transaction::creating('Models\DAL\UuidDAL@generateUuid');

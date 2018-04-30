<?php

namespace RZP\Http\Controllers;

use View;
use ApiResponse;
use Barryvdh\Debugbar\LaravelDebugbar;

/**
 * Class GenericController
 *
 * Hosts functions for those orphan routes that
 * don't really belong anywhere else
 *
 * @package RZP\Http\Controllers
 */
class GenericController extends Controller
{
    /**
     * Inspector/Debugbar session viewer
     *
     * @todo Shouldn't be under the /v1 namespace, remove
     *
     * @param LaravelDebugbar $debugBar
     *
     * @return mixed
     */
    public function getInspectorIndex(LaravelDebugbar $debugBar)
    {
        // Only allow access when the app is in debug mode, and dev env
        if (($this->config['app.debug'] !== true) or
            ($this->app->environment() !== 'dev'))
        {
            return ApiResponse::routeNotFound();
        }

        $debugBar->enable();

        // We don't want this route, `/v1/_inspector`, to store any collected data
        $debugBar->setStorage(null);

        // The view gets its data from JavascriptRenderer
        $renderer = $debugBar->getJavascriptRenderer();

        //
        // By defining an openHandlerUrl, debugbar enables us to list and view other
        // debug sessions. Here, we use debugbar's own route (defined with the alias
        // `debugbar.openhandler`) as the openHandlerUrl
        //
        $openHandlerUrl = route('debugbar.openhandler');
        $renderer->setOpenHandlerUrl($openHandlerUrl);

        return View::make('generic.inspector', ['renderer' => $renderer]);
    }
}

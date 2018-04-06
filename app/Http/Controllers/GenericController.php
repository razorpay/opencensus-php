<?php

namespace RZP\Http\Controllers;

use View;
use ApiResponse;
use Barryvdh\Debugbar\LaravelDebugbar;

use RZP\Error\ErrorCode;

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
        // Only allow access when the app is in debug mode
        if ($this->config['app.debug'] !== true)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $debugBar->enable();

        // We don't want this route, `/v1/_inspector`, to store any collected data
        $debugBar->setStorage(null);

        // The view gets its data from JavascriptRenderer
        $renderer = $debugBar->getJavascriptRenderer();

        // Defining the openHandler allows you to "open" other debug sessions
        $openHandlerUrl = route('debugbar.openhandler');
        $renderer->setOpenHandlerUrl($openHandlerUrl);

        return View::make('generic.inspector', ['renderer' => $renderer]);
    }
}

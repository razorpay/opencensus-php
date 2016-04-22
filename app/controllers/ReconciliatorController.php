<?php

use Trace\TraceCode;
use Http\Route;
use EE\Exception;

class ReconciliatorController extends BaseController
{
    public function receiveWebhook()
    {
        $input = Input::all();
        
        $orchestrator = new \Reconciliator\Orchestrator;
        
        $statusCode = $orchestrator->start($input);
        


        return $statusCode;
        //return Response::make($contents, $statusCode);
        //https://laravel.com/docs/4.2/responses
    }
}
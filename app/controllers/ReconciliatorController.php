<?php

use Trace\TraceCode;
use Http\Route;
use EE\Exception;

class ReconciliatorController extends BaseController
{
    protected $orchestrator;
    
    public function __construct()
    {
        $this->orchestrator = new Reconciliator\Orchestrator();
    }

    public function receiveWebhook()
    {
        $input = Input::all();

        $statusCode = $this->orchestrator->start($input);

        return $statusCode;
        //return Response::make($contents, $statusCode);
        //https://laravel.com/docs/4.2/responses
    }
}
<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Models\Typeform\Auth;
use RZP\Models\Typeform\Service;
use Illuminate\Http\Request as HttpRequest;

class TypeformController extends Controller
{

     public function webhookConsumption(HttpRequest $request)
     {
         $auth = null;

         if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === false)
         {
             $auth = Auth::authenticateTypeformWebhook($request);
         }
         $this->setMode();

         if($auth !== null)
         {
             return $auth;
         }
         $input = $request->all();

         $service = new Service();

         $data = $service->processTypeformWebhook($input);

         return ApiResponse::json($data);
     }

     private function setMode()
     {
         $this->ba->setModeAndDbConnection(Mode::LIVE);
     }
}

<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use ApiResponse;
use RZP\Models\Typeform\Auth;
use RZP\Models\Typeform\Service;
use Illuminate\Http\Request as HttpRequest;

class TypeformController extends Controller
{

     public function webhookConsumption(HttpRequest $request)
     {
         $auth = Auth::authenticateTypeformWebhook($request);

         if($auth !== null)
         {
             return $auth;
         }
         $input = $request->all();

         $service = new Service();

         $data = $service->processTypeformWebhook($input);

         return ApiResponse::json($data);
     }

}

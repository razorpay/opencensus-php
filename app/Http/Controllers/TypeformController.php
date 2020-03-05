<?php

namespace RZP\Http\Controllers;

use ApiResponse;


class TypeformController extends Controller
{

     public function webhookConsumption()
     {
        return ApiResponse::json(["authorization" => "cleared"]);
     }

}

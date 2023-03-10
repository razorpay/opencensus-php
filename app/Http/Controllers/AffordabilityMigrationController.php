<?php

namespace RZP\Http\Controllers;


use Request;
use ApiResponse;

use RZP\Models\Affordability;


class AffordabilityMigrationController extends Controller
{
    public function updateAffordabilityPaymentMethods()
    {
        $input = Request::all();

        $data = (new Affordability\AffordabilityMigrationService())->updateAffordabilityPaymentMethods($input);

        return ApiResponse::json($data);
    }
}

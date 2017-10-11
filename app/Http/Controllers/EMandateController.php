<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class EMandateController extends Controller
{
    public function postGenerateRegistrationFile($gateway)
    {
        $input = Request::all();

        $data = $this->service('emandate')->generateRegistrationFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postReconcileRegistrationFile($gateway)
    {
        $input = Request::all();

        $data = $this->service('emandate')->reconcileRegistrationFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postGenerateDebitFile($gateway)
    {
        $input = Request::all();

        $data = $this->service('emandate')->generateDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postReconcileDebitFile($gateway)
    {
        $input = Request::all();

        $data = $this->service('emandate')->reconcileDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }
}
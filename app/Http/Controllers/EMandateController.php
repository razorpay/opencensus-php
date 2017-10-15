<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Exception;
use RZP\Models\EMandate;

class EMandateController extends Controller
{
    public function postGenerateRegistrationFile($gateway)
    {
        $input = Request::all();

        $data = (new EMandate\Service)->generateRegistrationFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postReconcileRegistrationFile($gateway)
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $file = Request::file('file');

        $input = Request::all();

        $data = (new EMandate\Service)->reconcileRegistrationFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postGenerateDebitFile($gateway)
    {
        $input = Request::all();

        $data = (new EMandate\Service)->generateDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postReconcileDebitFile($gateway)
    {
        $input = Request::all();

        $data = (new EMandate\Service)->reconcileDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }
}
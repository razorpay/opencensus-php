<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\EMandate;

class EMandateController extends Controller
{
    public function postGenerateRegistrationFile($gateway)
    {
        $input = Request::all();

        $data = $this->service()->generateRegistrationFile($gateway, $input);

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

        $input = Request::all();

        $data = $this->service()->reconcileRegistrationFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postGenerateDebitFile($gateway)
    {
        $input = Request::all();

        $data = $this->service()->generateDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }

    public function postReconcileDebitFile($gateway)
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $input = Request::all();

        $data = $this->service()->reconcileDebitFile($gateway, $input);

        return ApiResponse::json($data);
    }
}

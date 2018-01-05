<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Exception;
use RZP\Models\EMandate;

class EMandateController extends Controller
{
    protected $service = EMandate\Service::class;

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

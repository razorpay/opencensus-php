<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;

class DisputeController extends Controller
{
    use Traits\HasCrudMethods;

    public function create(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $paymentId);

        return ApiResponse::json($data);
    }

    public function migrateOldAdjustments()
    {
        if (Request::hasFile('file') === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Input does not contain the excel file to be processed'
            );
        }

        $file = Request::file('file');

        $data = $this->service()->migrateOldAdjustments($file);

        return ApiResponse::json($data);

    }

    public function createReason()
    {
        $input = Request::all();

        $data = $this->service()->createReason($input);

        return ApiResponse::json($data);
    }
}

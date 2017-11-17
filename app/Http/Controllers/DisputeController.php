<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;

class DisputeController extends Controller
{
    public function create(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $paymentId);

        return ApiResponse::json($data);
    }

    public function update(string $id)
    {
        $input = Request::all();

        // For edits by merchant either from API or dashboard

        if (($this->ba->isPrivateAuth() === true) or
            ($this->ba->isProxyAuth() === true))
        {
            $response = $this->service()->updateForMerchant($id, $input);

            return ApiResponse::json($response);
        }

        $entity = $this->service()->update($id, $input);

        return ApiResponse::json($entity);
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

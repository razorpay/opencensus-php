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

        $response = [];

        if ($this->ba->isAdminAuth())
        {
            $response = $this->service()->update($id, $input);
        }
        else if (($this->ba->isPrivateAuth() === true) or
            ($this->ba->isProxyAuth() === true))
        {
            $response = $this->service()->updateForMerchant($id, $input);

        }

        return ApiResponse::json($response);
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

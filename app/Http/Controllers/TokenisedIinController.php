<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Card\TokenisedIIN\Entity;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class TokenisedIinController extends Controller
{
    public function createIin()
    {
        $input = Request::all();

        $data = $this->service()->createIin($input);

        return ApiResponse::json($data);
    }

    public function updateIin()
    {
        $input = Request::all();

        $data = $this->service()->update($input);

        return ApiResponse::json($data);
    }

    public function fetchIin($iin)
    {
        $input = Request::all();

        $response = $this->service()->fetchIin($iin);

        return ApiResponse::json($response);
    }

    public function fetchbyTokenIin($iin)
    {
        $input = Request::all();

        $data = $this->service()->fetchbyTokenIin($iin);

        return ApiResponse::json($data);
    }

    public function addIinBulk()
    {
        $input = Request::all();

        $data = $this->service()->addIinBulk($input);

        return ApiResponse::json($data);
    }

    public function deleteBulk($id)
    {
        $input = Request::all();

        $this->trace->info(TraceCode::TOKEN_IIN_DELETE_BULK,
            [
                'input' => $input
            ]);
        $data = $this->service()->deleteBulk($id , $input);

        return ApiResponse::json($data);
    }

    public function deleteIin($id)
    {
        $input = Request::all();

        $data = $this->service()->deleteIin($id);

        return ApiResponse::json($data);
    }

}

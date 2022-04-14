<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class TokenController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $data = $this->service()->createNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetch()
    {
        $input = Request::all();

        $data = $this->service()->fetchNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function fetchCryptoGram()
    {
        $input = Request::all();

        $data = $this->service()->fetchCryptoGram($input);

        return ApiResponse::json($data);
    }

    public function fetchParValue()
    {
        $input = Request::all();

        $data = $this->service()->fetchParValue($input);

        return ApiResponse::json($data);
    }

    public function delete()
    {
        $input = Request::all();

        $data = $this->service()->deleteNetworkToken($input);

        return ApiResponse::json($data);
    }

    public function pauseNotSupportedCardTokens()
    {
        $input = Request::all();

        $data = $this->service()->pauseNotSupportedCardTokens($input);

        return ApiResponse::json($data);
    }

    public function updateStatus()
    {
        $input = Request::all();

        $data = $this->service()->updateStatus($input);

        return ApiResponse::json($data);
    }

    public function recurringTokenPreDebitNotify($id)
    {
        $input = Request::all();

        $data = $this->service()->recurringTokenPreDebitNotify($id, $input);

        return ApiResponse::json($data);
    }

    public function localSavedCardAsyncTokenisation()
    {
        $data = $this->service()->localSavedCardAsyncTokenisation();

        return ApiResponse::json($data);
    }

    public function localSavedCardBulkTokenisation()
    {
        $input = Request::all();

        $data = $this->service()->localSavedCardBulkTokenisation($input);

        return ApiResponse::json($data);
    }

    public function globalSavedCardAsyncTokenisation()
    {
        $input = Request::all();

        $data = $this->service()->globalSavedCardAsyncTokenisation($input);

        return ApiResponse::json($data);
    }
}

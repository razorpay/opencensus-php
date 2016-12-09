<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Terminal;

class TerminalController extends Controller
{
    public function putTerminal($id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->editTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function restoreTerminal($id)
    {
        $data = (new Terminal\Service)->restoreTerminal($id);

        return ApiResponse::json($data);
    }

    public function deleteTerminal($id)
    {
        $data = (new Terminal\Service)->deleteTerminal2($id);

        return ApiResponse::json($data);
    }

    public function postCheckTerminalEncryptedValue($id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->checkTerminalEncryptedValue($id, $input);

        return ApiResponse::json($data);
    }

    public function toggleTerminal($id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->toggleTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function addMerchant($id, $mid)
    {
        $data = (new Terminal\Service)->addMerchantToTerminal($id, $mid);

        return ApiResponse::json($data);
    }

    public function removeMerchant($id, $mid)
    {
        $data = (new Terminal\Service)->removeMerchantFromTerminal($id, $mid);

        return ApiResponse::json($data);
    }
}

<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Terminal;

class TerminalController extends Controller
{
    public function putTerminal(string $id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->editTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function restoreTerminal(string $id)
    {
        $data = (new Terminal\Service)->restoreTerminal($id);

        return ApiResponse::json($data);
    }

    public function deleteTerminal(string $id)
    {
        $data = (new Terminal\Service)->deleteTerminal2($id);

        return ApiResponse::json($data);
    }

    public function postCheckTerminalEncryptedValue(string $id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->checkTerminalEncryptedValue($id, $input);

        return ApiResponse::json($data);
    }

    public function toggleTerminal(string $id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->toggleTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function addMerchant(string $id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->addMerchantToTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function removeMerchant(string $id)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->removeMerchantFromTerminal($id, $input);

        return ApiResponse::json($data);
    }
}

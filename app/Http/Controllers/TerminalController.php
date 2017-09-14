<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class TerminalController extends Controller
{
    public function putTerminal(string $id)
    {
        $input = Request::all();

        $data = $this->service()->editTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function restoreTerminal(string $id)
    {
        $data = $this->service()->restoreTerminal($id);

        return ApiResponse::json($data);
    }

    public function deleteTerminal(string $id)
    {
        $data = $this->service()->deleteTerminal2($id);

        return ApiResponse::json($data);
    }

    public function postCheckTerminalEncryptedValue(string $id)
    {
        $input = Request::all();

        $data = $this->service()->checkTerminalEncryptedValue($id, $input);

        return ApiResponse::json($data);
    }

    public function toggleTerminal(string $id)
    {
        $input = Request::all();

        $data = $this->service()->toggleTerminal($id, $input);

        return ApiResponse::json($data);
    }

    public function addMerchant(string $id, string $mid)
    {
        $data = $this->service()->addMerchantToTerminal($id, $mid);

        return ApiResponse::json($data);
    }

    public function removeMerchant(string $id, string $mid)
    {
        $data = $this->service()->removeMerchantFromTerminal($id, $mid);

        return ApiResponse::json($data);
    }

    public function reassignMerchant(string $id)
    {
        $input = Request::all();

        $data = $this->service()->reassignMerchantForTerminal($id, $input);

        return ApiResponse::json($data);
    }
}

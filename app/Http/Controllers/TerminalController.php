<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Terminal;

class TerminalController extends Controller
{
    public function putTerminal($tid)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->editTerminal($tid, $input);

        return ApiResponse::json($data);
    }

    public function restoreTerminal($tid)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->restoreTerminal($tid);

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

    public function toggleTerminal($tid)
    {
        $input = Request::all();

        $data = (new Terminal\Service)->toggleTerminal($tid, $input);

        return ApiResponse::json($data);
    }
}

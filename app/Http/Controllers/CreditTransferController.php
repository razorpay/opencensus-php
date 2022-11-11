<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\CreditTransfer;


class CreditTransferController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = (new CreditTransfer\Service());
    }

    public function createAsync()
    {
        $input = Request::all();

        $data = $this->service()->createAsync($input);

        return ApiResponse::json($data);
    }
}

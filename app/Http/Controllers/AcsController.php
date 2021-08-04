<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Merchant;

class AcsController extends Controller
{
    protected $service = Merchant\Acs\Service::class;

    public function triggerSync()
    {
        $input = Request::all();

        $data = $this->service()->triggerSync($input);

        return ApiResponse::json($data);
    }

    public function triggerFullSync()
    {
        $input = Request::all();

        $data = $this->service()->triggerFullSync($input);

        return ApiResponse::json($data);
    }
}
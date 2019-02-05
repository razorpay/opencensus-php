<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class PartnerConfigController extends Controller
{
    use Traits\HasCrudMethods;

    /**
     * Get config based on partner or application id instead of primary key
     *
     * @return mixed
     */
    public function getConfig()
    {
        $input = Request::all();

        $data  = $this->service()->fetch($input);

        return ApiResponse::json($data);
    }
}

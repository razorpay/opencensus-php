<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class NodalBeneficiaryController extends Controller
{
    public function update()
    {
        $input = Request::all();

        $entity = $this->service()->update($input);

        return ApiResponse::json($entity);
    }
}

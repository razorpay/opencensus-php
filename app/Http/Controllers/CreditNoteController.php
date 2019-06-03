<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CreditNoteController extends Controller
{
    use Traits\HasCrudMethods;

    public function apply(string $id)
    {
        $input = Request::all();

        $response = $this->service()->apply($id, $input);

        return ApiResponse::json($response);
    }

}
<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\CreditNote;

class CreditNoteController extends Controller
{
    protected $service = CreditNote\Service::class;

    use Traits\HasCrudMethods;

    public function apply(string $id)
    {
        $input = Request::all();

        $response = $this->service()->apply($id, $input);

        return ApiResponse::json($response);
    }
}

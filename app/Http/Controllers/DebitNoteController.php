<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class DebitNoteController extends Controller
{
    public function postBatch()
    {
        $response = $this->service()->postBatch($this->input);

        return APIResponse::json($response);
    }
}
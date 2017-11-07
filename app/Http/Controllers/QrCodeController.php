<?php

namespace RZP\Http\Controllers;

use Request;
use Response;

class QrCodeController extends Controller
{
    public function fetchQrCode(string $id)
    {
        $response = $this->service()->fetchQrCode($id);

        return Response::download($response, "abc");
    }
}

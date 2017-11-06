<?php

namespace RZP\Http\Controllers;

use Request;
use Response;

class QrCodeController extends Controller
{
    public function fetchQrCode()
    {
        $input = Request::all();

        $response = $this->service()->fetchQrCode($input);

        return Response::download($path, "$displayName");
    }
}

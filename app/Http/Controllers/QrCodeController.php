<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use RZP\Constants\Mode;

class QrCodeController extends Controller
{
    public function fetchTestQrCode(string $id)
    {
        \Database\DefaultConnection::set(Mode::TEST);

        $response = $this->service()->fetchQrCode($id);

        return Response::download($response, 'qr_code');
    }

    public function fetchLiveQrCode(string $id)
    {
        \Database\DefaultConnection::set(Mode::LIVE);

        $response = $this->service()->fetchQrCode($id);

        return Response::download($response, 'qr_code');
    }
}

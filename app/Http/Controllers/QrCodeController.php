<?php

namespace RZP\Http\Controllers;

use Response;
use RZP\Constants\Mode;
use RZP\Models\QrCode\Constants;

class QrCodeController extends Controller
{
    public function fetchTestQrCode(string $id)
    {
        \Database\DefaultConnection::set(Mode::TEST);

        $response = $this->service()->fetchQrCode($id);

        return Response::download($response, Constants::QR_CODE_FILE_NAME);
    }

    public function fetchLiveQrCode(string $id)
    {
        \Database\DefaultConnection::set(Mode::LIVE);

        $response = $this->service()->fetchQrCode($id);

        return Response::download($response, Constants::QR_CODE_FILE_NAME);
    }
}

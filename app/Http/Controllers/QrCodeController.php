<?php

namespace RZP\Http\Controllers;

use Response;
use RZP\Constants\Mode;
use RZP\Models\QrCode\Constants;

class QrCodeController extends Controller
{
    public function fetchTestQrCode(string $id)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        return $this->fetchQrcode($id);
    }

    public function fetchLiveQrCode(string $id)
    {
        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        return $this->fetchQrcode($id);
    }

    protected function fetchQrcode(string $id)
    {
        $response = $this->service()->fetchQrCodePath($id);

        return Response::download($response, Constants::QR_CODE_FILE_NAME);
    }
}

<?php

namespace RZP\Http\Controllers;

use Request;
use Response;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Models\QrCode\Constants;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Service as NonVAQrCodeService;

class QrCodeController extends Controller
{
    public function create()
    {
        $input = Request::all();

        $entity = (new NonVAQrCodeService())->create($input);

        return ApiResponse::json($entity);
    }

    public function closeQrCode(string $id)
    {
        $response = (new NonVAQrCodeService())->closeQrCode($id);

        return ApiResponse::json($response);
    }

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

    public function postTokenizeQrStringMpans()
    {
        $input = Request::all();

        $cronResponse = $this->service()->tokenizeExistingQrStringMpans($input);

        return ApiResponse::json($cronResponse);
    }

    protected function fetchQrcode(string $id)
    {
        $response = $this->service()->fetchQrCodePath($id);

        return Response::download($response, Constants::QR_CODE_FILE_NAME);
    }
}

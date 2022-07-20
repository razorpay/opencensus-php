<?php

namespace RZP\Http\Controllers;

use View;
use Request;
use ApiResponse;
use RZP\Trace\TraceCode;



class CapitalVirtualCardsController extends Controller
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';
    const MERCHANT = 'MERCHANT';

    public function validateToken($token)
    {
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'token' => $token,
        ]);
        $request = Request::instance();
        $response = $this->service()->validateToken($token, $request);
        return ApiResponse::json($response);
    }

    public function getCardNumber()
    {
        $request = Request::instance();
        $response = $this->service()->getCardNumber($request);
        return ApiResponse::json($response);
    }

    public function generateToken()
    {
        $headers = Request::header();
        $merchantId = optional($this->ba->getMerchant())->getId() ?? '';
        $userId = optional($this->ba->getUser())->getId() ?? '';
        if (!array_key_exists('x-dashboard-user-session-id', $headers) ||
            !is_array($headers['x-dashboard-user-session-id']) ||
            empty($merchantId) || empty($userId)) {
            return $this->service->getFormattedResponse(400, [], "Invalid Request");
        }
        $sessionId = $headers['x-dashboard-user-session-id'][0];
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'merchantId' => $this->ba->getUser()->getId(),
        ]);
        $data['token'] = $this->service()->generateToken($sessionId);
        return ApiResponse::json($data);
    }

    public function validateSessionAtCards()
    {
        $request = Request::instance();
        $response = $this->service()->validateSessionAtCards($request);
        return ApiResponse::json($response);
    }

    public function virtualCard()
    {
        return View::make('capitalvirtualcards.index');
    }

    public function sendOtp()
    {
        $response = $this->service()->sendOtp();
        return ApiResponse::json($response);
    }

    public function getCardCvv()
    {
        $response = $this->service()->getCardCvv();
        return ApiResponse::json($response);
    }
}

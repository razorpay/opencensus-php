<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Support\Facades\Mail;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use Request;
use View;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Mail\CapitalCards\Base;
use RZP\Models\D2cBureauReport\Provider;
use RZP\Services\Mozart;
use RZP\Services\Mozart as MozartBase;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Constants\Entity as E;
use RZP\Constants\Mode;



class CapitalVirtualCardsController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    public function validateToken($token){
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'token'=>$token,
        ]);
        $request = Request::instance();
        $response = $this->service()->validateToken($token,$request);
        return ApiResponse::json($response);
    }

    public function getCardNumber(){
        $request = Request::instance();
        $response = $this->service()->getCardNumber($request);
        return ApiResponse::json($response);
    }

    public function generateToken(){
        $headers = Request::header();
        $merchantId = optional($this->ba->getMerchant())->getId() ?? '';
        $userId = optional($this->ba->getUser())->getId() ?? '';
        if(!array_key_exists('x-dashboard-user-session-id',$headers) ||
            !is_array($headers['x-dashboard-user-session-id']) ||
            empty($merchantId) || empty($userId)){
            return $this->service->getFormattedResponse(400,[],"Invalid Request");
        }
        $sessionId = $headers['x-dashboard-user-session-id'][0];
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'merchantId' =>$this->ba->getUser()->getId(),
        ]);
        $data['token'] = $this->service()->generateToken($sessionId);
        return ApiResponse::json($data);
    }

    public function validateSessionAtCards(){
        $request = Request::instance();
        $response = $this->service()->validateSessionAtCards($request);
        return ApiResponse::json($response);
    }

    public function virtualCard(){
        return View::make('capitalvirtualcards.index');
    }

   public function sendOtp(){
        $request = Request::instance();
        $response = $this->service()->sendOtp($request);
        return ApiResponse::json($response);
   }

   public function getCardCvv(){
        $headers = Request::header();
        $request = Request::instance();
        $response = $this->service()->getCardCvv($request);
        return ApiResponse::json($response);
   }
}

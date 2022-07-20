<?php

namespace RZP\Models\CapitalVirtualCards;

use ApiResponse;
use Request;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const MOZART_GET_CARDS = 'get_cards';
    const MOZART_GET_CARD_CVV = 'get_cvv';
    const CARD_TOKEN_EXPIRY = '15';
    const CHECK_CARDS_SESSION_URL = 'v1/card/otp_session?route=GetCardByMerchantUser';
    const SEND_OTP = 'v1/otp';
    const GET_CARDS_ENTITY_INFO = 'v1/card/entity';
    protected $core;
    protected $validator;

    public function __construct()
    {
        parent::__construct();
        $this->core = new Core();
        $this->validator = new Validator();
    }

    public function sendOtp()
    {
        try {
            $body = ['action' => 'GetCardByMerchantUser'];
            $this->core->sendCardsRequestAndParseResponse(self::SEND_OTP, $body, [], self::HTTP_POST, []);
        } catch (\Exception $e) {
            return self::getFormattedResponse(500, []);
        }
        return self::getFormattedResponse(200, []);
    }

    public function getFormattedResponse($code, $body, $reason = '')
    {
        $response['response'] = $body;
        $isSuccess = ($code == 200);
        $response['success'] = $isSuccess;
        if (!$isSuccess) {
            $response['reason'] = $reason;
        }
        $response['status_code'] = $code;
        return $response;
    }

    public function getCardCvv()
    {
        $cvvResponse = [];
        try {
            $selectedCard = self::getCardNumber();
            if ($selectedCard['status_code'] != 200) {
                return self::getFormattedResponse(500, [], 'Card not found, it may be blocked or locked');
            }
            $cardDetails = $selectedCard['response'];

            $request = [];
            $request['program_processor_reference_id'] = $cardDetails['program_processor_reference_id'];
            $request['kit_number'] = $cardDetails['kit_number'];
            $request['expiry_month'] = $cardDetails['expiry_month'];
            $request['expiry_year'] = $cardDetails['expiry_year'];
            $request['date_of_birth'] = $cardDetails['date_of_birth'];

            $mozartResponse = $this->core->getMozartResponse(self::MOZART_GET_CARD_CVV, $request);
            $cvvResponse = ['cvv' => $mozartResponse['data']['cvv']];
        } catch (\Exception $e) {
            return self::getFormattedResponse(500, $cvvResponse, $e->getMessage());
        }
        return self::getFormattedResponse(200, $cvvResponse);
    }

    public function getCardNumber()
    {
        try {
            $selectedCard = [];
            $request = Request::instance();
            $this->validator->validateSession($request);
            list($code, $response) = self::getCardEntityInfo();
            if ($code != 200) {
                return self::getFormattedResponse($code, $response);
            }
            $cardsInfo = $response;

            $mozartRequest['program_processor_reference_id'] = $cardsInfo['entity_id'];
            $mozartResponse = $this->core->getMozartResponse(self::MOZART_GET_CARDS, $mozartRequest);
            $cardsList = $mozartResponse['data']['cardList'];
            foreach ($cardsList as $card) {
                if (!array_key_exists('kit_number', $card)) {
                    continue;
                }
                if ($card['kit_number'] == $cardsInfo['kit_number']) {
                    $selectedCard = $card;
                    break;
                }
            }
            if (empty($selectedCard)) {
                return self::getFormattedResponse(500, [], 'Card not found, it may be blocked or locked');
            }

            $selectedCard['date_of_birth'] = $mozartResponse['data']['dob'];
            $selectedCard['user_name'] = $request->session()->get('userName');
            $selectedCard['company_name'] = $request->session()->get('companyName');
        } catch (\Exception $e) {
            return self::getFormattedResponse(500, $selectedCard, $e->getMessage());
        }
        return self::getFormattedResponse(200, $selectedCard);
    }

    public function getCardEntityInfo()
    {
        $existingHeaders = Request::header();
        $otpHeaders = [];
        $otpHeaders['x-otp'] = $existingHeaders['x-otp'] ?? '';
        return $this->core->sendCardsRequestAndParseResponse(self::GET_CARDS_ENTITY_INFO, [], $otpHeaders, self::HTTP_GET, []);
    }

    public function validateToken($token, $request)
    {
        $validateTokenResponse['valid_token'] = false;
        try {
            $redis = $this->app['redis']->connection();
            $redisData = $redis->get($token);
            if (empty($redisData)) {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
            }
            $decodedRedisData = json_decode($redisData, true);
            $request->session()->put('sessionId', $decodedRedisData['sessionId']);
            $request->session()->put('merchantId', $decodedRedisData['merchantId']);
            $request->session()->put('userId', $decodedRedisData['userId']);
            $request->session()->put('userRole', $decodedRedisData['userRole']);
            $request->session()->put('userName', $decodedRedisData['userName']);
            $request->session()->put('contactMobile', $decodedRedisData['contactMobile']);
            $request->session()->put('companyName', $decodedRedisData['companyName']);
            $request->session()->put('mode', $decodedRedisData['mode']);

            $validateTokenResponse['valid_token'] = true;
            $validateTokenResponse['user_role'] = $decodedRedisData['userRole'];
            $validateTokenResponse['user_id'] = $decodedRedisData['userId'];
            $validateTokenResponse['user_mid'] = $decodedRedisData['merchantId'];
        } catch (\Exception $e) {
            return self::getFormattedResponse(500, $validateTokenResponse, $e->getMessage());
        }
        $redis->del($token);
        return self::getFormattedResponse(200, $validateTokenResponse, '');
    }

    public function generateToken($sessionId)
    {
        $token = gen_uuid();
        $redis = $this->app['redis']->connection();
        $redisData['sessionId'] = $sessionId;
        $redisData['merchantId'] = $this->auth->getMerchant()->getId();
        $redisData['userId'] = $this->auth->getUser()->getId();
        $redisData['userRole'] = $this->auth->getUserRole();
        $redisData['userName'] = $this->auth->getUser()->getName();
        $redisData['contactMobile'] = $this->auth->getUser()->getContactMobile();
        $redisData['companyName'] = $this->auth->getMerchant()->getName();
        $redisData['mode'] = $this->auth->getMode() ?? Mode::LIVE;
        $this->trace->debug(TraceCode::CAPITAL_VIRTUAL_CARDS_REQUEST, [
            'merchantId' => $redisData['merchantId'],
            'userId' => $redisData['userId'],
            'mode' => $redisData['mode']
        ]);
        $redis->set($token, json_encode($redisData), 'NX', 'EX', self::CARD_TOKEN_EXPIRY);
        return $token;
    }

    public function validateSessionAtCards($request)
    {
        list($responseCode, $body) = $this->core->
        sendCardsRequestAndParseResponse(self::CHECK_CARDS_SESSION_URL, [], [], self::HTTP_GET, []);
        $body['contact_mobile'] = $request->session()->get('contactMobile');
        return self::getFormattedResponse($responseCode, $body);
    }
}

?>

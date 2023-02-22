<?php

namespace RZP\Http\Controllers;

use App;
use Request;
use Response;
use ApiResponse;
use RZP\Constants\HyperTrace;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Models\QrCode\Constants;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\QrCode\Service as QrCodeService;
use RZP\Models\QrCode\NonVirtualAccountQrCode\RequestSource;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVAQrCodeEntity;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Service as NonVAQrCodeService;
use RZP\Trace\Tracer;


class QrCodeController extends Controller
{

    public function create()
    {
        $input = Request::all();

        $requestSource = Request::header(Constants::REQUEST_SOURCE);

        $this->setRequestSourceIfApplicable($input, $requestSource);

        $entity = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE], function () use ($input) {
            return (new NonVAQrCodeService())->create($input);
        });

        return ApiResponse::json($entity);
    }
    public function createForCheckout()
    {
        $input = Request::all();

        $entity = Tracer::inspan(['name' => HyperTrace::QR_CODE_CREATE_FOR_CHECKOUT], function () use ($input) {
            return (new NonVAQrCodeService())->createForCheckout($input);
        });

        return ApiResponse::json($entity);
    }

    public function closeQrCode(string $id)
    {
        $response = (new NonVAQrCodeService())->closeQrCode($id);

        return ApiResponse::json($response);
    }

    public function get(string $id)
    {
        $entity = (new NonVAQrCodeService)->fetch($id);

        return ApiResponse::json($entity);
    }

    public function list()
    {
        $input = Request::all();

        $merchant = $this->app['basicauth']->getMerchant();

        if($merchant !== null and $merchant->isFeatureEnabled(Feature::UPIQR_V1_HDFC) === true)
            $entities = (new QrCodeService)->fetch($input);
        else
            $entities = (new NonVAQrCodeService)->fetchMultiple($input);

        return ApiResponse::json($entities);
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

        return $response;
    }

    public function qrDemo()
    {
        $input = Request::all();

        $this->validateCaptcha($input);

        unset($input['captcha']);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        if ($this->app->environment('production') === false)
        {
            $this->app['basicauth']->setMerchantById('10000000000000');
        }
        else
        {
            $this->app['basicauth']->setMerchantById('4ozcIXB0Rl1pFO');
        }

        $entity = (new NonVAQrCodeService())->create($input);

        return ApiResponse::json($entity);
    }

    protected function validateCaptcha(array $input)
    {
        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        if(isset($input['captcha']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_TOKEN_NOT_PRESENT
            );
        }

        $captchaResponse = $input['captcha'];

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $app['request']->ip();

        $noCaptchaSecret = config('app.qr_demo.nocaptcha_secret');

        $input = [
            'secret'   => $noCaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $clientIpAddress,
        ];

        $captchaQuery = http_build_query($input);

        $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

        $response = \Requests::get($url);

        $output = json_decode($response->body);

        if($output->success !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output,
                ]
            );
        }
    }

    public function qrDemoCors()
    {
        $response = ApiResponse::json([]);

        $response->headers->set('Access-Control-Allow-Origin', $this->app['config']->get('app.razorpay_website_url'));

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    public function setRequestSourceIfApplicable(&$input, $requestSource)
    {
        if ($requestSource !== null)
        {
            RequestSource::checkRequestSource($requestSource);

            $input[NonVAQrCodeEntity::REQUEST_SOURCE] = $requestSource;
        }
    }

}

<?php

namespace RZP\Http\Controllers;

use RZP\Http\Controllers\Processors\BvsProxyPostProcessors;
use RZP\Http\Controllers\Processors\BvsProxyPreProcessors;
use RZP\Models\Merchant\AutoKyc\Bvs\Core as BvsCore;

class BvsProxyController extends BaseProxyController {
    const GET_CAPTCHA_API           = 'GetCaptchaApi';
    const VERIFY_CAPTCHA_GET_OTP    = 'VerifyCaptchaGetOtp';
    const SUBMIT_OTP                = 'SubmitOtp';

    const ROUTES_URL_MAP    = [
        self::GET_CAPTCHA_API           => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarGetCaptcha/",
        self::VERIFY_CAPTCHA_GET_OTP    => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarVerifyCaptchaAndSendOtp/",
        self::SUBMIT_OTP                => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarSubmitOtp/"
    ];

    const MERCHANT_ROUTES   = [
        self::GET_CAPTCHA_API,
        self::VERIFY_CAPTCHA_GET_OTP,
        self::SUBMIT_OTP
    ];

    const ADMIN_ROUTES      = [];

    /*
     * timeout in seconds
     */
    const PATH_TIMEOUT_MAP  = [
        self::GET_CAPTCHA_API           => 25,
        self::VERIFY_CAPTCHA_GET_OTP    => 25
    ];

    public function __construct()
    {
        parent::__construct("business_verification_service");

        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);
        $this->setDefaultTimeout(30);

        $this->registerProcessors(
            new BvsProxyPreProcessors($this->app),
            new BvsProxyPostProcessors($this->app)
        );

    }

    protected function getAuthorizationHeader()
    {
        return'Basic '. base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }

    protected function getBaseUrl(): string
    {
        return $this->serviceConfig['host'];
    }
}

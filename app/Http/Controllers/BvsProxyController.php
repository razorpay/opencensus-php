<?php

namespace RZP\Http\Controllers;

use RZP\Trace\TraceCode;
use RZP\Http\Controllers\Processors\BvsProxyPostProcessors;
use RZP\Http\Controllers\Processors\BvsProxyPreProcessors;
use RZP\Models\Merchant\AutoKyc\Bvs\Core as BvsCore;

class BvsProxyController extends BaseProxyController {
    const GET_CAPTCHA_API           = 'GetCaptchaApi';
    const VERIFY_CAPTCHA_GET_OTP    = 'VerifyCaptchaGetOtp';
    const SUBMIT_OTP                = 'SubmitOtp';

    //
    const SEND_OTP_API              = 'SendOtpApi';
    const VERIFY_OTP_API            = 'VerifyOtpApi';
    const FETCH_DIGILOCKER_AADHAAR_DETAILS = 'FetchDigilockerAadhaarDetails';

    const ROUTES_URL_MAP    = [
        self::GET_CAPTCHA_API                   => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarGetCaptcha/",
        self::VERIFY_CAPTCHA_GET_OTP            => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarVerifyCaptchaAndSendOtp/",
        self::SUBMIT_OTP                        => "/twirp\/platform.bvs.probe.v1.ProbeAPI\/AadhaarSubmitOtp/",
        self::SEND_OTP_API                      => "/twirp\/platform.bvs.artefactcurator.verify.v1.DigilockerAPI\/SendOtp/",
        self::VERIFY_OTP_API                    => "/twirp\/platform.bvs.artefactcurator.verify.v1.DigilockerAPI\/VerifyOtp/",
        self::FETCH_DIGILOCKER_AADHAAR_DETAILS  => "/twirp\/platform.bvs.artefactcurator.verify.v1.DigilockerAPI\/FetchAadhaarXmlDetails/"


    ];

    const MERCHANT_ROUTES   = [
        self::GET_CAPTCHA_API,
        self::VERIFY_CAPTCHA_GET_OTP,
        self::SUBMIT_OTP,
        self::SEND_OTP_API,
        self::VERIFY_OTP_API,
        self::FETCH_DIGILOCKER_AADHAAR_DETAILS
    ];

    const ADMIN_ROUTES      = [];

    /*
     * timeout in seconds
     */
    const PATH_TIMEOUT_MAP  = [
        self::GET_CAPTCHA_API                   => 30,
        self::VERIFY_CAPTCHA_GET_OTP            => 30,
        self::SEND_OTP_API                      => 30,
        self::VERIFY_OTP_API                    => 30,
        self::FETCH_DIGILOCKER_AADHAAR_DETAILS  => 15
    ];


    public function __construct()
    {
        parent::__construct("business_verification_service");

        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerMerchantRoutes(self::MERCHANT_ROUTES);

        $this->setPathTimeoutMap(self::PATH_TIMEOUT_MAP);
        $this->setDefaultTimeout(30);

        $config = config('circuit_breaker');

        $serviceName ='aadhaar_ekyc';

        $this->circuitBreaker->changeSettings($config[$serviceName]);
        $this->circuitBreaker->setService($serviceName);

        $this->registerProcessors(
            new BvsProxyPreProcessors($this->app),
            new BvsProxyPostProcessors($this->app),
            $serviceName
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

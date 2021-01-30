<?php


namespace RZP\Http\Controllers\Processors;

use RZP\Exception;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class BvsProxyPostProcessors extends PostProcessor
{
    protected $app;
    protected $ba;

    public function __construct($app)
    {
        $this->app = $app;
        $this->ba = $this->app['basicauth'];
    }

    /**
     * Store sessionID that we get from BVS into redis
     * @param array $response
     * @return array|mixed
     */
    public function handleGetCaptchaApi($payload = [], $response = [])
    {
        $merchantId = $this->ba->getMerchant()->getId();

        (new DetailCore)->setEsignAadhaarSession($merchantId, $response['session_id']);

        unset($response['session_id']);

        $response['sessionExpired'] = false;

        return $response;
    }

    /**
     * On successfully validating otp, store aadhaar_esign_status, aadhaar_pin in stakeholders entity
     * Also fetch zip file from s3 url and store as merchant document
     * @param array $payload
     * @param array $response
     */
    public function handleSubmitOtp($payload = [], $response = [])
    {
        if(isset($response['is_valid']) and $response['is_valid'] ===true)
        {
            $merchantId = $this->ba->getMerchant()->getId();

            (new DetailCore)->processEsignAadhaarVerification(
                $merchantId, $payload['file_password'], $response['file_url']);
        }

        unset($response['file_url']);
        return $response;
    }
}

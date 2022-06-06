<?php


namespace RZP\Http\Controllers\Processors;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class BvsProxyPreProcessors extends PreProcessor
{
    protected $app;
    protected $ba;

    public function __construct($app)
    {
        $this->app = $app;
        $this->ba = $this->app['basicauth'];
    }

    private function validateSession()
    {
        $sessionId = (new DetailCore)->getEsignAadhaarSession($this->ba->getMerchant()->getId());

        if(empty($sessionId) === true)
        {
            throw new Exception\BadRequestValidationFailureException('INVALID_SESSION_ID');
        }

        return $sessionId;
    }

    public function handleVerifyCaptchaGetOtp($payload=[], $response=[])
    {
        $sessionId = $this->validateSession();

        $payload['session_id'] = $sessionId;
        return $payload;
    }

    public function handleSubmitOtp($payload = [], $response = [])
    {
        $sessionId = $this->validateSession();

        $merchantId = $this->ba->getMerchant()->getId();

        $payload['session_id'] = $sessionId;
        $payload['reference_id'] = $merchantId;

        return $payload;
    }


}

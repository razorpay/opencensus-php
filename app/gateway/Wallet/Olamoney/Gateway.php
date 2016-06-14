<?php

namespace Gateway\Wallet\Payumoney;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_olamoney';

    protected $canRunOtpFlow = true;

    public function authorize(array $input)
    {
        parent::authorize($input);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        return $this->callbackTopupFlow($input);
    }

    public function otpGenerate($input)
    {

    }

    protected function getOtpArray()
    {
        return [
            "command"         =>    "capture",
            "accessToken"     =>    "string",
            "uniqueId"        =>    "string",
            "comments"        =>    "string",
            "udf"             =>    "string",
            "hash"            =>    "string",
            "returnUrl"       =>    "string",
            "notificationUrl" =>    "string",
            "amount"          =>    "amount",
            "currency"        =>    "INR",
            "otp"             =>    "string"
        ];
    }
}

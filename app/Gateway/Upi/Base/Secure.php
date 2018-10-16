<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Encryption\EccCrypto;
use RZP\Exception\RuntimeException;

class Secure extends EccCrypto
{
    protected $request;

    /**
     * Update Request
     *
     * @param array $request
     */
    public function setRequest(array $request)
    {
        $request[IntentParams::PURPOSE] = Purpose::getMappedPurpose($request);

        $request[IntentParams::ORG_ID]  = '000000';

        $this->request = $request;
    }

    public function getIntentUrl(): string
    {
        $this->request[IntentParams::MODE] = Mode::SECURE_INTENT;

        return $this->signUrl($this->request);
    }

    public function getQrcodeUrl(): string
    {
        $this->request[IntentParams::MODE] = Mode::SECURE_QR_CODE;

        return $this->signUrl($this->request);
    }

    public function signUrl(array $request)
    {
        $url = 'upi://pay?' . str_replace(' ', '', urldecode(http_build_query($request)));

        $sign  = $this->sign2Base64($url);

        return $url . '&' . IntentParams::SIGN . '=' . $sign;
    }

    public function verifyIntent(string $url)
    {
        if (str_contains($url, '&sign=') === false)
        {
            throw new RuntimeException('Not secure url.', ['url' => $url]);
        }

        list($content, $sign) = explode('&sign=', $url);

        return $this->verifyBase64($content, $sign);
    }
}

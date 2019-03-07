<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use phpseclib\Crypt\RSA;

trait GatewayTrait
{
    protected function getSdkRequestId()
    {
        return str_random();
    }

    protected function generateSignature($input, $key)
    {
        $privateKey = $this->getPrivateKey();

        $rsa = new RSA();

        $rsa->loadKey($privateKey);

        return base64_encode($rsa->sign(base64_decode($input)));
    }
}
<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use phpseclib\Crypt\RSA;

trait GatewayTrait
{
    protected function getSdkRequestId()
    {
        return str_random();
    }

    protected function generateSignature($input)
    {
        $key = $this->getPrivateKey();

        $key = str_replace('\n', "\n", $key);

        $rsa = new RSA();

        $rsa->loadKey($key, RSA::PRIVATE_FORMAT_PKCS1);

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $rsa->setSignatureMode(RSA::SIGNATURE_PSS);

        $signature = bin2hex($rsa->sign($input));

        return $signature;
    }
}

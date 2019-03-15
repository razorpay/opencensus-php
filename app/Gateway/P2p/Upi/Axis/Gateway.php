<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;

use RZP\Gateway\P2p\Upi;
use RZP\Constants\Timezone;
use RZP\Gateway\P2p\Upi\Axis\Sdk;

class Gateway extends Upi\Gateway
{
    protected $actionMap = [];

    protected $gateway = 'p2p_upi_axis';

    public function getMerchantSigner()
    {
        $rsa = new RSA();

        $rsa->loadKey($this->config['merchant_private_key'], RSA::PRIVATE_FORMAT_PKCS1);

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $rsa->setSignatureMode(RSA::SIGNATURE_PSS);

        return $rsa;
    }

    protected function initiateSdkRequest(string $action)
    {
        $request = new Sdk([
            'id' => $this->getSdkRequestId(),
        ]);

        $request->setActionMap($action, $this->actionMap[$action]);

        $request->setSigner($this->getMerchantSigner());

        return $request;
    }

    protected function getTimeStamp()
    {
        return (string) (Carbon::now(Timezone::IST)->getTimestamp() * 1000);
    }

    protected function toBoolean($value)
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $booleanValue;
    }

    protected function getMerchantId()
    {
        return $this->config['merchant_id'];
    }

    protected function getMerchantChannelId()
    {
        return $this->config['merchant_channel_id'];
    }

    protected function getMerchantCategoryCode()
    {
        return $this->config['merchant_category_code'];
    }

    protected function formatMerchantCustomerId($customerId)
    {
        return str_replace('_', '.', $customerId);
    }

    protected function throwP2pGatewayException()
    {
        // .Todo Need to fix the implementation
        throw new \Exception('Hi!');
    }

    protected function getSdkRequestId()
    {
        return str_random(14);
    }
}

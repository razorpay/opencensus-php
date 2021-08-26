<?php

namespace RZP\Gateway\Wallet\Base;

use RZP\Gateway\Wallet;
use RZP\Gateway\Base\Verify;

/**
 * WalletTrait will be used for Wallet payments,
 * Which enable gateway for routing request from API to Mozart
 */
trait WalletTrait
{

    protected function walletAuthorize(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->authorize($input);
    }

    protected function walletCallback(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->callback($input);
    }

    protected function walletVerify($input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->verify($input);
    }

    protected function walletSendPaymentVerifyRequest(Verify $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->sendVerifyRequest($input);
    }

    protected function walletVerifyPayment($verify)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        return $mozart->verifyPayment($verify);
    }

    /**
     * Creates a mozart gateway instance with the mode set from gateway.
     * @return \RZP\Gateway\Mozart\Gateway
     */
    protected function getMozartGatewayWithModeSet()
    {
        $gateway = $this->app['gateway']->gateway('mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }
}

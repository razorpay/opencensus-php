<?php
namespace RZP\Gateway\Ccavenue;

use RZP\Models\Payment;
use RZP\Gateway\Wallet\Base;
use RZP\Exception\LogicException;
use RZP\Gateway\Wallet\Base\WalletTrait;

class Gateway extends Base\Gateway
{
    use WalletTrait;

    function authorize(array $input)
    {
        parent::authorize($input);

        $method = $input['payment']['method'];

        /**
         * Processing authorize requests for wallet payment method with trait.
         * Checking method if wallet then use wallet trait.
         */

        if ($method === Payment\Method::WALLET)
        {
            return $this->walletAuthorize($input);
        }
        throw new LogicException('Invalid Payment method, authorize request failed');

    }

    function callback(array $input)
    {
        parent::callback($input);

        $method = $input['payment']['method'];

        /**
         * Processing callback requests for wallet payment method with trait.
         * Checking method if wallet then use wallet trait.
         */

        if ($method === Payment\Method::WALLET)
        {
            return $this->walletCallback($input);
        }
        throw new LogicException('Invalid Payment method, callback request failed');
    }

    function verify(array $input)
    {
        parent::verify($input);

        $method = $input['payment']['method'];

        /**
         * Processing verify requests for wallet payment method with trait.
         * Checking method if wallet then use wallet trait.
         */

        if ($method === Payment\Method::WALLET)
        {
            return $this->walletVerify($input);
        }
        throw new LogicException('Invalid Payment method, verify request failed');
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $method = $input['payment']['method'];

        /**
         * Processing verify requests for wallet payment method with trait.
         * Checking method if wallet then use wallet trait.
         */
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletSendPaymentVerifyRequest($input);
        }
        throw new LogicException('Invalid Payment method, Mozart request call failed');
    }

    protected function verifyPayment($verify)
    {
        $method = $verify->input['payment']['method'];

        /**
         * Verifying payments for wallet payment method with trait.
         * Checking method if wallet then use wallet trait.
         */

        if ($method === Payment\Method::WALLET)
        {
            return $this->walletSendPaymentVerifyRequest($verify);
        }
        throw new LogicException('Invalid Payment method, Payment verification failed');
    }
}

<?php
namespace RZP\Gateway\Ccavenue;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Action;
use RZP\Exception\LogicException;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Wallet\Base\WalletTrait;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{
    use WalletTrait;

    use AuthorizeFailed;

    use CommonGatewayTrait;

    const ACQUIRER = 'ccavenue';

    protected $gateway = 'ccavenue';

    function authorize(array $input)
    {
        parent::authorize($input);

        $method = $input['payment']['method'];

        // If payment method is UPI, then authorize the request via UPI common trait.
        if($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }

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
         * If the payment method is UPI, call the Mozart Gateway to decrypt the response, and then
         * process the response using upi common trait.
         */
        if ($method === Payment\Method::UPI)
        {
            $mozart=$this->getUpiMozartGatewayWithModeSet();

            $response = $mozart->sendUpiMozartRequest($input,TraceCode::PAYMENT_CALLBACK_REQUEST, 'pay_verify');

            $input['gateway']= $response;

            return $this->upiCallback($input);
        }

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

        if ($method === Payment\Method::UPI)
        {
            parent::verify($input);

            $verify = new Verify($this->gateway, $input);

            return $this->runPaymentVerifyFlow($verify);
        }

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

        // If the payment method is UPI, then process the request via UPI common trait.
        if ($method === Payment\Method::UPI)
        {
            return $this->upiSendPaymentVerifyRequest($verify);
        }

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

        if ($method === Payment\Method::UPI)
        {
            $this->upiVerifyPayment($verify);

            return null;
        }

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

    /**
     * For CCAvenue UPI payments, the callback data is received on Razorpay's static route and,
     * the payment ID is not encrypted, available outside the encrypted body, So directly returning input itself,
     * without calling Mozart for decryption.
     *
     * @param $input
     * @return array
     */
    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway): string
    {
        return $response['order_id'];
    }

    /**
     * Function to post process the response of callback. In case of success, returns true.
     * However, in case of exception, suppress the error and returns failure response.
     * @param $input
     * @param null $exception
     * @return bool[] - true or false
     */
    public function postProcessServerCallback($input, $exception = null): array
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];
    }

    /**
     * If payment method upi then use upi repository to fetch payments details
     * otherwise parent repository to fetch payments details.
     * @param Verify $verify
     * @return string
     */
    public function getPaymentToVerify(Verify $verify)
    {
        $method = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                $verify->input['payment']['id'], Action::AUTHORIZE);

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }

        return parent::getPaymentToVerify($verify);
    }
}

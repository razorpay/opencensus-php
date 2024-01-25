<?php

namespace RZP\Gateway\Upi\Payu;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Exception\LogicException;
use RZP\Gateway\Upi\Base\Response;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Wallet\Base\WalletTrait;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use WalletTrait;

    use CommonGatewayTrait;

    use Base\RecurringTrait {
        debit as protected upiDebit;
    }

    const ACQUIRER = 'payu';

    protected $gateway = Payment\Gateway::PAYU;

    protected $map = [];

    public function authorize(array $input)
    {
        /**
         * Processing authorize requests for upi/wallet payment method payu.
         * Checking method if upi/wallet then send to Mozart else throw exception.
         */
        parent::authorize($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiAuthorize($input);
        }
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletAuthorize($input);
        }
        throw new LogicException('Invalid Payment method, authorize request failed');
    }

    public function debit(array $input)
    {
        if((isset($input['payment']['method']) === true) and
            ($input['payment']['method'] === Payment\Method::UPI) and
            ($input['payment']['recurring'] === true))
        {
            return $this->upiDebit($input);

        }

        $this->input = $input;
    }

    protected function updateGatewayPaymentResponse(Entity $payment, array $response, bool $shouldMap = true)
    {
        $attr = $response;

        if ($shouldMap === true)
        {
            $attr = $this->getMappedAttributes($response);
        }

        if (isset($attr[Fields::MIHPAYID]) === true)
        {
            $attr[Entity::NPCI_REFERENCE_ID] = $attr[Fields::MIHPAYID];

            $attr[Entity::GATEWAY_DATA] = $payment->getGatewayData();

            // Not exactly needed, but just to be on safer side
            if (is_array($attr[Entity::GATEWAY_DATA]) === false)
            {
                $attr[Entity::GATEWAY_DATA] = [];
            }

            $attr[Entity::GATEWAY_DATA][Entity::NPCI_REFERENCE_ID][$attr[Entity::NPCI_REFERENCE_ID]] = [
                Entity::ACTION      => $this->getAction(),
                Entity::UPDATED_AT  => Carbon::now()->getTimestamp(),
            ];
        }

        // To mark that we have received a response for this request
        $attr[Entity::RECEIVED] = 1;

        $payment->fill($attr);

        $payment->generatePspData($attr);

        $payment->saveOrFail();
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            if ($input['payment']['recurring'] === true)
            {
                $input['action'] = ACTION::AUTHORIZE;
                return $this->processRecurringCallback($input);
            }

            $mozart = $this->getUpiMozartGatewayWithModeSet();

            $result = $mozart->sendUpiMozartRequest($input, TraceCode::GATEWAY_PAYMENT_CALLBACK, 'pay_verify');

            $input['gateway'] = $result;

            $data = $this->upiCallback($input);

            if (empty($data['acquirer']) === false && empty($input['gateway']) === false)
            {
                $gateway = $input['gateway'];
                if (empty($gateway['data']) === false)
                {
                    $response = new Response($gateway['data']);
                    $upi = $response->getUpi();
                    $data['acquirer']['gateway_payment_id'] = $upi['gateway_payment_id'] ?? null;
                }

            }
            return $data;
        }
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletCallback($input);
        }
        throw new LogicException('Invalid Payment method, callback request failed');
    }

    public function preProcessServerCallback($input): array
    {
        return $input;
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        // Used for PayU emandate as well, since gateway does not allow setting
        // separate URL for diff methods at their end.
        // Pls make sure changes in this flow, do not break for emandate.
        // In future, move UPI callback to staticS2SCallbackGatewayWithModeAndMethod
        // For emandate, already handled there.
        return $response[Fields::TXNID];
    }

    protected function getActualPaymentIdFromServerCallback(array $response)
    {
        return $response[Fields::TXNID];
    }

    public function postProcessServerCallback($input, $exception = null)
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

    protected function sendPaymentVerifyRequest($verify)
    {
        $method = $input = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiSendPaymentVerifyRequest($verify);
        }
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletSendPaymentVerifyRequest($input);
        }
        throw new LogicException('Invalid Payment method, Mozart request call failed');
    }

    public function verify(array $input)
    {
        $method = $input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            parent::verify($input);

            if (($this->isFirstRecurringPayment($input) === true) or
                ($this->isSecondRecurringPayment($input) === true))
            {
                return $this->recurringPaymentVerify($input);
            }

            $verify = new Verify($this->gateway, $input);

            return $this->runPaymentVerifyFlow($verify);
        }
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletVerify($input);
        }
        throw new LogicException('Invalid Payment method, verify request failed');
    }

    protected function verifyPayment($verify)
    {
        $method = $verify->input['payment']['method'];

        if ($method === Payment\Method::UPI)
        {
            return $this->upiVerifyPayment($verify);
        }
        if ($method === Payment\Method::WALLET)
        {
            return $this->walletSendPaymentVerifyRequest($verify);
        }
        throw new LogicException('Invalid Payment method, Payment verification failed');
    }

    public function getPaymentToVerify(Verify $verify)
    {
        if ($verify->input['payment']['method'] === Payment\Method::UPI)
        {
            $gatewayPayment = $this->upiGetRepository()->findByPaymentIdAndActionOrFail(
                $verify->input['payment']['id'], Action::AUTHORIZE);

            $verify->payment = $gatewayPayment;

            return $gatewayPayment;
        }
        return parent::getPaymentToVerify($verify);
    }
}

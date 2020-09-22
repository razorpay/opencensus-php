<?php

namespace RZP\Gateway\Wallet\Openwallet;

use RZP\Constants;
use RZP\Models\Customer;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Models\Customer\Transaction\Entity as CustomerTxnEntity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_openwallet';

    protected $topup = true;

    /**
     * Authorize flow when payment method=wallet, wallet=openwallet
     *
     * @param  array  $input
     * @return void
     */
    public function authorize(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            [
                'gateway'       => $this->gateway,
                'payment_id'    => $input['payment']['id'],
                'customer_id'   => $input['payment']['customer_id'] ?? null,
            ]);

        parent::authorize($input);

        $customerTxn = (new Customer\Transaction\Core)
                            ->createForCustomerDebit($input['payment'], $input['merchant'], Constants\Entity::PAYMENT);

        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'ctxn'       => $customerTxn->toArray(),
            ]);
    }

    /**
     * Verify refund to return in Scrooge format
     *
     * @param array $input
     * @return array
     */
    public function verifyRefund(array $input)
    {
        $scroogeResponse = new ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                               ->toArray();
    }

    /**
     * Refund action handler
     * Creates a customer_transaction and debit from balance
     *
     * @param  array  $input
     * @return array
     */
    public function refund(array $input)
    {
        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'gateway'    => $this->gateway,
                'refund_id'  => $input['refund']['id'],
            ]);

        parent::refund($input);

        $customerTxn = (new Customer\Transaction\Core)
                            ->createForCustomerRefund($input, $input['merchant']);

        $response = $customerTxn->toArray();

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'refund_id'     => $input['refund']['id'],
                'ctxn'          => $response,
            ]);

        return [
            PaymentGateway::GATEWAY_RESPONSE => json_encode($response),
            PaymentGateway::GATEWAY_KEYS     => $this->getGatewayData($response),
        ];
    }

    /**
     * Reverse a customer wallet payment
     * (called via auto-refund authorized payments)
     *
     * @param  array    $input
     * @return void
     */
    public function reverse(array $input)
    {
        parent::reverse($input);

        $this->refund($input);
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    public function forceAuthorizeFailed(array $input)
    {
        $payment = $input['payment'];

        $customerTxn = $this->app['repo']
                            ->customer_transaction
                            ->findByPaymentIdAndAmountForVerify($payment['id'],
                                                                $payment['base_amount'],
                                                                $payment['merchant_id']);

        return ($customerTxn !== null);
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                CustomerTxnEntity::STATUS => $response[CustomerTxnEntity::STATUS] ?? null,
            ];
        }

        return [];
    }
}

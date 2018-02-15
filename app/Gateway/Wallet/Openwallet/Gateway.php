<?php

namespace RZP\Gateway\Wallet\Openwallet;

use RZP\Gateway\Wallet\Base;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

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
                            ->createForCustomerDebit($input['payment'], $input['merchant']);

        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'ctxn'       => $customerTxn->toArray(),
            ]);
    }

    /**
     * Refund action handler
     * Creates a customer_transaction and debit from balance
     *
     * @param  array  $input
     * @return void
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

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'refund_id'     => $input['refund']['id'],
                'ctxn'          => $customerTxn->toArray(),
            ]);
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
}

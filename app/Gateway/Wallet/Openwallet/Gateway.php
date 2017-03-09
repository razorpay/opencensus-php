<?php

namespace RZP\Gateway\Wallet\Openwallet;

use RZP\Exception;
use RZP\Error\ErrorCode;
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
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
            ]);

        parent::authorize($input);

        $txnId = (new Customer\Transaction\Service)
                    ->createForDebit($input);

        $this->trace->info(
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'payment_id' => $input['payment']['id'],
                'ctxn_id'    => $txnId,
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

        $txnId = (new Customer\Transaction\Service)->createForRefund($input);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'       => $this->gateway,
                'refund_id'     => $input['refund']['id'],
                'ctxn_id'       => $txnId,
            ]);
    }

    /**
     * Reverse a customer wallet payment
     *
     * @param  array    $input
     * @return void
     */
    public function reverse(array $input)
    {
        parent::reverse($input);

        $this->refund($input);
    }
}

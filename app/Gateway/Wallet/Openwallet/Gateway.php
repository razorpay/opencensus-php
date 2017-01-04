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
     * Pay from wallet: openwallet
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

        $txnId = $this->walletPayment($input);

        $this->trace->info(
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                [
                    'gateway'    => $this->gateway,
                    'success'    => true,
                    'payment_id' => $input['payment']['id'],
                    'ctxn_id'    => $txnId,
                ]);
    }

    protected function walletPayment(array $input)
    {
        try
        {
            return (new Customer\Transaction\Service)
                    ->createForDebit($input);
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(
                    TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                    [
                        'gateway'       => $this->gateway,
                        'payment_id'    => $input['payment']['id'],
                        'success'       => false,
                        'error_code'    => $ex->getCode(),
                        'error_message' => $ex->getMessage(),
                    ]);

            throw $ex;
        }

        return NULL;
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
        parent::refund($input);

        $this->trace->info(
                TraceCode::GATEWAY_REFUND_REQUEST,
                [
                    'gateway'    => $this->gateway,
                    'refund_id'  => $input['refund']['id'],
                ]);

        $txnId = $this->walletRefund($input);

        $this->trace->info(
                TraceCode::GATEWAY_REFUND_RESPONSE,
                [
                    'gateway'       => $this->gateway,
                    'refund_id'     => $input['refund']['id'],
                    'ctxn_id'       => $txnId,
                    'success'       => true,
                ]);
    }

    protected function walletRefund(array $input)
    {
        try
        {
            return (new Customer\Transaction\Service)->createForRefund($input);
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(
                    TraceCode::GATEWAY_REFUND_RESPONSE,
                    [
                        'gateway'       => $this->gateway,
                        'refund_id'     => $input['refund']['id'],
                        'success'       => false,
                        'error_code'    => $ex->getCode(),
                        'error_message' => $ex->getMessage(),
                    ]);

            throw $ex;
        }

        return NULL;
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

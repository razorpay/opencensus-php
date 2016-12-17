<?php

namespace RZP\Gateway\Wallet\Flashwallet;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Wallet\Base;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = 'wallet_flashwallet';

    protected $topup = true;

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

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
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

        $txnId = $this->processCustomerBalanceForRefund($input);

        $this->trace->info(
                TraceCode::GATEWAY_REFUND_RESPONSE,
                [
                    'gateway'       => $this->gateway,
                    'refund_id'     => $input['refund']['id'],
                    'ctxn_id'       => $txnId,
                    'success'       => true,
                ]);
    }

    protected function processCustomerBalanceForRefund(array $input)
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

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED);
        }
    }

}

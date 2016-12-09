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
        parent::authorize($input);
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

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $input);

        // $this->validateInputForRefund($input);

        $this->processCustomerBalanceForRefund($input);

        $this->trace->info(TraceCode::GATEWAY_REFUND_RESPONSE, []);
    }

    protected function processCustomerBalanceForRefund($input)
    {
        $customerId = Customer\Entity::getSignedId($input['payment']['customer_id']);

        try
        {
            (new Customer\Transaction\Service)->createForRefund($customerId, $input);
        }
        catch (\Throwable $ex)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                $ex->getCode(),
                $ex->getMessage());
        }
    }

}

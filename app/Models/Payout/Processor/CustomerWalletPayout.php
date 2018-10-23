<?php

namespace RZP\Models\Payout\Processor;

use RZP\Constants;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Customer\Transaction\Core as CustTransactionCore;

class CustomerWalletPayout extends Base
{
    /**
     * Since we don't want to register beneficieries for all the merchants customers.
     * Yes bank will be used as channel.
     */
    protected function setChannel()
    {
        $this->channel = Settlement\Channel::YESBANK;
    }

    protected function setPayoutDestination($input)
    {
        $this->destination = (new PayoutCore)->getPayoutDestination($input, $this->merchant, $this->customer);
    }

    protected function setCustomer(array $input)
    {
        $customerId = $input[Entity::CUSTOMER_ID];

        $this->customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
    }

    public function createPayout(array $input)
    {
        $this->setCustomer($input);

        return parent::createPayout($input);
    }

    protected function createTxns(Payout\Entity $payout)
    {
        $customerTransactionData = $this->getCustomerTransactionData($payout);

        // Create customer debit transaction.
        $customerTransaction = (new CustTransactionCore)->createForCustomerDebit($customerTransactionData,
                                                                                 $this->merchant,
                                                                          Constants\Entity::PAYOUT);
        $payout->transaction()->associate($customerTransaction);

        // Calculate merchant fee.

        // Create merchant adjustment.
    }

    private function getCustomerTransactionData(Payout\Entity $payout)
    {
        $transactionData = [
            Entity::ID          => $payout->getId(),
            Entity::AMOUNT      => $payout->getAmount(),
            Entity::CUSTOMER_ID => $payout->customer->getId(),
            Entity::DESCRIPTION => 'Wallet Withdrawal',
        ];

        return $transactionData;
    }
}


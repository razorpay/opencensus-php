<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Constants;
use RZP\Models\Pricing;
use RZP\Models\Customer;
use RZP\Models\Adjustment;
use RZP\Models\Payout\Entity;
use RZP\Models\Settlement\Channel;

class CustomerWalletPayout extends Base
{
    const DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION  = 'Debit wallet withdrawal fee amount';

    protected function createTransaction(Entity $payout)
    {
        $customerTransactionData = $this->getCustomerTransactionData($payout);

        // Create customer debit transaction.
        $customerTransaction = (new Customer\Transaction\Core)->createForCustomerDebit(
            $customerTransactionData,
            $this->merchant,
            Constants\Entity::PAYOUT);

        $payout->transaction()->associate($customerTransaction);

        // TODO: Associate the corresponding payment also to the payout?
        // We do this in payment payout.

        // Calculate merchant fee.
        list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

        // Set Fees and tax in payout.
        $payout->setFees($fee);
        $payout->setTax($tax);

        // Create adjustment only if fee is > 0.
        if ($fee > 0)
        {
            // Create merchant adjustment to deduct fee from merchant balance.
            $this->createAdjustmentForFee($payout, $fee);
        }
    }

    protected function setChannel(Entity $payout)
    {
        $channel = Channel::YESBANK;

        $payout->setChannel($channel);
    }

    protected function getCustomerTransactionData(Entity $payout)
    {
        $transactionData = [
            Entity::ID                     => $payout->getId(),
            Entity::AMOUNT                 => $payout->getAmount(),
            Entity::CUSTOMER_ID            => $payout->getCustomerId(),
            Adjustment\Entity::DESCRIPTION => 'Wallet Withdrawal',
        ];

        return $transactionData;
    }

    protected function createAdjustmentForFee(Entity $payout, $fee)
    {
        $adjustmentData = [
            Adjustment\Entity::CURRENCY    => $payout->getCurrency(),
            Adjustment\Entity::AMOUNT      => 0 - $fee,
            Adjustment\Entity::DESCRIPTION => self::DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION,
        ];

        // Create merchant adjustment.
        (new Adjustment\Core)->createAdjustmentForSource($adjustmentData, $payout);
    }
}

<?php

namespace RZP\Models\Payout\Processor;

use RZP\Constants;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Payout\Entity;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\Customer\Transaction\Core as CustTransactionCore;

class CustomerWalletPayout extends Base
{
    const DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION  = 'Debit wallet withdrawal fee amount';

    /**
     * Since we don't want to register beneficiaries for all the merchants customers.
     * Yes bank will be used as channel.
     */
    protected function setChannel()
    {
        $this->channel = Settlement\Channel::YESBANK;
    }

    protected function createTxns(Payout\Entity $payout)
    {
        $customerTransactionData = $this->getCustomerTransactionData($payout);

        // Create customer debit transaction.
        $customerTransaction = (new CustTransactionCore)->createForCustomerDebit($customerTransactionData,
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
            $this->createAdjustmentForFee($fee, $payout);
        }
    }

    private function getCustomerTransactionData(Payout\Entity $payout)
    {
        $transactionData = [
            Entity::ID                     => $payout->getId(),
            Entity::AMOUNT                 => $payout->getAmount(),
            Entity::CUSTOMER_ID            => $payout->getCustomerId(),
            Adjustment\Entity::DESCRIPTION => 'Wallet Withdrawal',
        ];

        return $transactionData;
    }

    private function createAdjustmentForFee($fee, Entity $payout)
    {
        $adjustmentData = [
            Adjustment\Entity::CURRENCY    => $payout->getCurrency(),
            Adjustment\Entity::AMOUNT      => 0 - $fee,
            Adjustment\Entity::DESCRIPTION => self::DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION,
        ];

        // Create merchant adjustment.
        (new AdjustmentCore)->createAdjustmentForSource($adjustmentData, $payout);
    }
}

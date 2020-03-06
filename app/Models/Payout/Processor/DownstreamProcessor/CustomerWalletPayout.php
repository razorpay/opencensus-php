<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor;

use RZP\Constants;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Adjustment;
use RZP\Models\Payout\Entity;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;

class CustomerWalletPayout extends Base
{
    const DEBIT_WALLET_FEE_ADJUSTMENT_DESCRIPTION  = 'Debit wallet withdrawal fee amount';

    protected function setChannel(Entity $payout)
    {
        $channel = Channel::YESBANK;

        $this->blockYesbankCustomerWalletPayoutsIfRequired($channel, $payout, $this->mode);

        $payout->setChannel($channel);
    }

    public function blockYesbankCustomerWalletPayoutsIfRequired($channel, $payout, $mode)
    {
        if ($mode === Mode::TEST)
        {
            return;
        }

        if ($channel === Channel::YESBANK)
        {
            $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::BLOCK_YESBANK_WALLET_PAYOUTS]) ?? false;

            if (boolval($config) === false)
            {
                return;
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUTS_NOT_ALLOWED_CURRENTLY,
                null,
                [
                    'channel'       => 'yesbank',
                    'merchant_id'   => $payout->getMerchantId(),
                    'payout_id'     => $payout->getId(),
                ]);
        }
    }

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

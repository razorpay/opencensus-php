<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Settlement\Ondemand as OndemandModel;
use RZP\Trace\TraceCode;

/**
 *
 * @package RZP\Models\Transaction\Processor
 */
class SettlementOndemand extends Base
{
    /** @var OndemandModel\Entity $source*/
    protected $source;

    protected function setTransactionForSource($txnId = null)
    {
        $this->setTransaction($this->createNewTransaction($txnId));
    }

    public function setFeeDefaults()
    {
        $settlementOndemandPayouts = $this->source->settlementOnDemandPayouts;

        /** @var OndemandPayout\Entity $settlementOndemandPayout*/
        foreach($settlementOndemandPayouts as $settlementOndemandPayout)
        {
            $this->fees += $settlementOndemandPayout->getFees();

            $this->tax += $settlementOndemandPayout->getTax();
        }
    }

    public function setFeeDefaultsForDualWrite($fees, $tax)
    {
        $settlementOndemandPayouts = $this->source->settlementOnDemandPayouts;

        /** @var OndemandPayout\Entity $settlementOndemandPayout*/
        foreach($settlementOndemandPayouts as $settlementOndemandPayout)
        {
            $this->fees += $settlementOndemandPayout->getFees();

            $this->tax += $settlementOndemandPayout->getTax();
        }
    }

    public function setSourceDefaults()
    {
        $txnData = [
            Transaction\Entity::CURRENCY        => $this->source->getCurrency(),
            Transaction\Entity::CHANNEL         => $this->source->merchant->getChannel(),
            Transaction\Entity::RECONCILED_AT   => null,
        ];

        $this->txn->fill($txnData);

        $this->txn[Transaction\Entity::TYPE] = Transaction\Type::SETTLEMENT_ONDEMAND;

        parent::tidbStreamingMakeshiftLogic();
    }

    public function calculateFees()
    {
        $amount = $this->source->getAmount();

        $settlementOndemandAmount = $amount - $this->fees;

        if ($settlementOndemandAmount < 100)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
                null,
                [
                    'amount' => $amount,
                    'fee'    => $this->fees
                ]);
        }

        $this->debit = $amount;

        $this->txn->setAmount($settlementOndemandAmount);
    }

    public function calculateFeesForDualWrite($fees, $tax, $feeCreditsUsed, $amountCreditsUsed, $refundCreditsUed)
    {
        $amount = $this->source->getAmount();

        $settlementOndemandAmount = $amount - $this->fees;

        if ($settlementOndemandAmount < 100)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
                null,
                [
                    'amount' => $amount,
                    'fee'    => $this->fees
                ]);
        }

        $this->debit = $amount;

        $this->txn->setAmount($settlementOndemandAmount);
    }

    public function setOtherDetails()
    {
        parent::setOtherDetails();

        $this->txn->setApiFee($this->fees);
    }

    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);

        $this->txn->setReconciledAt(null);

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setGatewayFee(0);

        $this->txn->setGatewayServiceTax(0);

        $this->updatePostedDate();
    }

    /**
     *
     * @return bool
     */
    public function shouldUpdateBalance()
    {
        $merchant = $this->repo->merchant->findOrFailPublic($this->txn->getMerchantId());

        if ($this->txn->isBalanceUpdated() === false && $merchant->isFeatureEnabled(Feature\Constants::CLS_ONBOARDING_INPROGRESS) === true)
        {
            $this->trace->info(TraceCode::TXN_FAILURE_DURING_CLS_ONBOARDING,
                [
                    'merchant_id'           => $merchant->getMerchantId(),
                    'type'                  => 'settlement_ondemand',
                ]
            );

            throw new Exception\RuntimeException(
                'CLS on-boarding in progress, please try again after some time',
                [
                    'merchant_id' => $this->txn->getMerchantId(),
                    'type'    => $this->txn->getType(),
                ]);
        }

        return $this->source->shouldValidateAndUpdateBalances();
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->validateMerchantBalance();

        parent::updateBalances($negativeLimit);
    }

    protected function validateMerchantBalance()
    {
        $debitAmount = $this->txn->getAmount();

        $hasBalance = ($this->merchantBalance->getBalance() >= $debitAmount);

        if ($hasBalance === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE,
                null);
        }
    }
}

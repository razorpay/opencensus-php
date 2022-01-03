<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;

use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Settlement\Ondemand as OndemandModel;

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

    public function setSourceDefaults()
    {
        $txnData = [
            Transaction\Entity::CURRENCY        => $this->source->getCurrency(),
            Transaction\Entity::CHANNEL         => $this->source->merchant->getChannel(),
            Transaction\Entity::RECONCILED_AT   => null,
        ];

        $this->txn->fill($txnData);

        $this->txn[Transaction\Entity::TYPE] = Transaction\Type::SETTLEMENT_ONDEMAND;
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

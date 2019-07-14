<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Pricing\Feature;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Reversal as ReversalModel;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;

/**
 * Class Reversal
 *
 * This cannot be used as it is for other types of reversals.
 * Mainly due to setting of settlement time and such.
 *
 * @package RZP\Models\Transaction\Processor
 *
 * @property Transaction\Entity   $txn
 * @property ReversalModel\Entity $source
 */
class Reversal extends Base
{
    /**
     * {@inheritdoc}
     *
     * @see Base::setTransactionForSource()
     */
    protected function setTransactionForSource()
    {
        $this->setTransaction($this->createNewTransaction());
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::fillDetails()
     */
    public function fillDetails()
    {
        $amount = $this->source->getAmount();

        $this->txn->setAmount($amount);

        // We are only filling reversal channel for payout types.
        // For transfers, we are not. Ideally, we should. But, later.
        $channel = $this->source->getChannel() ?? $this->source->merchant->getChannel();

        $this->txn->setChannel($channel);
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::setFeeDefaults()
     */
    public function setFeeDefaults()
    {
        $this->fees = 0;

        $this->tax = 0;
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::calculateFees()
     */
    public function calculateFees()
    {
        $this->credit = $this->source->getAmount() + $this->source->getFee();

        // Deducting only refund's debit amount in the forward transaction
        // case 1 is when we are reversing the refund amount + fees -> in which case we credit only what has been debited
        // case 2 is when we are reversing only the refund fees -> in which case we credit only the fees that has been debited
        if ($this->source->getEntityType() === 'refund')
        {
            if ($this->source->getAmount() === $this->source->entity->getAmount())
            {
                $this->credit = $this->source->entity->transaction->getDebit();
            }
            else if (($this->source->getAmount() === 0) and
                     ($this->source->getFee() === $this->source->entity->getFee()))
            {
                $this->credit = $this->source->entity->transaction->getFee();
            }
        }

        if ($this->source->getFee() > 0)
        {
            $feeParams = [
                Transaction\FeeBreakup\Entity::NAME       => Feature::REFUND,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * ($this->source->getFee() - $this->source->getTax()),
            ];

            $taxParams = [
                Transaction\FeeBreakup\Entity::NAME       => FeeBreakupName::TAX,
                Transaction\FeeBreakup\Entity::AMOUNT     => -1 * $this->source->getTax(),
            ];

            $fee = (new Transaction\FeeBreakup\Entity)->build($feeParams);
            $tax = (new Transaction\FeeBreakup\Entity)->build($taxParams);

            $this->feesSplit->push($fee);
            $this->feesSplit->push($tax);
        }
    }

    public function setOtherDetails()
    {
        parent::setOtherDetails();

        $this->txn->setApiFee($this->fees);

        $this->txn->setFee(-1 * $this->source->getFee());

        $this->txn->setTax(-1 * $this->source->getTax());
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::updateTransaction()
     */
    public function updateTransaction()
    {
        $settledAt = $reconciledAt = time();

        // In case of payout reversal, reversal settlement
        // will be instant since payout settlement is.
        $this->txn->setSettledAt($settledAt);

        $this->txn->setReconciledAt($reconciledAt);

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setGatewayFee(0);

        $this->txn->setGatewayServiceTax(0);

        $reversalType = $this->source->getEntityType();

        switch ($reversalType)
        {
            case E::TRANSFER:
                // TODO: Call settled_at calculation method here.
                break;
        }

        $this->txn->setAttribute(Transaction\Entity::SETTLED_AT, $settledAt);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill reversals
        // with all existing reversals having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

<?php

namespace RZP\Models\Transaction\Processor;

use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Reversal as ReversalModel;
use RZP\Models\Transaction\ReconciledType;

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
        // Don't need to do anything here since default fees related stuff
        // is already 0 and we don't charge any fees for reversals.
    }

    /**
     * {@inheritdoc}
     *
     * @see Base::calculateFees()
     */
    public function calculateFees()
    {
        $this->credit = $this->source->getAmount();
    }

    public function setOtherDetails()
    {
        parent::setOtherDetails();

        // In case of reversal, this would be 0.
        $this->txn->setApiFee($this->fees);
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

    protected function setMerchantBalanceLockForUpdate()
    {
        // TODO: Remove the second condition later once we backfill reversals
        // with all existing reversals having primaryBalance filled in.
        $this->merchantBalance = $this->source->balance ?? $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }
}

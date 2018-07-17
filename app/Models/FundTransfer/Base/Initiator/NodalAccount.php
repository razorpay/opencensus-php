<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use RZP\Exception\RuntimeException;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Batch\Entity;

abstract class NodalAccount extends Base\Core
{
    const MIN_RTGS_AMOUNT       = 200000;

    const RTGS_CUTOFF_HOUR      = 15;

    const RTGS_CUTOFF_MINUTE    = 45;

    protected $batchFundTransfer = null;

    protected $amount = 0;

    protected $fees = 0;

    protected $tax = 0;

    protected $count = 0;

    protected $txnsCount = 0;

    protected $channel = null;

    protected $type = null;

    protected $summary = [];

    public function __construct()
    {
        $this->initSummary();

        parent::__construct();
    }

    protected $purpose           = null;

    protected function isRefund(): bool
    {
        return ($this->purpose === Attempt\Purpose::REFUND);
    }

    protected function isSettlement(): bool
    {
        return ($this->purpose === Attempt\Purpose::SETTLEMENT);
    }

    protected function getTransferMode($amount, Merchant\Entity $merchant): string
    {
        $rtgsCutoffTime = Carbon::createFromTime(
                                self::RTGS_CUTOFF_HOUR,
                                self::RTGS_CUTOFF_MINUTE,
                                0,
                                Timezone::IST)->getTimestamp();

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $mode = Mode::NEFT;

        if (($now <= $rtgsCutoffTime) and
            ($amount >= self::MIN_RTGS_AMOUNT))
        {
            $mode = Mode::RTGS;
        }

        //
        // Need this only for Piggy merchants currently. Hence
        // the check against parentId and not the merchantId.
        // Temporary solution. Proper solution coming soon.
        //
        if (in_array($merchant->getParentId(), Merchant\Preferences::ONLY_NEFT_SETTLEMENT_MIDS, true) === true)
        {
            $mode = Mode::NEFT;
        }

        return $mode;
    }

    protected function updateAttemptStatus(Base\PublicCollection $attempts)
    {
        foreach ($attempts as $attempt)
        {
            $txnsCount = $this->getTransactionsCount($attempt);

            $source = $attempt->source;

            $this->count++;

            $this->type      = $source->getEntity();

            $this->channel   = $source->getChannel();

            $this->tax       += $source->getTax();

            $this->fees      += $source->getFees();

            $this->amount    += $source->getAmount();

            $this->txnsCount += $txnsCount;

            if ($this->batchFundTransfer === null)
            {
                $this->createBatchFundTransferEntity();
            }

            $attempt->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->setStatus(Attempt\Status::INITIATED);

            $attempt->source->batchFundTransfer()->associate($this->batchFundTransfer);

            $attempt->source->setStatus(Attempt\Status::INITIATED);
        }

        $this->updateBatchFundTransferEntity();
    }

    /**
     * It'll create batchFundTransfer entity only if its not created
     */
    protected function createBatchFundTransferEntity()
    {
        $this->batchFundTransfer = new Entity;

        $input = [
            Entity::TYPE              => $this->type,
            Entity::CHANNEL           => $this->channel,
            Entity::AMOUNT            => $this->amount,
            Entity::FEES              => $this->fees,
            Entity::TAX               => $this->tax,
            Entity::TOTAL_COUNT       => 1,
            Entity::TRANSACTION_COUNT => $this->txnsCount,
            Entity::INITIATED_AT      => time(),
            Entity::API_FEE           => 0,
            Entity::GATEWAY_FEE       => 0,
            Entity::URLS              => null,
        ];

        $this->batchFundTransfer->build($input);

        $this->repo->saveOrFail($this->batchFundTransfer);
    }

    protected function updateBatchFundTransferEntity()
    {
        if ($this->batchFundTransfer === null)
        {
            throw new RuntimeException('Trying to update batchFundTransfer entity before creating');
        }

        $this->batchFundTransfer->setAmount($this->amount);

        $this->batchFundTransfer->setFees($this->fees);

        $this->batchFundTransfer->setTax($this->tax);

        $this->batchFundTransfer->setTotalCount($this->count);

        $this->batchFundTransfer->setTransactionCount($this->txnsCount);
    }

    protected function getTransactionsCount(Attempt\Entity $attempt): int
    {
        $sourceType = $attempt->getSourceType();

        switch ($sourceType)
        {
            case Attempt\Type::SETTLEMENT:

                $source = $attempt->source;

                return $source->setlTransactions->count();

            default:
                return 1;
        }
    }

    /**
     * Initialize the Settlement summary variables
     */
    protected function initSummary()
    {
        $this->summary = [
            'total' => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::NEFT  => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::RTGS  => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::IFT   => [
                'amount'    => 0,
                'count'     => 0
            ],
            Mode::IMPS   => [
                'amount'    => 0,
                'count'     => 0
            ],
        ];
    }

    protected function updateSummary($type, $amount)
    {
        $this->summary['total']['count']++;
        $this->summary['total']['amount'] += $amount;

        $this->summary[$type]['amount'] += $amount;
        $this->summary[$type]['count']++;
    }

}

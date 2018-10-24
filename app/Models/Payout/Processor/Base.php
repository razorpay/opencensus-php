<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

abstract class Base extends BaseCore
{
    protected $merchant;

    protected $customer = null;

    protected $method;

    protected $tax;

    protected $fees;

    protected $destination;

    protected $channel;

    public function __construct()
    {
        parent::__construct();

        $this->tax = 0;

        $this->fees = 0;

        $this->customer = null;
    }

    /**
     * Create Payout will drive the payout cycle for merchant/customer.
     *
     * @param array $input
     *
     * @return Payout\Entity
     */
    public function createPayoutEntity(array $input)
    {
        $payout = (new Payout\Entity)->build($input);

        $payout->merchant()->associate($this->merchant);

        if (empty($this->customer) === false)
        {
            $payout->customer()->associate($this->customer);
        }

        $payout->setChannel($this->channel);

        $payout->destination()->associate($this->destination);

        return $payout;
    }

    public function createPayout(array $input)
    {
        return $this->repo->transaction(function () use ($input)
        {
            $this->setPayoutDestination($input);

            $this->setChannel();

            $payout = $this->createPayoutEntity($input);

            $this->createFundTransferAttemptEntity($payout);

            // Create merchant/customer transactions and link it to payout.
            $this->createTxns($payout);

            // Set Fees and tax in payout.
            $payout->setFees($this->fees);
            $payout->setTax($this->tax);

            $this->repo->saveOrFail($payout);

            return $payout;
        });
    }

    public function createFundTransferAttemptEntity($payout): FundTransferAttempt\Entity
    {
        return (new Payout\Core)->createPayoutAttemptEntity($payout);
    }

    abstract protected function setChannel();

    abstract protected function setPayoutDestination($input);

    abstract protected function createTxns(Payout\Entity $payout);
}


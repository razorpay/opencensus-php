<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

/**
 * Payouts base where we will have a generic flow for the customer/merchants payouts.
 * Class Base
 * @package RZP\Models\Payout\Processor
 */
abstract class Base extends BaseCore
{
    protected $merchant;

    protected $customer = null;

    /**
     * method by which the payout will be made for destination.
     * @var string
     */
    protected $method;

    protected $tax;

    /**
     * @var int
     */
    protected $fees;

    /**
     * Destination can be bank accounts/wallets/any other destination where the money should be deposited.
     */
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

        $payout->customer()->associate($this->customer);

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

            // Create a payout entity
            $payout = $this->createPayoutEntity($input);

            // Need to create a fund transfer entity where the fund transfers will be processed.
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

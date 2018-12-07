<?php

namespace RZP\Models\Payout\Processor;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Transaction;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Payout\Metric;
use RZP\Trace\TraceCode;

/**
 * Payouts base where we will have a generic flow for the customer/merchants payouts.
 * Class Base
 * @package RZP\Models\Payout\Processor
 */
abstract class Base extends BaseCore
{
    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var Customer\Entity
     */
    protected $customer;

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

    public function __construct(Merchant\Entity $merchant, string $customerId = null)
    {
        parent::__construct();

        $this->tax = 0;

        $this->fees = 0;

        $this->merchant = $merchant;

        $this->setCustomerFromId($customerId);
    }

    protected function setCustomerFromId(string $customerId = null)
    {
        if ($customerId !== null)
        {
            $this->customer = $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
        }
    }

    public function createPayout(array $input)
    {
        return $this->repo->transaction(function () use ($input)
        {
            $this->preValidations();

            // TODO: Figure out something better for `typeEntity` concept
            $typeEntity = $this->customer ?? $this->merchant;

            $this->setPayoutDestination($input, $typeEntity);

            $this->setChannel();

            // Create a payout entity
            $payout = $this->createPayoutEntity($input);

            // Need to create a fund transfer entity where the fund transfers will be processed.
            $this->createFundTransferAttemptEntity($payout);

            // Create merchant/customer transactions and link it to payout.
            $this->createTxns($payout);

            $this->repo->saveOrFail($payout);

            $this->trace->info(
                TraceCode::PAYOUT_CREATED,
                [
                    'input' => $input,
                    'payout' => $payout->toArray(),
                    'type_entity' => $typeEntity->getId(),
                ]);

            $this->trace->count(Metric::PAYOUT_CREATED, [], 1);

            return $payout;
        });
    }

    /**
     * Create Payout will drive the payout cycle for merchant/customer.
     *
     * @param array $input
     *
     * @return Payout\Entity
     */
    protected function createPayoutEntity(array $input)
    {
        $payout = (new Payout\Entity)->build($input);

        $this->runInputValidations($payout, $input);

        $payout->merchant()->associate($this->merchant);

        $payout->customer()->associate($this->customer);

        $payout->setChannel($this->channel);

        $payout->destination()->associate($this->destination);

        return $payout;
    }

    protected function createFundTransferAttemptEntity(Payout\Entity $payout): FundTransferAttempt\Entity
    {
        $fundTransferAttemptInput = [
            FundTransferAttempt\Entity::PURPOSE         => $payout->getPurpose(),
            FundTransferAttempt\Entity::CHANNEL         => $payout->getChannel(),
            FundTransferAttempt\Entity::NARRATION       => 'RAZORPAY SETTLEMENT',
        ];

        if ($payout->getDestinationType() === Constants\Entity::BANK_ACCOUNT)
        {
            $fundTransferAttempt = (new FundTransferAttempt\Core)->createWithBankAccount($payout,
                                                                                         $payout->destination,
                                                                                         $fundTransferAttemptInput);
        }
        else
        {
            $fundTransferAttempt = (new FundTransferAttempt\Core)->createWithVpa($payout,
                                                                                 $payout->destination,
                                                                                 $fundTransferAttemptInput);
        }

        return $fundTransferAttempt;
    }

    protected function preValidations()
    {
        // If SKIP_HOLD_FUNDS_ON_PAYOUT feature is enabled for merchant,
        // then we don't check the merchant funds_on_hold and proceed with payout creation
        if (($this->merchant->isFeatureEnabled(Features::SKIP_HOLD_FUNDS_ON_PAYOUT) === false) and
            ($this->merchant->getHoldFunds() === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD);
        }
    }

    protected function runInputValidations(Payout\Entity $payout, array $input)
    {
        $validatorOperation = camel_case(class_basename(get_called_class()));

        $validator = $payout->getValidator();

        $validator->validateInput(camel_case($validatorOperation), $input);
    }

    /**
     * @param array        $input
     * @param PublicEntity $typeEntity This can either be a merchant or a customer.
     *                                 The destination must belong to either the customer or the merchant
     *                                 based on the type of payout this is.
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function setPayoutDestination(array $input, PublicEntity $typeEntity)
    {
        $destinationId = $this->getDestinationId($input);

        if (is_string($destinationId) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "the destination field is required",
                Payout\Entity::DESTINATION,
                [
                    'input' => $input,
                    'type_entity' => $typeEntity->getEntityName(),
                    'entity_id' => $typeEntity->getId(),
                ]);
        }

        $destination = null;

        if ($input[Payout\Entity::METHOD] === Payout\Method::FUND_TRANSFER)
        {
            $destination = $this->repo->bank_account->findByPublicIdAndMerchant($destinationId, $this->merchant);
        }
        else
        {
            $destination = $this->repo->vpa->findByPublicIdAndMerchant($destinationId, $this->merchant);
        }

        if (empty($destination) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Destination not valid for the method ' . $input[Payout\Entity::METHOD]);
        }

        // Check if the bank account / vpa destination is linked to the customer/merchant
        if ($destination->getEntityId() !== $typeEntity->getId())
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid destination_id: ' . $destination->getPublicId());
        }

        $this->destination = $destination;
    }

    protected function getDestinationId(array $input)
    {
        return $input[Payout\Entity::DESTINATION] ?? null;
    }

    protected function createTxns(Payout\Entity $payout)
    {
        $txnCore = new Transaction\Core;

        $txn = $txnCore->createFromPayout($payout);

        $payout->setFees($txn->getFee());
        $payout->setTax($txn->getTax());

        $this->validateMerchantBalance($payout);

        $txnCore->updateBalances($txn, true);

        $this->repo->saveOrFail($txn);
    }

    protected function validateMerchantBalance(Payout\Entity $payout)
    {
        $debitAmount = $payout->getAmount() + $payout->getFees();

        $hasBalance = (new Merchant\Balance\Core)->checkMerchantBalance($payout->merchant, $debitAmount);

        if ($hasBalance === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE);
        }
    }

    abstract protected function setChannel();
}

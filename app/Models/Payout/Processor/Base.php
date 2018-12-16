<?php

namespace RZP\Models\Payout\Processor;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Transaction;
use RZP\Models\Payout\Metric;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

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

    /**
     * @var Balance\Entity
     */
    protected $balance;

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
        $this->preValidations();

        // TODO: Figure out something better for `typeEntity` concept
        $typeEntity = $this->customer ?? $this->merchant;

        $this->setPayoutDestination($input, $typeEntity);

        $this->setPayoutBalance($input);

        $this->setChannel();

        return $this->repo->transaction(function () use ($input, $typeEntity)
        {
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

        $payout->balance()->associate($this->balance);

        $this->associateUserIfApplicable($payout);

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

    protected function setPayoutBalance(array $input)
    {
        // TODO: Change to `source_account` instead of `balance_id`
        $balanceId = $input[Payout\Entity::BALANCE_ID] ?? null;

        if (empty($balanceId) === true)
        {
            $this->balance = $this->merchant->primaryBalance;
        }
        else
        {
            $this->balance = $this->repo->balance->findByPublicIdAndMerchant($balanceId, $this->merchant);
        }
    }

    protected function getDestinationId(array $input)
    {
        return $input[Payout\Entity::DESTINATION] ?? null;
    }

    protected function createTxns(Payout\Entity $payout)
    {
        list ($txn, $feeSplit) = (new Transaction\Processor\Payout($payout))->createTransaction();

        //
        // In an on-demand payout, whatever payout amount the merchant asks for, we DO NOT create
        // a payout for that amount. Instead, we deduct some fees from that amount and create the
        // payout with the REMAINING amount. For example: If a merchant wants a payout of 100rs,
        // we create a payout of 98rs only and keep the remaining 2rs as fees.
        //
        // In case of a normal payout, we add extra fees to the actual payout amount and deduct
        // that much amount of money from the merchant's balance. For example, if a merchant wants
        // to do a payout of 100rs, we create a payout of 100rs and then deduct 102rs from his balance.
        // The 2rs extra is our fees. The reason we don't deduct from the actual payout amount here is
        // because in most cases normal payout is used to payout some money to a customer (of the merchant).
        // The customer would always expect a certain amount. (we can have customer fee bearer concept later).
        //
        // In case of on-demand, it's basically a customer fee bearer kind of concept, where in the customer
        // is the actual merchant himself. He bears the fees for the payout to his account. Hence, the payout
        // happens after deducting the razorpay fees from the actual payout amount. For this reason, we also
        // reset the payout amount here.
        //
        // In both the above cases, we need to ensure that the merchant has enough balance in his account.
        // The validation for the balance would always be payout's amount + our fees.
        //

        if ($payout->getPayoutType() === Payout\Entity::ON_DEMAND)
        {
            // Here, payout amount is the amount requested by merchant for payout and fees is
            // levied over it. Also, this fees is deducted from merchant balance. This happens for
            // merchants who do not have 'es_on_demand' feature enabled. In case of 'es_on_demand'
            // merchants, payout fees will be deducted from payout amount requested by the merchant.
            // This is done to allow a merchant to do a payout on requested amount, rather than
            // calculating fees over it and failing a transaction if merchant does not have enough balance.
            $payout->setAmount($txn->getAmount());
        }

        $payout->setFees($txn->getFee());
        $payout->setTax($txn->getTax());

        $this->repo->saveOrFail($txn);
    }

    /**
     * Naive audit logging.
     * Sets the user for requests from dashboard (proxy_auth)
     * On private auth, user_id is unset.
     *
     * @param Payout\Entity $payout
     */
    protected function associateUserIfApplicable(Payout\Entity $payout)
    {
        $user = app('basicauth')->getUser();

        $payout->user()->associate($user);
    }

    abstract protected function setChannel();
}

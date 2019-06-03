<?php

namespace RZP\Models\Payout\Processor;

use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\Transaction;
use RZP\Models\Payout\Metric;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
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
     * @var Batch\Entity
     */
    protected $batch;

    /**
     * @var Customer\Entity
     */
    protected $customer;

    /**
     * Method by which the payout will be made.
     * @var string
     */
    protected $method;

    /**
     * @var int
     */
    protected $tax = 0;

    /**
     * @var int
     */
    protected $fees = 0;

    /**
     * @var string|null
     */
    protected $channel;

    /**
     * @var Balance\Entity
     */
    protected $balance;

    /**
     * @var BankAccount\Entity|Vpa\Entity|Card\Entity
     */
    protected $fundTransferDestination;

    public function createPayout(array $input): Payout\Entity
    {
        $this->preValidations();

        $this->setPayoutBalance($input);

        $this->setChannel($input);

        $payout = $this->repo->transaction(function () use ($input)
        {
            // Create a payout entity
            $payout = $this->createPayoutEntity($input);

            try
            {
                // Create merchant/customer transactions and link it to payout.
                $this->createTxns($payout);

                // Create a fund transfer entity where the fund transfers will be processed.
                // NOTE: Ensure that this is created after transaction creation, so that if
                // the transaction creation fails because of insufficient funds and we want
                // to queue the payout instead of failing the complete DB transaction, this
                // FTA does not get created.
                $this->createFundTransferAttemptEntity($payout);
            }
            catch (Exception\BadRequestException $ex)
            {
                //
                // This needs to be done since while creating a transaction we also associate
                // the source (payout) with the transaction and then we fail the transaction
                // creation due to insufficient balance and then later attempt to save the payout.
                // Payout save fails because we associated the failed transaction with the payout
                // but we had not actually saved the transaction in the DB.
                //
                $payout->transaction()->dissociate();

                $insufficientFundsErrorCode = ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING;

                if ($ex->getError()->getInternalErrorCode() === $insufficientFundsErrorCode)
                {
                    $this->handleInsufficientFunds($ex, $payout);
                }
                else
                {
                    throw $ex;
                }
            }

            $this->repo->saveOrFail($payout);

            $this->trace->info(
                TraceCode::PAYOUT_CREATED,
                [
                    'input'       => $input,
                    'payout'      => $payout->toArray(),
                ]);

            $this->trace->count(Metric::PAYOUT_CREATED, [], 1);

            return $payout;
        });

        if ($payout->isStatusQueued() === true)
        {
            $this->app->events->fire('api.payout.queued', [$payout]);
        }
        else
        {
            // api.payout.created to be removed after merchants have migrated.
            $this->app->events->fire('api.payout.created', [$payout]);
            $this->app->events->fire('api.payout.initiated', [$payout]);
        }

        return $payout;
    }

    public function processQueuedPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->repo->transaction(
                    function () use ($payout)
                    {
                        // Create merchant/customer transactions and link it to payout.
                        $this->createTxns($payout);

                        // TODO: Later, we will have to handle active / inactive stuff also here.
                        // Refer the function `fetchAndAssociatePayoutAccount`
                        // Also, this will have to be fixed for MerchantPayout since there the fundTransferDestination
                        // is merchant's bank account.
                        $this->fundTransferDestination = $payout->fundAccount->account;

                        // Create a fund transfer entity where the fund transfers will be processed.
                        $this->createFundTransferAttemptEntity($payout);

                        $payout->setStatus(Payout\Status::CREATED);

                        $this->repo->saveOrFail($payout);

                        $this->trace->info(
                            TraceCode::QUEUED_PAYOUT_CREATED,
                            [
                                'payout_id'      => $payout->getId(),
                                'transaction_id' => $payout->getTransactionId(),
                                'payout_status'  => $payout->getStatus(),
                            ]);

                        return $payout;
                    });

        $this->app->events->fire('api.payout.initiated', [$payout]);

        //
        // This needs to be done only for fund_account type and not for others.
        // We need to figure out at this stage what type of payout are we processing in queue.
        // Since, currently, we only do fund_account, we are not handling it. Once we start
        // processing queued payouts for other types also, this needs to be changed.
        //
        (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);

        return $payout;
    }

    /**
     * Set the merchant context, always required.
     *
     * @param Merchant\Entity $merchant
     *
     * @return Base
     */
    public function setMerchant(Merchant\Entity $merchant): self
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function setBatch(Batch\Entity $batch = null): self
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * Set the customer relation for the Payout.
     * To be used only for the customer wallet use case: customer_id is treated
     * as a Payout source
     *
     * @param Customer\Entity $customer
     *
     * @return self
     */
    public function setSourceCustomer(Customer\Entity $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    protected function fetchAndAssociatePayoutAccount(Payout\Entity $payout, array $input)
    {
        $fundAccountId = $input[Payout\Entity::FUND_ACCOUNT_ID];

        /** @var FundAccount\Entity $fundAccount */
        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

        if ($fundAccount->isActive() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payouts cannot be created on an inactive fund account',
                Payout\Entity::FUND_ACCOUNT_ID);
        }

        if (optional($fundAccount->source)->isActive() === false)
        {
            $sourceEntity = $fundAccount->source->getEntity();

            throw new Exception\BadRequestValidationFailureException(
                'Payouts cannot be created on an inactive ' . $sourceEntity . ' fund account',
                Payout\Entity::FUND_ACCOUNT_ID);
        }

        $payout->fundAccount()->associate($fundAccount);

        $this->fundTransferDestination = $fundAccount->account;
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
        $payout = (new Payout\Entity);

        $payout->merchant()->associate($this->merchant);

        $payout->customer()->associate($this->customer);

        $payout->setChannel($this->channel);

        $this->fetchAndAssociatePayoutAccount($payout, $input);

        $this->setMethod($payout);

        $payout->balance()->associate($this->balance);

        //
        // Doing this after all the associations since
        // the modifiers and validators require payout
        // account and merchant to be associated.
        //
        $payout = $payout->build($input);

        //
        // Doing only user and batch association after build because
        // since it is present in $defaults, the association
        // gets overridden with the default value (null)
        // in the build function.
        // NOTE: Not sure why it does not happen with FundAccount. (todo: check)
        //
        $this->associateUserIfApplicable($payout);

        $payout->batch()->associate($this->batch);

        //
        // Doing this after all the associations since
        // some validations run on the relations' data
        //
        $this->runInputValidations($payout, $input);

        if ((isset($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true) and
            (boolval($input[Payout\Entity::QUEUE_IF_LOW_BALANCE]) === true))
        {
            $payout->setQueueFlag(true);
        }

        (new Payout\Purpose)->setPurposeAndTypeForPayout($payout, $payout->getPurpose());

        return $payout;
    }

    protected function createFundTransferAttemptEntity(Payout\Entity $payout)
    {
        $ftaInput = [
            FundTransferAttempt\Entity::PURPOSE   => $payout->getPurposeType(),
            FundTransferAttempt\Entity::CHANNEL   => $payout->getChannel(),
            FundTransferAttempt\Entity::MODE      => $payout->getMode(),
            FundTransferAttempt\Entity::NARRATION => $payout->getNarration(),
        ];

        $ftaAccount = $this->fundTransferDestination;
        $ftaCore    = new FundTransferAttempt\Core;

        $ftaAccountEntity = $ftaAccount->getEntity();

        switch ($ftaAccountEntity)
        {
            case E::BANK_ACCOUNT:
                $ftaCore->createWithBankAccount($payout, $ftaAccount, $ftaInput);
                break;

            case E::VPA:
                $ftaCore->createWithVpa($payout, $ftaAccount, $ftaInput);
                break;

            case E::CARD:
                $ftaCore->createWithCard($payout, $ftaAccount, $ftaInput);
                break;

            default:
                throw new Exception\InvalidArgumentException(
                    'Payout fta destination entity is invalid. '. $ftaAccount->getEntity(),
                    [
                        'payout_id'             => $payout->getId(),
                        'fta_account_id'        => $ftaAccount->getId(),
                        'fta_account_entity'    => $ftaAccountEntity,
                    ]);
        }
    }

    protected function preValidations()
    {
        //
        // If SKIP_HOLD_FUNDS_ON_PAYOUT feature is enabled for merchant,
        // then we don't check the merchant funds_on_hold and proceed with payout creation
        //
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

    protected function setPayoutBalance(array $input)
    {
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

        (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

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

    protected function setMethod(Payout\Entity $payout)
    {
        $destinationType = $this->fundTransferDestination->getEntity();

        $method = Payout\Method::$destinationMethodMap[$destinationType];

        $payout->setMethod($method);
    }

    protected function handleInsufficientFunds(Exception\BadRequestException $ex, Payout\Entity $payout)
    {
        throw $ex;
    }

    abstract protected function setChannel($input = []);
}

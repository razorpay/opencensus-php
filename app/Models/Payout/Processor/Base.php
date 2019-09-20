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
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\Core as BaseCore;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\Processor\DownstreamProcessor\DownstreamProcessor;

/**
 * Payouts base where we will have a generic flow for the customer/merchants payouts.
 * Class Base
 * @package RZP\Models\Payout\Processor
 */
class Base extends BaseCore
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
     * @var string
     */
    protected $batchId;

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
     * @var Balance\Entity
     */
    protected $balance;

    /**
     * @var bool
     */
    protected $workflowActivated = false;

    /**
     * @var BankAccount\Entity|Vpa\Entity|Card\Entity
     */
    protected $fundTransferDestination;

    public function createPayout(array $input): Payout\Entity
    {
        $this->preValidations();

        $this->setPayoutBalance($input);

        /** @var Payout\Entity $payout */
        $payout = $this->repo->transaction(function () use ($input)
        {
            $payout = $this->handleWorkflowsIfApplicable(function() use ($input)
            {
                return $this->createPayoutEntity($input);
            });

            if ($this->workflowActivated === true)
            {
                return $payout;
            }

            $payoutType = $this->getPayoutType();

            $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                           $payout,
                                                           $this->fundTransferDestination);

            $downstreamProcessor->process();

            $this->repo->saveOrFail($payout);

            $this->trace->info(
                TraceCode::PAYOUT_CREATED,
                [
                    'input'       => $input,
                    'payout'      => $payout->toArray(),
                ]);

            return $payout;
        });

        $this->fireEventForPayoutStatus($payout);

        return $payout;
    }

    public function processQueuedPayout(Payout\Entity $payout): Payout\Entity
    {
        $payout = $this->repo->transaction(
                    function () use ($payout)
                    {
                        // TODO: Later, we will have to handle active / inactive stuff also here.
                        // Refer the function `fetchAndAssociatePayoutAccount`
                        // Also, this will have to be fixed for MerchantPayout since there the fundTransferDestination
                        // is merchant's bank account.
                        $this->fundTransferDestination = $payout->fundAccount->account;

                        $payoutType = $this->getPayoutType();

                        $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                                       $payout,
                                                                       $this->fundTransferDestination);

                        //
                        // Ensure that the queued flag in the payout entity is not set.
                        // If it is set, it's going to cause issues since the downstream processor
                        // doesn't throw an error on insufficient funds if queued flag is set.
                        // If it doesn't throw an error, we'll end up marking it created without actually
                        // creating any transaction or FTA.
                        //
                        $downstreamProcessor->process();

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

    public function processPendingPayout(Payout\Entity $payout): Payout\Entity
    {
        /** @var Payout\Entity $payout */
        $payout = $this->repo->transaction(
            function () use ($payout)
            {
                //
                // TODO: Later, we will have to handle active / inactive stuff also here.
                // Refer the function `fetchAndAssociatePayoutAccount`
                //
                $this->fundTransferDestination = $payout->fundAccount->account;

                $payoutType = $this->getPayoutType();

                //
                // We're setting the queued flag to true since Queued Payouts is always enabled
                // alongside Payout Workflows. Hence, we want to enabled the queued payout logic in
                // DownstreamProcessor
                //
                $payout->setQueueFlag(true);

                $downstreamProcessor = new DownstreamProcessor($payoutType,
                                                               $payout,
                                                               $this->fundTransferDestination);

                $downstreamProcessor->process();

                //
                // Downstream processor can set the status to queued in some cases (low balance)
                // If set, we want the payout to remain in queued so it can be processed separately.
                // Hence, payout status is set to created only if it's not already queued.
                //
                if ($payout->isStatusQueued() === false)
                {
                    $payout->setStatus(Payout\Status::CREATED);
                }

                $this->repo->saveOrFail($payout);

                $this->trace->info(
                    TraceCode::PENDING_PAYOUT_CREATED,
                    [
                        'payout_id'      => $payout->getId(),
                        'transaction_id' => $payout->getTransactionId(),
                        'payout_status'  => $payout->getStatus(),
                    ]);

                return $payout;
            });

        $this->fireEventForPayoutStatus($payout);

        if ($payout->isStatusCreated() === true)
        {
            (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
        }

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

    public function setBatch($batchIdOrBatch): self
    {
        // TODO: remove batch entity handling once ramped to 100%
        if (($batchIdOrBatch instanceof Batch\Entity) === true)
        {
            $this->batch = $batchIdOrBatch;
        }
        else if (is_string($batchIdOrBatch) === true)
        {
            $this->batchId = $batchIdOrBatch;
        }

        return $this;
    }

    /**
     * @param callable $createPayoutCallback The callable is expected to create and return a payout entity.
     *
     * @return Payout\Entity|null
     * @throws Exception\BadRequestException
     */
    protected function handleWorkflowsIfApplicable(callable $createPayoutCallback)
    {
        $areWorkflowsEnabled = $this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS);

        if ($areWorkflowsEnabled === false)
        {
            //
            // Workflows feature was not enabled.
            // The callback will create a payout entity, which we return back from here,
            // which will progressed on to DownstreamProcessor
            //
            return $createPayoutCallback();
        }

        //
        // Workflows module works on org and requires org details to be set in basicauth
        // The current flows set and override org details at multiple places. We're setting
        // this here explicitly to avoid bugs and missed flows. Not ideal, but not harmful either.
        //
        app('basicauth')->setOrgDetails($this->merchant->org);

        //
        // Call the create Payout callback that will return a base Payout entity,
        // which we further work with.
        //
        /** @var Payout\Entity $payout */
        $payout = $createPayoutCallback();

        try
        {
            //
            // Initiate the workflow process. If a workflow is triggered successfully,
            // this function will thrown an EarlyWorkflowResponse exception.
            //
            $this->app['workflow']
                 ->setEntityAndId($payout->getEntity(), $payout->getId())
                 ->setPermission(Permission\Name::CREATE_PAYOUT)
                 ->handle((new \stdClass), $payout);
        }
        catch (Exception\EarlyWorkflowResponse $ex)
        {
            $this->trace->info(TraceCode::PAYOUT_WORKFLOW_TRIGGERED, ['payout' => $payout->toArray()]);

            if ($payout === null)
            {
                $this->trace->critical(TraceCode::PAYOUT_WORKFLOW_ACTION_EXCEPTION);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE,
                    null,
                    ['payout_id' => $payout->getId()]);
            }

            // Set payout to pending and move on
            $payout->setStatus(Payout\Status::PENDING);

            $this->repo->saveOrFail($payout);

            $this->workflowActivated = true;
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_WORKFLOW_FAILURE,
                null,
                ['payout_id' => optional($payout)->getId()]);
        }

        return $payout;
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

        $this->batchId ? ($payout->setBatchId($this->batchId)) : ($payout->batch()->associate($this->batch));

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
        $validatorOperation = camel_case($this->getPayoutType());

        $validator = $payout->getValidator();

        $validator->validateInput(camel_case($validatorOperation), $input);
    }

    protected function getPayoutType()
    {
        return class_basename(get_called_class());
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

    protected function fireEventForPayoutStatus(Payout\Entity $payout)
    {
        if ($payout->isStatusQueued() === true)
        {
            $this->app->events->fire('api.payout.queued', [$payout]);
        }
        else if ($payout->isStatusPending() === true)
        {
            // TODO:: Add pending webhook trigger here
        }
        else
        {
            // api.payout.created to be removed after merchants have migrated.
            $this->app->events->fire('api.payout.created', [$payout]);
            $this->app->events->fire('api.payout.initiated', [$payout]);
        }
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
}

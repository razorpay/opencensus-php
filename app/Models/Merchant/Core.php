<?php

namespace RZP\Models\Merchant;

use ApiResponse;
use Config;
use Mail;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Jobs\DispatchRouter;
use RZP\Jobs\MerchantSync;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Admin\Permission;
use RZP\Models\BankAccount;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Models\Pricing;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Transaction;
use RZP\Models\User;
use RZP\Trace\TraceCode;
use RZP\Mail\Payout\Payout as PayoutMail;

class Core extends Base\Core
{
    use Notify;

    public function create($input)
    {
        $merchant = (new Merchant\Entity)->build($input);

        $this->trace->info(
            TraceCode::MERCHANT_CREATE,
            [
                'data' => $input
            ]);

        $merchant->setAuditAction(Action::CREATE_MERCHANT);

        $email['email'] = $input['email'];

        $merchant->getValidator()->validateInput('unique_email', $email);

        $merchant->setPricingPlan(Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID);

        $this->repo->saveOrFail($merchant);

        $this->addMerchantSupportingEntities($merchant);

        $this->syncHeimdallRelatedEntities($merchant, $input, true);

        // Updating the existing customer info and setting activated to false
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::CREATED);

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::CREATED, $merchant->toArrayEvent());

        return $merchant;
    }

    public function createSubMerchant($input, $aggregatorMerchant): Entity
    {
        // We only check for email uniqueness if the email
        // address is provided
        if (isset($input['email']) === true)
        {
            $email['email'] = $input['email'];

            (new Validator)->validateInput('unique_email', $email);
        }
        else
        {
            $input['email'] = $aggregatorMerchant->getEmail();
        }

        $merchantData['name'] = $input['name'] ?? null;

        (new Validator)->validateInput('edit_name', $merchantData);

        $subMerchant = (new Merchant\Entity)->build($input);

        $subMerchant->setAuditAction(Action::CREATE_SUBMERCHANT);

        $subMerchant->setPricingPlan($aggregatorMerchant->getPricingPlanId());

        if ($aggregatorMerchant->isMarketplace() === true)
        {
            // Use Startup Plan as the default for linked accounts
            // where transfer method pricing is 0
            $subMerchant->setPricingPlan(Pricing\DefaultPlan::STARTUP_PLAN_ID);

            $subMerchant->setMaxPaymentAmount($aggregatorMerchant->getMaxPaymentAmount());

            $subMerchant->parent()->associate($aggregatorMerchant);
        }

        $aggregatorOrgId = $aggregatorMerchant->getOrgId();

        if ($aggregatorOrgId !== null)
        {
           $org = $this->repo->org->findOrFailPublic($aggregatorOrgId);

            // Link sub-merchant to its aggregator's org
            $subMerchant->org()->associate($org);
        }

        $this->repo->saveOrFail($subMerchant);

        $this->addMerchantSupportingEntities($subMerchant);

        $this->syncHeimdallRelatedEntities($subMerchant, $input);

        return $subMerchant;
    }

    protected function addMerchantSupportingEntities(Entity $merchant)
    {
        $this->createBalance($merchant, Mode::TEST);

        (new BankAccount\Core)->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant);

        (new Detail\Core)->createMerchantDetails($merchant);

        (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);
    }

    public function syncHeimdallRelatedEntities(Entity $merchant, array $input, $create = false)
    {
        if (isset($input[Entity::GROUPS]) === true)
        {
            $this->repo->sync($merchant, Entity::GROUPS, $input[Entity::GROUPS]);
        }

        if (isset($input[Entity::ADMINS]) === true)
        {
            $this->repo->sync($merchant, Entity::ADMINS, $input[Entity::ADMINS]);

            if ($create === true)
            {
                $firstAdminId = current($input[Entity::ADMINS]);

                if ($firstAdminId !== false)
                {
                    // Update admin leads
                    $adminLead = (new AdminLead\Core)->getByAdminId($firstAdminId);

                    if (empty($adminLead) === false)
                    {
                        $adminLead->merchant()->associate($merchant);

                        $this->repo->saveOrFail($adminLead);
                    }
                }
            }
        }
    }

    public function get($id, $relations = [])
    {
        return $this->repo->merchant->findOrFailPublicWithRelations(
            $id, $relations);
    }

    /**
     * Edit merchant entity
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     * @return \RZP\Models\Merchant\Entity
     */
    public function edit($merchant, $input)
    {
        $merchant->setAuditAction(Action::EDIT_MERCHANT);

        $merchant->edit($input);

        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->saveAndNotify($merchant);

        $this->syncHeimdallRelatedEntities($merchant, $input);

        // Groups have to be saved separately
        //
        // Also since we're doing a fetch again it's better we save
        // the previous version of $merchant entity first and then fetch it.
        if ((empty($input[Entity::GROUPS]) === false) or
            (empty($input[Entity::ADMINS]) === false))
        {
            // If groups has been edited, fetch the entity again with relations.
            // Simple entity edit does not contain updated relations
            $merchant = $this->get(
                $merchant->getId(),
                [Entity::GROUPS, Entity::ADMINS]);
        }

        return $merchant;
    }

    /**
     * Edit merchant email
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param array $input
     *
     * @return \RZP\Models\Merchant\Entity
     * @throws BadRequestException
     */
    public function editEmail($merchant, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'old_email' => $merchant->getEmail(),
                'new_email' => $input['email']
            ]);

        $parentId = $merchant->getReferrer();

        if (empty($parentId) === false)
        {
            $parent = $this->repo->merchant->find($parentId);

            if ((empty($parent) === false) and
                (strtolower($merchant->getEmail()) === strtolower($parent->getEmail())))
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                    Merchant\Entity::EMAIL, $input[Merchant\Entity::EMAIL]);
            }
        }

        $merchant->edit($input, 'editEmail');

        $this->saveAndNotify($merchant);

        return $merchant;
    }

    /**
     * Edit merchant configuration
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     */
    public function editConfig($merchant, $input): Entity
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $merchant->getId(),
                'input'       => $input,
            ]);

        $merchant->edit($input, 'editConfig');

        $this->saveAndNotify($merchant);

        return $merchant;
    }

    public function createBalance($merchant, $mode)
    {
        $merchantBalance = Merchant\Balance\Entity::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        $this->repo->balance->createBalance($merchantBalance);

        return $merchantBalance;
    }

    public function getUsers(Entity $merchant)
    {
        $users = $merchant->users->callOnEveryItem('toArrayMerchant');

        return $users;
    }

    /**
     * Save merchant entity and notify on slack
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return null
     */
    protected function saveAndNotify($merchant)
    {
        $data = $this->getEditedMerchantDifference($merchant);

        $this->repo->saveOrFail($merchant);

        if (empty($data) === false)
        {
            $label   = $merchant->getBillingLabel();
            $message = $merchant->getDashboardEntityLinkForSlack($label);
            $user    = $this->getInternalUsernameOrEmail();

            $message .= ' ' . $merchant->getEntity() . ' edited by ' . $user;

            $this->app['slack']->queue(
                $message,
                $data,
                [
                    'channel'  => Config::get('slack.channels.operations_log'),
                    'username' => 'Jordan Belfort',
                    'icon'     => ':boom:'
                ]
            );
        }
    }

    /**
     * Get difference between the original and updated attributes
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @return array|null
     */
    protected function getEditedMerchantDifference($merchant)
    {
        $original = $merchant->getOriginalAttributesAgainstDirty();

        if ($original !== null)
        {
            $dirtyAttributes = $merchant->getDirty();

            $data = array();

            foreach ($original as $key => $value)
            {
                $data[$key] = '*Old*: ' . $value . PHP_EOL . '*New*: ' . $dirtyAttributes[$key];
            }

            return $data;
        }
    }

    public function action($merchant, $input)
    {
        $merchant->getValidator()->validateInput('action', $input);

        $admin = $this->app['basicauth']->getAdmin();

        $action = $input['action'];

        // Check for admin permissions
        $admin->hasMerchantActionPermissionOrFail($action);

        if ($action === Merchant\Action::ENABLE_INTERNATIONAL)
        {
            $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

            (new Methods\Core)->validatePricingForInternational($merchant, $plan);
        }

        $routePermission = Permission\Name::$actionMap[$action];

        $originalMerchant = clone $merchant;

        $function = camel_case($action);

        $merchant->$function();

        $this->app['workflow']->setPermission($routePermission)->handle(
            $originalMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, $action);

        return $merchant;
    }

    public function markGratisTransactionPostpaid(string $merchantId, int $from)
    {
        $merchant =  $this->repo->merchant->findOrFail($merchantId);

        $transactions = $this->repo->transaction->fetchGratisTransactions($merchantId, $from);

        $transactionCore = (new Transaction\Core);

        foreach($transactions as $txn)
        {
            try
            {
                $transactionCore->markGratisTransactionPostpaid($txn, $merchant);
            }
            catch (\Exception $e)
            {
                 $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::GRATIS_TO_POSTPAID_FAILED,
                    ['transaction_id' => $txn->getId()]
                );
            }
        }
    }

    public function validateFilterAttributesAndAddMerchantId($merchantId, $input)
    {
        $filters = $input[Entity::FILTERS];

        $validator = new AnalyticsValidator();

        foreach ($filters as $key => $filter)
        {
            array_push($input[Entity::FILTERS][$key], [Entity::KEY_MERCHANT_ID => $merchantId]);

            foreach ($filter as $attributes)
            {
                $validator->validateAnalyticsInputFilter($attributes);
            }
        }
    }

    /**
     * If a merchant user has a role as owner and has confirm_token set to null
     * then the user will be considered as a confirmed owner.
     *
     * @param $merchant
     * @return mixed
     */
    public function getMerchantConfirmedOwner(Merchant\Entity $merchant)
    {
        return $merchant->users()->where(Merchant\Detail\Entity::ROLE, '=', User\Role::OWNER)
                                 ->whereNull(User\Entity::CONFIRM_TOKEN)
                                 ->first();
    }

    /**
     * Pushes MerchantSync job onto queue for given event with given payload.
     *
     * Events e.g. Group got edited/deleted and we need to handle the hierarchy
     * updates in Es docs.
     *
     * This method is here at once place and will be called from few other places
     * where merchant's es doc is getting affected
     *
     * @param string $event
     * @param array  $payload
     */
    public function syncEventToEs(string $event, array $payload)
    {
        $job = new MerchantSync($this->mode, $event, $payload);

        $job->delay(Repository::ES_JOB_DELAY);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::ES_V2);
    }

    public function createBatches(Entity $merchant, array $input): array
    {
        $merchant->getValidator()->validateInput('create_batch', $input);

        $type = $input['type'];

        $input = $input['data'];

        $merchant->getValidator()->validateInput($type, $input);

        $batches = $this->repo->transaction(function() use ($input, $type, $merchant)
                   {
                        $batches = [];

                        foreach ($input as $key => $file)
                        {
                            $batchType =  $type . '_' . $key;

                            $params = [
                                Batch\Entity::FILE        => $file,
                                Batch\Entity::TYPE        => $batchType
                            ];

                            $batch = (new Batch\Core)->create($params, $merchant);

                            $batches[$batchType] = $batch->getId();
                        }

                        return $batches;
                    });

        $class = 'RZP\\Jobs\\' . studly_case($type) . 'Batch';

        $job = new $class($this->mode, $batches);

        (new DispatchRouter)->dispatchOn($job, DispatchRouter::BATCH);

        return $batches;
    }

    public function sendPayoutMail(Entity $merchant, int $from, int $to, string $email)
    {
        $payouts = $this->repo->payout->fetchProcessedPayouts($from, $to, $merchant->getId());

        $recipients = $merchant->getTransactionReportEmail();

        if (empty($email) === false)
        {
            array_push($recipients, $email);
        }

        $processed = false;

        foreach ($payouts as $payout)
        {
            $body = 'Settlement Processed<br />';
            $body = $body . 'Total Amount : Rs.' . number_format($payout->getAmount() / 100, 2, '.', '') . '<br />';

            if (empty($payout->getUtr()) === false)
            {
                $body = $body . 'UTR : ' . $payout->getUtr() . '<br />';
            }

            if (empty($merchant->bankAccount) === false)
            {
                $body = $body . 'Bank Account Number :' . $merchant->bankAccount->getAccountNumber() . '<br />';
            }

            $mailData = ['body'  =>  $body];

            $payoutMail = new PayoutMail(
                $mailData,
                $recipients);

            Mail::queue($payoutMail);

            $processed = true;
        }

        return $processed;
    }

    /**
     * This handles 3 possible cases when changing user email.
     * 1. There exists a team member with the new email
     *    Here, we swap the roles of the team member(manager) with new email and the original owner
     * 2. There exists a user(not team member) with the new email
     *    Here, we change the original owner to manager and then add the user with new email as owner
     * 3. The new email is unique so far
     *    Here, we just change the email of the original user(owner).
     *
     * @param $merchant
     * @param $originalEmail
     * @param $newEmail
     *
     * @return bool
     */
    public function changeMerchantUsersEmail(Entity $merchant, string $originalEmail, string $newEmail)
    {
        $merchantUsersCount = $merchant->users()->count();

        if ($merchantUsersCount === 0)
        {
            return false;
        }

        $teamUser = $merchant->users()->where('email', $newEmail)->first();

        $existingUser = $this->repo->user->getUserFromEmail($newEmail);

        $selfUser = $this->repo->user->getUserFromEmail($originalEmail);

        $oldOwner = $merchant->primaryOwner();

        if ((empty($oldOwner) === false) and ((empty($teamUser) === false) or (empty($existingUser) === false)))
        {
            // Assign Manager role to the old owner.
            (new User\Core)->detachAndAttachMerchantUser($oldOwner, $merchant->getId(), 'manager');
        }

        if (empty($teamUser) === false)
        {
            // Assign Owner role to the team user.
            (new User\Core)->detachAndAttachMerchantUser($teamUser, $merchant->getId(), 'owner');
        }
        elseif (empty($existingUser) === false)
        {
            // Assign owner to existing user.
            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => 'owner',
                'merchant_id' => $merchant->getId(),
            ];

            (new User\Core)->updateUserMerchantMapping($existingUser, $userMerchantMappingInputData);
        }
        elseif (empty($selfUser) === false)
        {
            $userData = [
                'email' => $newEmail,
            ];

            (new User\Core)->edit($selfUser, $userData);
        }
    }
}

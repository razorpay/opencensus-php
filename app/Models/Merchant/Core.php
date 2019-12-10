<?php

namespace RZP\Models\Merchant;

use Mail;
use Config;
use ApiResponse;
use Carbon\Carbon;
use Monolog\Logger;
use Razorpay\OAuth\Application as OAuthApp;
use Razorpay\Spine\DataTypes\Dictionary;

use RZP\Exception;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Jobs\EsSync;
use RZP\Models\Batch;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\User\Role;
use RZP\Constants\Product;
use RZP\Jobs\MerchantSync;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Admin\Action;
use RZP\Constants\Entity as E;
use RZP\Jobs\MailingListUpdate;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Merchant\Detail;
use RZP\Models\Admin\Permission;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Settings\Accessor;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\LegalEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Webhook\Stork;
use RZP\Mail\Merchant\PartnerOnBoarded;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Mail\Payout\Payout as PayoutMail;
use RZP\Models\Schedule\Task as ScheduleTask;
use Razorpay\OAuth\Exception\DBQueryException;
use RZP\Models\Merchant\Detail\ActivationFlow;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Merchant\Request as MerchantRequest;
use RZP\Models\Merchant\Detail\BusinessSubCategoryMetaData;

class Core extends Base\Core
{
    use Notify;

    // This is used in case for
    // IRCTC for sending payout
    // mails
    const MASTER_ID_MAPPING = [
        '8YPFnW5UOM91H7' => 'WMRAZOR00000',
    ];

    // in minutes
    const DEFAULT_MERCHANT_ES_SYNC_INTERVAL = 15;

    const MAX_ES_MERCHANT_SYNC_LIMIT = 1000;

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

        $org = $this->repo->org->findOrFailPublic($input[Entity::ORG_ID]);

        $planId = $org->getDefaultPricingPlanId();

        if (empty($planId) === true)
        {
              throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_NO_DEFAULT_PLAN_IN_ORG,
                    null,
                    [
                        'org_id'      => $org->getId(),
                    ]
                );
        }

        $merchant->setPricingPlan($planId);

        $merchant->org()->associate($org);

        $this->repo->saveOrFail($merchant);

        $this->savePartnerIntentInSettings($input, $merchant);

        $this->addMerchantSupportingEntities($merchant);

        $this->syncHeimdallRelatedEntities($merchant, $input, true);

        $this->upsertLegalEntity($merchant, []);

        // Updating the existing customer info and setting activated to false
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::CREATED);

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::CREATED, $merchant->toArrayEvent());

        return $merchant;
    }

    public function upsertLegalEntity(Entity $merchant, array $input)
    {
        $legalEntity = (new LegalEntity\Core)->upsert($merchant, $input);

        $merchant->legalEntity()->associate($legalEntity);

        $this->repo->saveOrFail($merchant);
    }

    /**
     * @param array  $input
     * @param Entity $aggregatorMerchant
     * @param bool   $linkedAccount
     * @param bool   $accountEntity
     *
     * @return Account\Entity|Entity
     * @throws BadRequestException
     */
    public function createSubMerchant(
        array $input,
        Entity $aggregatorMerchant,
        bool $linkedAccount = true,
        bool $accountEntity = false)
    {
        $aggregatorMerchant->getValidator()->validateSubMerchantInput($input, $linkedAccount);

        $input['email'] = $input['email'] ?? $aggregatorMerchant->getEmail();

        if ($accountEntity === true)
        {
            $entity = new Account\Entity;
        }
        else
        {
            $entity = new Entity;
        }

        $subMerchant = $entity->build($input);

        $has24x7SettlementFeature = $aggregatorMerchant->isFeatureEnabled(Feature\Constants::SETTLEMENT_24X7);

        if (($has24x7SettlementFeature === true) and
            ($linkedAccount === true))
        {
            $subMerchant->setChannel(Channel::YESBANK);
        }

        $subMerchant->setAuditAction(Action::CREATE_SUBMERCHANT);

        $this->assignSubMerchantPricingPlan($aggregatorMerchant, $subMerchant, $linkedAccount);

        // The parent Id has to be linked only when it's a marketplace
        // If both market place and referral are present when creating a referral account we should not link parentId.
        if ($aggregatorMerchant->isMarketplace() === true and $linkedAccount === true)
        {
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

        $this->addMerchantSupportingEntities($subMerchant, $aggregatorMerchant);

        $this->syncHeimdallRelatedEntities($subMerchant, $input);

        $this->upsertLegalEntity($subMerchant, []);

        return $subMerchant;
    }

    /**
     * @param Entity $merchant
     * @param Entity $subMerchant
     * @param bool   $linkedAccount
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    protected function assignSubMerchantPricingPlan(Entity $merchant, Entity $subMerchant, bool $linkedAccount = false)
    {
        // assign parent pricing plan by default
        $pricingPlan = $merchant->getPricingPlanId();

        // Use Startup Plan as the default for linked accounts where transfer method pricing is 0
        if (($merchant->isMarketplace() === true) and ($linkedAccount === true))
        {
            $pricingPlan = Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID;
        }
        elseif ($merchant->isPartner() === true)
        {
            // for partner, assign based on config defined on partner, if available
            $application = $this->getInternalPartnerApp($merchant);

            $config      = (new PartnerConfig\Core)->fetch($application);

            $pricingPlan = optional($config)->getDefaultPlanId() ?:  $pricingPlan;
        }

        $subMerchant->setPricingPlan($pricingPlan);
    }

    protected function addMerchantSupportingEntities(Entity $merchant, Entity $aggregatorMerchant = null)
    {
        $this->createBalance($merchant, Mode::TEST);

        (new BankAccount\Core)->createTestBankAccount($merchant);

        (new Methods\Core)->setDefaultMethods($merchant, $aggregatorMerchant);

        (new Detail\Core)->createMerchantDetails($merchant);

        (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);

        // Removing this feature is complicated, but
        // blindly assigning the feature to everybody is not
        (new Feature\Core)->create([
            Feature\Entity::ENTITY_TYPE     => E::MERCHANT,
            Feature\Entity::ENTITY_ID       => $merchant->getId(),
            Feature\Entity::NAME            => Feature\Constants::OTP_AUTH_DEFAULT,
        ], $shouldSync = true);
    }

    /**
     * Saves partner_intent if present in settings table
     *
     * @param array $input
     * @param array $merchant
     *
     */
    protected function savePartnerIntentInSettings(array $input, Entity $merchant)
    {
        if (isset($input[Constants::PARTNER_INTENT]) and $input[Constants::PARTNER_INTENT] === true)
        {
            $data = [
                Constants::PARTNER_INTENT       => true,
            ];

            Accessor::for($merchant, Constants::PARTNER)
                ->upsert($data)
                ->save();
        }
    }

    /**
     * Resets merchants settlements to default settlement schedule for linked accounts
     *
     * Schedule will be same as its parent settlement schedule
     *
     * @param array $merchantIds
     * @return array
     * @throws \Throwable
     */
    public function resetSettlementSchedule(array $merchantIds): array
    {
        $failed = $invalid = $processed = [];

        $merchants = $this->repo->merchant->findManyByPublicIds($merchantIds);

        foreach ($merchants as $merchant)
        {
            if ($merchant->isLinkedAccount() === false)
            {
                $invalid[] = $merchant->getId();

                continue;
            }

            try
            {
                (new ScheduleTask\Core)->createDefaultSettlementSchedule($merchant);

                $this->trace->info(
                    TraceCode::MERCHANT_SCHEDULE_UPDATED,
                    [
                        'merchant_id' => $merchant->getId()
                    ]);

                $processed[] = $merchant->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::FAILED_TO_ASSIGN_SCHEDULE,
                    [
                        'merchant_id' => $merchant->getId()
                    ]);

                $failed[] = $merchant->getId();
            }
        }

        $summary = [
            'invalid_count'   => count($invalid),
            'failed_count'    => count($failed),
            'processed_count' => count($processed),
            'invalid'         => $invalid,
            'failed'          => $failed,
            'processed'       => $processed,
        ];

        $this->trace->info(
            TraceCode::RESET_SCHEDULES_SUMMARY,
            $summary
        );

        return $summary;
    }

    public function syncHeimdallRelatedEntities(Entity $merchant, array $input, $create = false)
    {
        if (isset($input[Entity::GROUPS]) === true)
        {
            $this->repo->group->validateExists($input[Entity::GROUPS]);

            $this->repo->sync($merchant, Entity::GROUPS, $input[Entity::GROUPS]);
        }

        if (isset($input[Entity::ADMINS]) === true)
        {
            $this->repo->admin->validateExists($input[Entity::ADMINS]);

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

        $input = $this->modifyEditInput($input);

        $merchant->edit($input);

        $plan = $this->repo->pricing->getPricingPlanByIdWithoutOrgId($merchant->getPricingPlanId());

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input)
        {
            $merchantDetailCore = new Detail\Core;
            // This is used to sync fields transaction_report_email and website in merchant and merchantDetail
            $merchantDetailCore->syncToMerchantDetailFields($merchant, $input);

            $merchantDetailCore->updateLegalEntity($input, $merchant);

            $this->saveAndNotify($merchant);
        });

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

    public function modifyEditInput(array $input): array
    {
        if (array_key_exists('category', $input))
        {
            $input['category'] = (string) $input['category'];
        }
        return $input;
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
        $oldEmail = $merchant->getEmail();

        $parentId = $merchant->getReferrer();

        if (empty($parentId) === false)
        {
            $parent = $this->repo->merchant->find($parentId);

            if ((empty($parent) === false) and (strtolower($merchant->getEmail()) === strtolower($parent->getEmail())))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                    Merchant\Entity::EMAIL,
                    $input[Merchant\Entity::EMAIL]
                );
            }
        }

        $merchant->edit($input, 'editEmail');

        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'old_email' => $oldEmail,
                'new_email' => $input['email']
            ]);

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
                'activated' => $merchant->isActivated(),
                'live'      => $merchant->isLive(),
                'input'     => $input,
            ]);

        $merchant->edit($input, 'editConfig');

        $this->saveAndNotify($merchant);

        return $merchant;
    }

    public function createBalance($merchant, $mode)
    {
        $merchantBalance = $this->repo->balance->getMerchantBalanceByType($merchant->getId(), Type::PRIMARY, $mode);

        // Avoid Creating a new Balance of type Primary if already exists for the merchant,
        // This a Safety Check to avoid multiple primary balances being created,
        // applicable (rare scenarios, due to unknown bug) when a Whitelist Merchant is instantly activated
        // with primary balance is created but activated flag not set
        if ($merchantBalance !== null)
        {
            $this->trace->info(TraceCode:: MERCHANT_BALANCE_ID,
                               [
                                   "balance_id" => $merchantBalance->getId()
                               ]);

            return $merchantBalance;
        }

        $merchantBalance = Merchant\Balance\Entity::buildFromMerchant($merchant);

        $merchantBalance->setConnection($mode);

        $this->repo->balance->createBalance($merchantBalance);

        return $merchantBalance;
    }

    public function getUsers(Entity $merchant, string $product = Product::PRIMARY)
    {
        $users = $merchant->users()
                          ->wherePivot(User\Entity::PRODUCT, $product)
                          ->get()
                          ->callOnEveryItem('toArrayMerchant');

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
        $this->repo->saveOrFail($merchant);

        // Dont notify for linked account changes
        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $data = $this->getEditedMerchantDifference($merchant);

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

    public function action($merchant, $input, bool $useWorkflows = true)
    {
        $merchant->getValidator()->validateInput('action', $input);

        $action = $input['action'];

        $originalMerchant = clone $merchant;

        $function = camel_case($action);

        $merchant->$function();

        if ($useWorkflows === true)
        {
            $this->triggerWorkFlowForMerchantEditAction($originalMerchant, $merchant, $action);
        }

        $this->repo->saveOrFail($merchant);

        if($action === Merchant\Action::RELEASE_FUNDS)
        {
            $this->addMerchantToSettlementBucketOnFundsRelease($merchant);
        }

        if($action === Constants::SUSPEND)
        {
            $this->removeMerchantEmailToMailingList($merchant);
        }
        else if($action === Constants::UNSUSPEND)
        {
            $this->addMerchantEmailToMailingList($merchant);
        }

        // pipe to slack if the action is defined
        if (empty(SlackActions::$actionMsgMap[$action]) === false)
        {
            $this->logActionToSlack($merchant, $action);
        }

        return $merchant;
    }

    /**
     * Adds merchant to settlement bucket when funds are released for the merchant.
     *
     * @param Merchant\Entity $merchant
     */
    protected function addMerchantToSettlementBucketOnFundsRelease(Merchant\Entity $merchant)
    {
        $settlementTime = Carbon::now(Timezone::IST)->getTimestamp();

        (new Bucket\Core())->addMerchantToSettlementBucket('', $merchant->getId(), $settlementTime);

        $this->trace->info(
            TraceCode::MERCHANT_ADDED_TO_BUCKET_ON_RELEASE_FUNDS,
            [
                'merchant_id' => $merchant->getId(),
            ]);
    }

    /**
     * @param Entity $oldMerchant
     * @param Entity $newMerchant
     * @param string $action
     */
    protected function triggerWorkFlowForMerchantEditAction(Entity $oldMerchant, Entity $newMerchant, string $action)
    {
        $admin = $this->app['basicauth']->getAdmin();

        // Check for admin permissions
        $admin->hasMerchantActionPermissionOrFail($action);

        $routePermission = Permission\Name::$actionMap[$action];

        $this->app['workflow']->setPermission($routePermission)->handle($oldMerchant, $newMerchant);
    }

    /**
     * This function is used for getting the activation status change log of a merchant
     * @param Entity $merchant
     *
     * @return PublicCollection
     */
    public function getActivationStatusChangeLog(Entity $merchant): PublicCollection
    {
        return $merchant->getActivationStatusChangeLog();
    }

    /**
     * This function is used for updating key access of a merchant
     * @param Entity $merchant
     * @param array $input
     *
     * @return Entity
     */
    public function updateKeyAccess(Entity $merchant, array $input): Entity
    {
        $merchant->getValidator()->validateInput('keyAccess', $input);

        $this->trace->info(
            TraceCode::MERCHANT_UPDATE_KEY_ACCESS,
            ['input' => $input]);

        $oldMerchant = clone $merchant;

        $merchant->setHasKeyAccess($input[Entity::HAS_KEY_ACCESS]);

        $this->app['workflow']
             ->setEntity($merchant->getEntity())
             ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

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

    public function processMerchantAnalyticsQuery(string $merchantId, array $input): array
    {
        if (isset($input[Entity::FILTERS]) === false)
        {
            $input = $this->addDefaultAnalyticsFilter($merchantId, $input);

            return $input;
        }

        //
        // Iterates through input filters and
        // - If one filter is empty, adds one sub filter with merchant id clause
        // - If there are sub filters, adds merchant id clause in each of them
        //
        $filters = & $input[Entity::FILTERS];

        foreach ($filters as & $filter)
        {
            if (empty($filter) === true)
            {
                $filter[] = [Entity::KEY_MERCHANT_ID => $merchantId];
            }
            else
            {
                foreach ($filter as & $subFilter)
                {
                    $subFilter[Entity::KEY_MERCHANT_ID] = $merchantId;
                }
            }
        }

        return $input;
    }

    protected function addDefaultAnalyticsFilter(string $merchantId, array $input = []): array
    {
        $defaultFilter[] = [Entity::KEY_MERCHANT_ID => $merchantId];

        $input[Entity::FILTERS] = [Entity::DEFAULT_FILTER => $defaultFilter];

        return $input;
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
        MerchantSync::dispatch($this->mode, $event, $payload)->delay(Repository::ES_JOB_DELAY);
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

        $class::dispatch($this->mode, $batches);

        return $batches;
    }

    public function sendPayoutMail(Entity $merchant, int $from, int $to, string $email)
    {
        $payouts = $this->repo->payout->fetchPayoutsWithUtrNotNull($from, $to, $merchant->getId());

        $recipients = $merchant->getTransactionReportEmail();

        $merchantId = $merchant->getId();

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

            $payoutBankAccount = $payout->destination;

            if (empty($payoutBankAccount) === false)
            {
                $body = $body . '<br />' . $payoutBankAccount->getBeneficiaryName() . '<br />';
                $body = $body . 'Bank Account Number : ' . $payoutBankAccount->getAccountNumber() . '<br />';
                $body = $body . $payoutBankAccount->source->merchantDetail->getBusinessRegisteredAddress() . '<br />';
            }

            $body = $body . '<br />'
                          . 'Razorpay Software Pvt Ltd' . '<br />'
                          . 'Bank Account Number : 7911547334' . '<br />'
                          . 'Kotak Mahindra Bank 5 C/ II, <br />'
                          . 'MITTAL COURT,224, NARIMAN POINT,MUMBAI - 400 021, <br/>'
                          . 'GREATER BOMBAY,MAHARASHTRA <br /><br />';

            if (array_key_exists($merchantId, self::MASTER_ID_MAPPING) === true)
            {
                $body = $body . 'Master ID :' . self::MASTER_ID_MAPPING[$merchantId] . '<br />';
            }

            $date= Carbon::createFromTimestamp($payout->getCreatedAt(), Timezone::IST)->format('d-m-Y');

            $body = $body . 'Date Of Deposit : ' . $date . '<br />';

            $body = $body . 'Date Of Credit : ' . $date . '<br />';

            $mailData = ['body' => $body];

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
     * @param Entity $merchant
     * @param string $originalEmail
     * @param string $newEmail
     * @param string $product
     *
     * @return bool
     */
    public function changeMerchantUsersEmail(Entity $merchant, string $originalEmail, string $newEmail, string $product)
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

        $traceData = [
            'team_user' => empty($teamUser) ? null : $teamUser->getEmail(),
            'existing_user' => empty($existingUser) ? null : $existingUser->getEmail(),
            'self_user' => empty($selfUser) ? null : $selfUser->getEmail(),
            'old_owner' => empty($oldOwner) ? null : $oldOwner->getEmail(),
        ];

        $this->trace->info(TraceCode::MERCHANT_USER_EMAIL_CHANGE, $traceData);

        if ((empty($oldOwner) === false) and ((empty($teamUser) === false) or (empty($existingUser) === false)))
        {
            // Assign Manager role to the old owner.
            (new User\Core)->detachAndAttachMerchantUser($oldOwner, $merchant->getId(), 'manager', $product);
        }

        if (empty($teamUser) === false)
        {
            // Assign Owner role to the team user.
            (new User\Core)->detachAndAttachMerchantUser($teamUser, $merchant->getId(), 'owner', $product);
        }
        elseif (empty($existingUser) === false)
        {
            // Assign owner to existing user.
            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => 'owner',
                'merchant_id' => $merchant->getId(),
                'product'     => $product,
            ];

            (new User\Core)->updateUserMerchantMapping($existingUser, $userMerchantMappingInputData);
        }
        elseif (empty($selfUser) === false)
        {
            $userData = [
                'email' => $newEmail,
            ];

            (new User\Core)->edit($selfUser, $userData, 'edit_email_for_merchant');
        }
    }

    public function enableEmiMerchantSubvention(Entity $merchant, Emi\Entity $emiPlan, array $input)
    {
        $emiMerchantSub = (new EmiPlans\Entity)->build($input);

        $emiMerchantSub->merchant()->associate($merchant);

        $emiMerchantSub->emiPlan()->associate($emiPlan);

        $emiMerchantSub->generateId();

        $this->repo->saveOrFail($emiMerchantSub);

        return $emiMerchantSub->toArray();
    }

    public function getEmailsOfOwnersAndAdmins(Entity $merchant)
    {
        $emails = $merchant->users()
                           ->whereIn(User\Entity::ROLE, [User\Role::ADMIN, User\Role::OWNER])
                           ->pluck(User\Entity::EMAIL)
                           ->all();

        return $emails;
    }

    /**
     * Saves data from partner activation/deactivation requests into the settings table.
     *
     * @param Request\Entity $request
     * @param array          $submissions
     */
    public function postPartnerSubmissions(MerchantRequest\Entity $request, array $submissions)
    {
        $partnerType = $submissions[Entity::PARTNER_TYPE];

        $data[Entity::PARTNER_TYPE] = $partnerType;

        $this->trace->info(
            TraceCode::PARTNER_REQUEST_SUBMITTED,
            [
                Entity::PARTNER_TYPE       => $partnerType,
                MerchantRequest\Entity::ID => $request->getId(),
            ]);

        $dimensions = [Entity::PARTNER_TYPE => $partnerType];

        $this->trace->count(Metric::PARTNER_MARK_REQUEST, $dimensions);

        Accessor::for ($request, Constants::PARTNER)
            ->upsert($data)
            ->save();
    }

    /**
     * @param Request\Entity $merchantRequest
     *
     * @return array
     */
    public function getPartnerSubmissions(MerchantRequest\Entity $merchantRequest): array
    {
        $settings = Accessor::for($merchantRequest, Constants::PARTNER)->all();

        $response = $settings->toArray();

        return $response;
    }

    /**
     * Returns the internal dummy app created for all non-pure-platform type partners.
     * Since the app is not created for pure-platform type partners, they cannot access this feature.
     *
     * @param Entity $merchant
     *
     * @return OAuthApp\Entity
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function getInternalPartnerApp(Entity $merchant)
    {
        // For pure platforms, no internal partner app is created
        (new Validator)->validateIsNonPurePlatformPartner($merchant);

        try
        {
            $app = $this->getPartnerAppByMerchantId($merchant->getId());
        }
        catch (DBQueryException $ex)
        {
            throw new Exception\LogicException(
                'Server error app not found',
                ErrorCode::SERVER_ERROR_PARTNER_APP_NOT_FOUND,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        return $app;
    }

    /**
     * Returns an array of the partner's application ids.
     *
     * If the partner is -
     *      a pure platform partner, the result will be the list of all the ids of the apps created by the partner.
     *      a non pure platform partner, the result will have just one element - id of the internal dummy app created.
     *
     * @param Entity $merchant
     *
     * @return array
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function getPartnerApplicationIds(Entity $merchant): array
    {
        (new Validator)->validateIsPartner($merchant);

        if ($merchant->isPurePlatformPartner() === true)
        {
            // Fetch all the active applications that the pure platform has created
            $apps = (new OAuthApp\Repository)->findActiveApplicationsByMerchantIdAndType($merchant->getId());

            $appIds = $apps->getIds();
        }
        else
        {
            $partnerAppId = $this->getInternalPartnerApp($merchant)->getId();

            $appIds = [$partnerAppId];
        }

        return $appIds;
    }

    /**
     * @param Entity $merchant
     * @param string $partnerType
     *
     * @return Entity
     */
    public function markAsPartner(Entity $merchant, string $partnerType): Entity
    {
        $validator = new Validator;

        $validator->validateIfAlreadyPartner($merchant);

        $validator->validateIsNotLinkedAccount($merchant);

        $validator->validatePartnerType($partnerType);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerType)
        {
            $merchant->setPartnerType($partnerType);

            $this->repo->saveOrFail($merchant);

            $this->createPartnerApp($merchant);
        });

        $dimensions = [Entity::PARTNER_TYPE => $merchant->getPartnerType()];

        $this->trace->count(Metric::PARTNER_MARKED_TOTAL, $dimensions);

        return $merchant;
    }

    /**
     * Sets the Partner type attribute as null
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function unmarkAsPartner(Entity $merchant): Entity
    {
        (new Validator)->validateIsPartner($merchant);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $this->deleteSupportingEntities($merchant);

            // removes partner application and associated merchant access map entries for non-platform partners
            $this->deletePartnerApp($merchant);

            $merchant->setPartnerType();

            $this->repo->saveOrFail($merchant);
        });

        return $merchant;
    }

    /**
     * Updates partner type on merchant's request
     *
     * @param Entity $merchant
     * @param String $partnerType
     *
     * @return Entity
     */
    public function updatePartnerType(Entity $merchant, string $partnerType): array
    {
        $partner = $this->repo->transactionOnLiveAndTest(function () use ($merchant, $partnerType)
        {
            $partner = $this->markAsPartner($merchant, $partnerType);

            $application = $this->getPartnerAppByMerchantId($merchant->getId());

            $config = [
                PartnerConfig\Entity::DEFAULT_PLAN_ID       => Pricing\DefaultPlan::SUBMERCHANT_PRICING_OF_ONBOARDED_PARTNERS,
                PartnerConfig\Entity::IMPLICIT_PLAN_ID      => Pricing\DefaultPlan::PARTNER_COMMISSION_PLAN_ID,
                PartnerConfig\Entity::COMMISSIONS_ENABLED   => true,
                PartnerConfig\Constants::PARTNER_ID         => $partner->getId(),
            ];

            (new PartnerConfig\Core)->create($application, $config);

            return $partner;
        });

        $this->sendPartnerOnBoardedEmail($partner);

        return [
            'partner_type'              => $partnerType,
            'has_commission_configs'    => true,
        ];
    }

    protected function sendPartnerOnBoardedEmail(Entity $partner)
    {
        $data = [
            'name'         => $partner->getName(),
            'email'        => $partner->getEmail(),
            'partner_type' => $partner->getPartnerType(),
        ];

        $email = new PartnerOnBoarded($data);

        Mail::queue($email);
    }

    /**
     * This function also adds ref-tag and creates user-merchant mapping in addition to the
     * access map. The aggregator user is mapped to submerchant as an owner in cases of
     * fully managed and aggregator type partners. The aggregator type will not get mapped
     * in the future, it is only kept for backward compatibility.
     *
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @return array
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function createPartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant): array
    {
        $merchantValidator = new Validator;

        $merchantValidator->validateIsNotLinkedAccount($submerchant);
        $merchantValidator->validatePartnerIsNotSubmerchant($partner, $submerchant);

        $this->trace->info(
            TraceCode::PARTNER_CREATE_ACCESS_MAP_REQUEST,
            [
                'partner_id'     => $partner->getId(),
                'submerchant_id' => $submerchant->getId(),
            ]);

        $accessMap = $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant)
        {
            $partnerApp = $this->getInternalPartnerApp($partner);

            $config     = (new PartnerConfig\Core)->fetch($partnerApp);

            if (($config !== null) and
                ($config->getDefaultPlanId() !== null) and
                ($config->getDefaultPlanId() !== $submerchant->getPricingPlanId()))
            {
                // TODO: currently just logging, will have to send mail later to ops team
                $this->trace->info(
                    TraceCode::SUBMERCHANT_PLAN_DEFAULT_PLAN_NOT_EQUAL,
                    [
                        'partner_id'       => $partner->getId(),
                        'submerchant_id'   => $submerchant->getId(),
                        'submerchant_plan' => $submerchant->getPricingPlanId(),
                        'default_plan'     => $config->getDefaultPlanId(),
                    ]);
            }

            // Maintained for backward compatibility
            $this->addSubMerchantReferral($partner, $submerchant);

            $this->assignSubmerchantDashboardAccessIfApplicable($partner, $submerchant);

            // If the mapping already exists, the existing entity is returned
            $accessMap = (new AccessMap\Core)->addMappingForOAuthApp(
                            $partner,
                            $submerchant,
                            [
                                AccessMap\Entity::APPLICATION_ID => $partnerApp->getId(),
                            ]);

            return $accessMap;
        });

        (new Stork)->invalidateCacheForBothModeWithoutFail($submerchant->getId());

        return $accessMap->toArrayPublic();
    }

    /**
     * @param Entity $partner
     * @param Entity $submerchant
     */
    public function deletePartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant)
    {
        $this->trace->info(
            TraceCode::PARTNER_DELETE_ACCESS_MAP_REQUEST,
            [
                'partner_id'     => $partner->getId(),
                'submerchant_id' => $submerchant->getId(),
            ]);

        $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant)
        {
            $partnerApp = $this->getInternalPartnerApp($partner);

            (new AccessMap\Core)->deleteMappingForOAuthApp($submerchant, $partnerApp->getId());

            $this->removeSubMerchantReferralTag($submerchant, $partner->getId());
        });
    }

    /**
     * @param Entity $merchant
     */
    public function createPartnerApp(Entity $merchant)
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            // Don't create a dummy application for pure platforms
            return;
        }

        $name = $merchant->getName();

        // Default value is required because website is a required field to create oauth applications
        $website = $merchant->getWebsite() ?: 'https://www.razorpay.com';

        $appInput = [
            'name'     => $name,
            'website'  => $website,
        ];

        $logoUrl = $merchant->getLogoUrl();

        // Do not send the logo_url parameter if it is null. Auth service will reject it.
        if ($logoUrl !== null)
        {
            $appInput['logo_url'] = $logoUrl;
        }

        $app = app('authservice')->createApplication($appInput, $merchant->getId(), OAuthApp\Type::PARTNER);

        return $app;
    }

    /**
     * @param Entity $merchant
     *
     * @return OAuthApp\Entity|void
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function deletePartnerApp(Entity $merchant)
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            //
            // A dummy internal application for pure platforms does not exist
            // un-marking a platform partner should not delete applications and access mappings
            // only explicit delete application deletes mapping for platform partners
            //
            return;
        }

        $app = $this->getInternalPartnerApp($merchant);

        // deletes the application and access mapping
        $app = app('authservice')->deleteApplication($app->getId(), $merchant->getId());

        return $app;
    }

    public function addSubMerchantReferral($aggregratorMerchant, $account)
    {
        $refTag = 'ref-' . $aggregratorMerchant->getId();

        $this->appendTag($account, $refTag);
    }

    /**
     * The function was earlier used to just attach `owner` hence the name.
     * It now takes role as an optional input and hence user of any role can
     * be attached.
     *
     * @param string $ownerId
     * @param Entity $subMerchant
     */
    public function attachSubMerchantOwner(string $ownerId, Entity $subMerchant)
    {
        $userMerchantMappingInputData = [
            'action'      => 'attach',
            'role'        => $subMerchant->getUserOwnerRole(),
            'merchant_id' => $subMerchant->getId(),
        ];

        (new User\Service)->updateUserMerchantMapping($ownerId, $userMerchantMappingInputData);
    }

    /**
     * @param string $partnerUserId
     * @param Entity $subMerchant
     */
    public function detachSubMerchantOwner(string $partnerUserId, Entity $subMerchant)
    {
        $userMerchantMappingInputData = [
            'action'      => 'detach',
            'role'        => $subMerchant->getUserOwnerRole(),
            'merchant_id' => $subMerchant->getId(),
        ];

        (new User\Service)->updateUserMerchantMapping($partnerUserId, $userMerchantMappingInputData);
    }

    /**
     * used for deleting a single tag of a merchant
     * @param string $id
     * @param string $tagName tag which has to be deleted
     */
    public function deleteTag($id, $tagName)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant->untag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
    }

    /**
     * used for adding tags to merchant
     * This function uses retag(), which overwrites all previous tags
     * with the ones passed in the $input array
     *
     * @param string $id
     * @param array  $input which contains the tags of the merchant
     * @param bool   $slackNotify
     *
     * @return
     */
    public function addTags($id, $input, $slackNotify = false)
    {
        (new Validator)->validateInput('addTags', $input);

        $this->trace->info(TraceCode::MERCHANT_TAGS_ADD, $input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $tags = $input['tags'];

        $merchant->retag($tags);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        if ($slackNotify === true)
        {
            $this->logActionToSlack($merchant, SlackActions::TAGGED, $input);
        }

        return $merchant->tagNames();
    }

    /**
     * Adds a new tag to the merchant
     *
     * @param Entity $merchant
     * @param string $tagName
     */
    public function appendTag(Entity $merchant, string $tagName)
    {
        $this->trace->info(TraceCode::MERCHANT_TAGS_APPEND, ['tag' => $tagName]);

        $merchant->tag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        $this->trace->info(TraceCode::MERCHANT_TAGS_APPEND_COMPLETED);
    }

    /**
     * Changes the 2fa setting of the merchant.
     *
     * Only an owner can enable/disable 2fa
     * for the merchant id.
     *
     * In all cases, the owner's mobile should be setup
     *
     * In case of restricted merchant, all the users
     * associated with the merchant should have their mobile setup.
     *
     *
     * @param Entity $user
     * @param Entity $merchant
     * @param array $input
     *
     * @return array
     */
    public function change2faSetting(User\Entity $user, Entity $merchant, array $input): array
    {
        $action = $input[Entity::SECOND_FACTOR_AUTH];

        if ($input === false)
        {
            $merchant->setSecondFactorAuth($action);
            $this->repo->saveOrFail($merchant);

            return [
                Entity::SECOND_FACTOR_AUTH => $merchant->isSecondFactorAuth(),
            ];
        }

        //owner should have their own 2fa setup done
        if ($user->isSecondFactorAuthSetup() === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_OWNER_2FA_SETUP_MANDATORY);
        }

        if ($merchant->getRestricted() === true)
        {
            $query = $merchant->users()
                        ->where(function ($q)
                        {
                            $q->where(User\Entity::CONTACT_MOBILE_VERIFIED, 0)
                            ->orWhereNull(User\Entity::CONTACT_MOBILE);
                        });

            $totalUsersWithNo2faSetup = $query->get()->count();

            if ($totalUsersWithNo2faSetup !== 0)
            {
                $maxUserDetailsInError = 20;

                $usersWithNo2faSetup = $query->get()->take($maxUserDetailsInError);

                $usersWithNo2faSetupToArrayMerchant = $usersWithNo2faSetup->callOnEveryItem('toArrayMerchant');

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_USER_2FA_SETUP_REQUIRED,
                    null,
                    [
                        'total_users'   => $totalUsersWithNo2faSetup,
                        'users'         => $usersWithNo2faSetupToArrayMerchant,
                    ]);
            }
        }

        $merchant->setSecondFactorAuth($action);
        $this->repo->saveOrFail($merchant);

        return [
            Entity::SECOND_FACTOR_AUTH => $merchant->isSecondFactorAuth(),
        ];
    }

    protected function removeSubMerchantReferralTag(Entity $merchant, string $partnerId): array
    {
        $tag = 'ref-' . $partnerId;

        $tags = $this->deleteTag($merchant->getPublicId(), $tag);

        return $tags;
    }

    /**
     * Returns the submerchant with the partner context set.
     *
     * @param Entity $partner
     * @param string $submerchantId
     * @param array  $input
     *
     * @return Entity
     * @throws BadRequestException
     */
    public function getSubmerchant(Entity $partner, string $submerchantId, array $input = []): Entity
    {
        $partnerAppIds = $this->getPartnerApplicationIds($partner);

        //
        // Apps not being present is only possible in case of pure platforms where
        // the partner manually creates and deletes the oauth applications.
        // If the partner tries to access the submerchant detail api without creating an app, throw an error.
        //
        $appId = current($partnerAppIds);

        if ($appId === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OAUTH_APP_NOT_FOUND,
                null,
                [
                    Entity::ID           => $partner->getId(),
                    Entity::PARTNER_TYPE => $partner->getPartnerType(),
                ]);
        }

        if ($partner->isPurePlatformPartner() === true)
        {
            //
            // For pure platforms, a submerchant could have authorized multiple oauth applications
            // and we need to know which app mapping is being requested.
            // Hence, throw an error if the application id is missing.
            //
            if (empty($input[AccessMap\Entity::APPLICATION_ID]) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_MISSING_APPLICATION_ID,
                    AccessMap\Entity::APPLICATION_ID,
                    [
                        Entity::ID                => $partner->getId(),
                        Entity::PARTNER_TYPE      => $partner->getPartnerType(),
                    ]);
            }

            $inputAppId = $input[AccessMap\Entity::APPLICATION_ID];

            // raise an exception if the input app id does not belong to the list of oauth apps created by the partner.
            (new Validator)->validatePartnerApplicationId($inputAppId, $partnerAppIds);

            $appId = $inputAppId;
        }

        $merchant = $this->repo
                         ->merchant
                         ->findSubmerchantByIdAndConnectedAppId($submerchantId, $appId);

        $partnerUser = $partner->primaryOwner();

        $merchant = $this->getPartnerSubmerchantData($merchant, $partnerUser);

        return $merchant;
    }

    /**
     * This function checks for a mapping between the partner merchant's dummy app from
     * auth database and the submerchant. This is stored in the `merchant_access_map` table
     * on API side.
     *
     * @param  string $merchantId
     * @param  string $partnerId
     *
     * @return bool
     */
    public function isMerchantMappedToNonPurePlatformPartner(string $merchantId, string $partnerId): bool
    {
        $app = $this->getPartnerAppByMerchantId($partnerId);

        $mapping = (new AccessMap\Repository)
            ->findMerchantAccessMapOnEntityId($merchantId, $app->getId(), AccessMap\Entity::APPLICATION);

        return (empty($mapping) === false);
    }

    /**
     * @param string $merchantId
     *
     * @return OAuthApp\Entity|null
     */
    public function getPartnerAppByMerchantId(string $merchantId)
    {
        return (new OAuthApp\Repository)->findActivePartnerApplicationByMerchantId($merchantId);
    }

    /**
     * @param Entity $partner
     * @param array  $params
     *
     * @return PublicCollection
     */
    public function listSubmerchants(Entity $partner, array $params): Base\PublicCollection
    {
        $appIds = $this->getPartnerApplicationIds($partner);

        $this->trace->info(TraceCode::PARTNER_FETCH_SUBMERCHANTS,
            [
                'partner_id' => $partner->getId(),
                'app_ids'    => $appIds,
                'params'     => $params,
            ]);

        if (empty($params[Constants::APPLICATION_ID]) === false)
        {
            $inputAppId = $params[Constants::APPLICATION_ID];

            (new Validator)->validatePartnerApplicationId($inputAppId, $appIds);

            // Filter with only the input app id
            $appIds = [$inputAppId];

            // Filters will be applied based on $params. Since app id is already handled above, unsetting it here.
            unset($params[Constants::APPLICATION_ID]);
        }

        $merchants = $this->repo
                          ->merchant
                          ->fetchSubmerchantsByAppIds($appIds, $params);

        $partnerUser = $partner->primaryOwner();

        $merchants = $merchants->map(function($submerchant) use ($partnerUser)
        {
            return $this->getPartnerSubmerchantData($submerchant, $partnerUser);
        });

        return $merchants;
    }

    /**
     * @param Entity $partner
     *
     * @return PublicCollection
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    public function fetchActivatedSubMerchantsForPartner(Entity $partner): Base\PublicCollection
    {
        $appIds = $this->getPartnerApplicationIds($partner);

        return $this->repo
                    ->merchant
                    ->fetchSubmerchantsByAppIds($appIds,
                        [
                            Detail\Entity::ACTIVATION_STATUS => Entity::ACTIVATED,
                        ]);
    }

    /**
     * Fetch the list of all merchants the submerchant is associated with
     *
     * @param string $submerchantId
     *
     * @return PublicCollection
     */
    public function fetchAffiliatedPartners(string $submerchantId): PublicCollection
    {
        return $this->repo
                    ->merchant_access_map
                    ->fetchAffiliatedPartnersForSubmerchant($submerchantId)
                    ->unique(function ($item)
                    {
                        return $item->entityOwner->getId();
                    })
                    ->map(function ($item)
                    {
                        return $item->entityOwner;
                    });
    }

    protected function isPartnerUserAddedToSubmerchant(Entity $partner, Entity $submerchant): bool
    {
        $partnerUser = $partner->primaryOwner();

        $ownerIds = $submerchant->owners->getIds();

        return (in_array($partnerUser->getId(), $ownerIds, true) === true);
    }

    /**
     * Maps the partner user to the submerchant account,
     * if the partner merchant should have access to the submerchant's dashboard, and,
     * if the partner user is not already mapped to the submerchant's account.
     *
     * @param Entity $partner
     * @param Entity $submerchant
     */
    protected function assignSubmerchantDashboardAccessIfApplicable(Entity $partner, Entity $submerchant)
    {
        if ($partner->allowSubmerchantDashboardAccess() === false)
        {
            return;
        }

        if ($this->isPartnerUserAddedToSubmerchant($partner, $submerchant) === false)
        {
            // Attaches partners's user to the submerchant account as an owner
            $this->attachSubMerchantOwner($partner->primaryOwner()->getId(), $submerchant);
        }
        else
        {
            $this->trace->info(
                TraceCode::PARTNER_USER_ALREADY_OWNER_TO_SUBMERCHANT,
                [
                    'partner_id'     => $partner->getId(),
                    'submerchant_id' => $submerchant->getId(),
                ]);
        }
    }

    /**
     * Sets the partner attributes in the instance of Merchant\Entity so that toArrayPartner() can be used later.
     *
     * @param Entity      $submerchant
     * @param User\Entity $partnerUser
     *
     * @return Entity
     */
    protected function getPartnerSubmerchantData(Entity $submerchant, User\Entity $partnerUser): Entity
    {
        $submerchant[Entity::DETAILS] = [
            Detail\Entity::ACTIVATION_STATUS => $submerchant->getAttribute(Detail\Entity::ACTIVATION_STATUS),
        ];

        $submerchantOwner = $this->getNonPartnerPrimaryOwner($submerchant, $partnerUser);

        $submerchant[Entity::USER] = ($submerchantOwner === null) ? null : $submerchantOwner->toArrayPublic();

        $submerchant[Entity::DASHBOARD_ACCESS] = $this->hasSubmerchantDashboardAccess($submerchant);

        $submerchant[Entity::APPLICATION] = [
            OAuthApp\Entity::ID => $submerchant->getAttribute(Constants::APPLICATION_ID),
        ];

        return $submerchant;
    }

    /**
     * A submerchant account can have at a max of 2 users with the `owner` role -
     * One being his own user and second being the partner merchant's user linked as an owner to the submerchant.
     *
     * This function returns the first type of primary owner.
     *
     * @param Entity      $merchant
     * @param User\Entity $partnerUser
     *
     * @return null
     */
    protected function getNonPartnerPrimaryOwner(Entity $merchant, User\Entity $partnerUser)
    {
        $owners = $merchant->owners;

        foreach ($owners as $owner)
        {
            if ($owner->getEmail() !== $partnerUser->getEmail())
            {
                return $owner;
            }
        }

        return null;
    }

    /**
     * Checks whether the logged in partner user has access over the submerchant's account
     *
     * @param Entity $submerchant
     *
     * @return bool
     */
    protected function hasSubmerchantDashboardAccess(Entity $submerchant): bool
    {
        $userIds = $submerchant->users->getIds();

        $loggedInPartnerUser = $this->app['basicauth']->getUser();

        if (($loggedInPartnerUser !== null) and
            (in_array($loggedInPartnerUser->getId(), $userIds, true) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Cleans up all the supporting entities that were created when the merchant was a partner. This includes -
     * 1. All the ref tags that indicate that the merchant is a referral to a partner.
     * 2. All the mappings (merchant_users) that is currently allowing the partner user to access a submerchant.
     *
     * @param Entity $partner
     *
     * @throws BadRequestException
     * @throws Exception\LogicException
     */
    protected function deleteSupportingEntities(Entity $partner)
    {
        if ($partner->isPurePlatformPartner() === true)
        {
            // A dummy internal application for pure platforms does not exist
            return;
        }

        // Fetch partner app and then access maps
        $partnerApp = $this->getInternalPartnerApp($partner);
        $accessMaps = $this->repo
                           ->merchant_access_map
                           ->fetchMerchantAccessMapOnEntity(AccessMap\Entity::APPLICATION, $partnerApp->getId());

        //
        // Fetch subMerchants
        // access maps will be deleted as part of delete application flow
        //
        $submerchantIds = $accessMaps->pluck(AccessMap\Entity::MERCHANT_ID)->toArray();
        $submerchants   = $this->repo->merchant->findMany($submerchantIds);

        $this->deleteAllSubmerchantRefTags($submerchants, $partner);

        $this->deletePartnerDashboardAccessOnSubmerchants($partner, $submerchants);
    }

    /**
     * @param PublicCollection $submerchants
     * @param Entity           $partner
     */
    protected function deleteAllSubmerchantRefTags(Base\PublicCollection $submerchants, Entity $partner)
    {
        $tagName = 'ref-' . $partner->getId();

        foreach ($submerchants as $merchant)
        {
            $merchant->untag($tagName);

            $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);
        }
    }

    /**
     * @param Entity           $partner
     * @param PublicCollection $submerchants
     */
    protected function deletePartnerDashboardAccessOnSubmerchants(Entity $partner, Base\PublicCollection $submerchants)
    {
        $partnerUsers = $partner->users()->get();

        //
        // if partner added himself as a submerchant which used to happen before but not anymore
        // then we should not remove his own user
        //
        $submerchantIds = $submerchants->reject(function($subMerchant) use ($partner) {
            return ($subMerchant->getId() === $partner->getId());
        })->pluck(Entity::ID)->toArray();

        foreach ($partnerUsers as $partnerUser)
        {
            $merchantIdsAccessible = $partnerUser->merchants()->get()->pluck(Entity::ID)->toArray();

            $submerchantIdsAccessible = array_intersect($merchantIdsAccessible, $submerchantIds);

            $this->repo->detach($partnerUser, User\Entity::MERCHANTS, $submerchantIdsAccessible);
        }
    }

    /**
     * handles cases for la merchant users.
     * 3 possible cases like the normal merchant edit email.
     * 1. There exists a team member with the new email , we swap the roles of the team member(linked_account_admin)
     * with new email and the original linked_account_owner.
     * 2. There exists a user(not team member) with the new email Here, we change the original linked_account_owner to
     * linked_account_admin and then add the user with new email as linked_account_owner
     * 3. The new email is completely new to the razorpay and doesn't have a user account associated with it, for
     * normal merchants we used to get edit email change requests via support and admin used to directly change
     * the email. but in LA dashboard case marketplace merchants will be able to change the linked account's email at
     * any time so for any new email we will have to assign the new email as linked_account_owner and send a
     * password reset link so that the user will generate a password and login to the LA dashboard.(this ensures that
     * email is also verified.) and promote the existing linked_account_owner role user to team member.
     *
     * @param Merchant\Entity $merchant
     * @param string          $product
     *
     * @return User\Entity
     */
    public function handleLinkedAccountMerchantsUsers(Merchant\Entity $merchant, string $product)
    {
        $newEmail = $merchant->getEmail();

        $teamUser = $merchant->users()->where('email', $newEmail)->first();

        $existingUser = $this->repo->user->getUserFromEmail($newEmail);

        $oldOwner = $merchant->primaryLinkedAccountOwner();

        if (empty($oldOwner) === false)
        {
            // Assign Linked Account Admin role to the old owner.
            (new User\Core)->detachAndAttachMerchantUser($oldOwner,
                                                        $merchant->getId(),
                                                        Role::LINKED_ACCOUNT_ADMIN,
                                                        $product);
        }

        if (empty($teamUser) === false)
        {
            // Assign Linked Account owner role to the team user.
            (new User\Core)->detachAndAttachMerchantUser($teamUser,
                                                        $merchant->getId(),
                                                        Role::LINKED_ACCOUNT_OWNER,
                                                        $product);
        }
        elseif (empty($existingUser) === false)
        {
            // Assign Linked Account owner to existing user.
            $userMerchantMappingInputData = [
                'action'      => 'attach',
                'role'        => Role::LINKED_ACCOUNT_OWNER,
                'merchant_id' => $merchant->getId(),
            ];

            (new User\Core)->updateUserMerchantMapping($existingUser, $userMerchantMappingInputData);
        }
        else
        {
            list($subMerchantUser, $createdNew) =
                (new Merchant\Service)->createOrFetchUserAndAttachMerchant($merchant, $newEmail);

            // Sends Account linked communication emails to users.
            (new User\Service)->sendAccountLinkedCommunicationEmail($subMerchantUser, $merchant, $createdNew);
        }
    }

    /**
     * fetches subcategory metadata from business subcategory and business category
     * and updates merchant category and category2
     *
     * @param \RZP\Models\Merchant\Entity $merchant
     * @param string                      $category
     * @param null|string                 $subcategory
     *
     * @return \RZP\Models\Merchant\Entity
     * @throws \RZP\Exception\BadRequestException
     */
    public function autoUpdateCategoryDetails(
        Entity $merchant,
        string $category,
        string $subcategory = null): Entity
    {
        $subcategoryMetaData = BusinessSubCategoryMetaData::getSubCategoryMetaData($category, $subcategory);

        $oldData = [
            Entity::CATEGORY2 => $merchant->getCategory2(),
            Entity::CATEGORY  => $merchant->getCategory(),
        ];

        $category  = $subcategoryMetaData[Entity::CATEGORY];
        $category2 = $subcategoryMetaData[Entity::CATEGORY2];

        $merchant->setCategory2($category2);
        $merchant->setCategory($category);

        $this->repo->saveOrFail($merchant);

        $newData = [
            Entity::CATEGORY2 => $merchant->getCategory2(),
            Entity::CATEGORY  => $merchant->getCategory(),
        ];

        $this->trace->info(
            TraceCode::MERCHANT_AUTO_UPDATE_SUBCATEGORY_METADATA,
            compact('oldData', 'newData'));

        return $merchant;
    }

    /**
     * Extracts a few fields like business name and website from the input
     * and saves it to merchants as well as merchant details table.
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     */
    public function editPreSignupFields(Merchant\Entity $merchant, array $input): Entity
    {
        $businessWebsite = $input[Detail\Entity::BUSINESS_WEBSITE] ?? null;

        $preSignupInput = [
            Entity::NAME    => $input[Detail\Entity::BUSINESS_NAME],
            Entity::WEBSITE => $businessWebsite,
        ];

        (new Validator)->validateInput('edit_pre_signup', $preSignupInput);

        $this->trace->info(TraceCode::MERCHANT_EDIT, ['input' => $preSignupInput]);

        $merchant = $this->edit($merchant, $preSignupInput);

        return $merchant;
    }

    /**
     * Disables live transactions if the merchant is (instantly) activated
     *
     * @param Entity $merchant
     */
    public function disableLiveIfAlreadyActivated(Entity $merchant)
    {
        if ($merchant->isActivated() === true)
        {
            $this->disableLive($merchant);
        }
    }

    /**
     * Disables live transactions
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function disableLive(Entity $merchant): Entity
    {
        if ($merchant->isLive() === false)
        {
            return $merchant;
        }

        $this->trace->info(TraceCode::MERCHANT_LIVE_DISABLE_REQUEST);

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $merchant->liveDisable();

            $this->repo->saveOrFail($merchant);

            return $merchant;
        });

        return $merchant;
    }

    /**
     * Enables live transactions
     *
     * @param Entity $merchant
     *
     * @return Entity
     */
    public function enableLive(Entity $merchant): Entity
    {
        // return if already live
        if ($merchant->isLive() === true)
        {
            return $merchant;
        }

        $this->trace->info(TraceCode::MERCHANT_LIVE_ENABLE_REQUEST);

        $merchant = $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $merchant->liveEnable();

            $this->repo->saveOrFail($merchant);

            return $merchant;
        });

        return $merchant;
    }

    /**
     * Pushes merchant ids to Es sync queue
     *
     * @param array $input
     *
     * @return array
     */
    public function syncMerchantsToEs(array $input): array
    {
        (new Validator)->validateInput('bulk_sync_balance', $input);

        $interval = $input[Constants::INTERVAL] ?? self::DEFAULT_MERCHANT_ES_SYNC_INTERVAL;

        $minUpdatedAtTimeStamp = Carbon::now(Timezone::IST)->subMinutes($interval)->getTimestamp();

        $merchantIds = $this->repo->balance->getMerchantsIdsForEsSync($minUpdatedAtTimeStamp);

        $batches = array_chunk($merchantIds, self::MAX_ES_MERCHANT_SYNC_LIMIT, true);

        foreach ($batches as $batch)
        {
            EsSync::dispatch($this->mode, EsRepository::UPDATE, E::MERCHANT, $batch);
        }

        $resultSummary = [
            Constants::RECORDS_PROCESSED => count($merchantIds),
            Constants::INTERVAL          => $interval,
        ];

        $this->trace->info(TraceCode::MERCHANT_ES_SYNC_RESPONSE, $resultSummary);

        return $resultSummary;
    }

    /**
     * Update merchant data like international based on business category
     * and syncs merchant data with merchant_details website and business name
     *
     * @param Entity $merchant
     * @param array  $input
     *
     * @return Entity
     * @throws \Throwable
     */
    public function syncMerchantEntityFields(Entity $merchant, array $input): Entity
    {
        $merchantInput = [];

        if ((isset($input[Detail\Entity::BUSINESS_WEBSITE]) === true) and
            ($merchant->getWebsite() !== $input[Detail\Entity::BUSINESS_WEBSITE]))
        {
            $merchantInput[Entity::WEBSITE] = $input[Detail\Entity::BUSINESS_WEBSITE];

            $this->updateWhitelistedDomain($merchant, $merchantInput);
        }

        if (isset($input[Detail\Entity::BUSINESS_NAME]) === true)
        {
            $merchantInput[Entity::NAME] = $input[Detail\Entity::BUSINESS_NAME];
        }

        if (isset($input[Detail\Entity::BUSINESS_DBA]) === true)
        {
            $merchantInput[Entity::BILLING_LABEL] = $input[Detail\Entity::BUSINESS_DBA];
        }

        if (empty($merchantInput) === true)
        {
            return $merchant;
        }

        $merchant->setAuditAction(Action::EDIT_MERCHANT);

        $merchant->edit($merchantInput);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant)
        {
            $this->saveAndNotify($merchant);
        });

        return $merchant;
    }

    /**
     * Extract domain and add it in whitelisted domain
     * if previously website is present then remove it's domain from whitelisted_domain
     * and update website in business website.
     *
     * @param Entity $merchant
     * @param array  $merchantInput
     */
    public function updateWhitelistedDomain(Entity $merchant, array $merchantInput)
    {
        $website = $merchantInput[Entity::WEBSITE];

        $domain = (new TLDExtract)->getEffectiveTLDPlusOne($website);

        $oldWebsite = $merchant->getWebsite();

        if ($oldWebsite !== null)
        {
            $oldDomain = (new TLDExtract)->getEffectiveTLDPlusOne($oldWebsite);

            $this->removeDomainFromWhitelistedDomain($merchant, $oldDomain);
        }

        $this->addDomainInWhitelistedDomain($merchant, $domain);
    }

    /**
     * add domain in whitelisted domain if domain is not in autoWhitelisted domain array
     *
     * @param Entity      $merchant
     * @param string|null $domain
     */
    public function addDomainInWhitelistedDomain(Entity $merchant, string $domain = null)
    {
        $whitelistedDomains = $merchant->getWhitelistedDomains() ?? [];

        if ((in_array($domain, Entity::AUTO_WHITELISTED_DOMAINS) === false) and
            (in_array($domain, $whitelistedDomains) === false) and
            (empty($domain) === false))
        {
            array_push($whitelistedDomains, $domain);

            $merchantInput[Entity::WHITELISTED_DOMAINS] = $whitelistedDomains;

            $merchant->edit($merchantInput);
        }
    }

    /**
     * remove domain from whitelisted domain column
     *
     * @param Entity      $merchant
     * @param string|null $domain
     */
    public function removeDomainFromWhitelistedDomain(Entity $merchant, string $domain = null)
    {
        $whitelistedDomains = $merchant->getWhitelistedDomains() ?? [];

        $key = array_search($domain, $whitelistedDomains);

        if ($key !== false)
        {
            unset($whitelistedDomains[$key]);

            $merchantInput[Entity::WHITELISTED_DOMAINS] = $whitelistedDomains;

            $merchant->edit($merchantInput);
        }
    }

    /**
     * Returns maximum transaction amount for a merchant
     *
     * @param Entity $merchant
     *
     * @return int
     * @throws BadRequestException
     */
    public function getMaxPayAmount(Entity $merchant): int
    {
        //
        // for fetching merchant detail we can do $merchant->merchantDetail also
        // but this function is getting called from merchant entity so doing this will cache $merchant->merchantDetail
        // merchant detail object hence on subsequent call will get stale  merchantDetail object
        //

        $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchant->getId());

        if (($merchantDetail !== null) and
            (empty($merchant->getCategory()) === false) and
            (Detail\BusinessType::isUnregisteredBusiness($merchantDetail->getBusinessType()) === true))
        {

            //
            // Mcc can have values other then predefined values
            // for those cases we should return default values
            //
            if (BusinessSubCategoryMetaData::isMccPresentInPredefinedList((int) $merchant->getCategory()) === false)
            {
                $this->trace->count(Metric::UNREGISTERED_BUSINESS_DEFAULT_LIMIT_USED_TOTAL);

                return Entity::MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED;
            }

            $amount = BusinessSubCategoryMetaData::getFeatureValueUsingMccCode(
                BusinessSubCategoryMetaData::NON_REGISTERED_MAX_PAYABLE_AMOUNT,
                $merchant->getCategory(),
                Entity::MAX_PAYMENT_AMOUNT_DEFAULT_FOR_UNREGISTERED);
        }
        else
        {
            $amount = Entity::MAX_PAYMENT_AMOUNT_DEFAULT;
        }

        return (int) $amount;
    }

    public function getPaymentTimeoutWindow(Entity $merchant)
    {
        $accessor = Accessor::for($merchant, Settings\Module::MERCHANT);

        if ($accessor->exists(Merchant\Constants::PAYMENT_TIMEOUT_WINDOW) === false)
        {
            return null;
        }

        return (int)($accessor->get(Merchant\Constants::PAYMENT_TIMEOUT_WINDOW));
    }


    /**
     * Enable international and set convert currency as false, if applicable
     *
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @throws BadRequestException
     */
    public function activateInternationalIfApplicable(Entity $merchant, Detail\Entity $merchantDetails)
    {
        $shouldActivateInternational = $this->shouldActivateInternational($merchant, $merchantDetails);

        if ($shouldActivateInternational === true)
        {
            $merchant->enableInternational();

            $merchant->setCurrencyConversion(false);

            $this->trace->info(
                TraceCode::MERCHANT_UPDATE_INTERNATIONAL,
                [
                    'category'      => $merchantDetails->getBusinessCategory(),
                    'subcategory'   => $merchantDetails->getBusinessSubCategory(),
                ]);

            $this->trace->count(Metric::INTERNATIONAL_ACTIVATION);
        }
    }

    public function translateWebhookPayloadIfApplicable(Entity $merchant, string $payload): array
    {
        $partners = $this->fetchAffiliatedPartners($merchant->getId());

        // submerchant can belong to only one aggregator or fully managed at a time
        $partner = $partners->filter(function(Entity $partner)
        {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (empty($partner) === true)
        {
            return [
                'headers' => [],
                'content' => $payload,
            ];
        }

        $translationGateway = $this->getTranslateWebhookGateway($partner);

        if (empty($translationGateway) === true)
        {
            return [
                'headers' => [],
                'content' => $payload,
            ];
        }

        $this->trace->info(TraceCode::PARTNER_WEBHOOK_TRANSLATION,
            [
                'translation_gateway' => $translationGateway,
                'partner_id'          => $partner->getId(),
            ]);

        return $this->app['mozart']->translateWebhook($translationGateway, $payload);
    }

    protected function getTranslateWebhookGateway(Entity $partner)
    {
        return (new Settings\Service)->getForMerchant(Constants::PARTNER, Constants::TRANSLATE_WEBHOOK_GATEWAY, $partner);
    }

    /**
     * Check if merchant is eligible for international payments
     * @param Entity        $merchant
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestException
     */
    protected function shouldActivateInternational(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        $autoEnableInternational = $this->autoEnableInternational($merchant, $merchantDetails);

        if ($autoEnableInternational === false)
        {
            return false;
        }

        // If business_website is empty, then don't allow international by default
        $businessWebsite = $merchant->getWebsite() ?? $merchantDetails->getWebsite();

        $category = $merchantDetails->getBusinessCategory();

        $subcategory = $merchantDetails->getBusinessSubCategory();

        if (($merchant->isInternational() === true) or
            (empty($businessWebsite) === true) or
            (empty($category) === true))
        {
            return false;
        }

        $internationalActivationFlowFromCategory = BusinessSubCategoryMetaData::getFeatureValueUsingCategoryOrSubcategory(
            BusinessSubCategoryMetaData::INTERNATIONAL_ACTIVATION,
            $category,
            $subcategory,
            ActivationFlow::BLACKLIST);

        $featureValue = $merchantDetails->getInternationalActivationFlow() ?: $internationalActivationFlowFromCategory;

        //
        // Conditions being checked:
        // 1: If international_activation is whitelist, return true
        // 2: If international_activation is greylist and merchant is kyc verifed, return true
        //
        if (($featureValue === ActivationFlow::WHITELIST) or
            (($merchantDetails->getActivationStatus() === Detail\Status::ACTIVATED) and
            ($featureValue === ActivationFlow::GREYLIST)))
        {
            return true;
        }

        return false;
    }

    /**
     * Auto Enable International for merchant if
     *  1) Merchant belongs to Razorpay org Or
     *  2) Merchant is not in unregistered business onBoarding flow
     *
     * @param Entity        $merchant
     *
     * @param Detail\Entity $merchantDetails
     *
     * @return bool
     */
    public function autoEnableInternational(Entity $merchant, Detail\Entity $merchantDetails): bool
    {
        $isRazorpayOrg = ($merchant->getOrgId() === Org::RAZORPAY_ORG_ID);

        if (($isRazorpayOrg === false) or
            ($this->isUnRegisteredOnBoardingEnabled($merchant, $merchantDetails->isUnregisteredBusiness()) === true))
        {
            return false;
        }

        //
        // SubMerchant batch upload flow defines a way to disable the auto-enabling international feature
        // If the submerchant is getting activated using a submerchant batch and if the submerchant
        // batch parameters define to not auto-enable international attribute, false will be returned.
        //
        $isBatchFlow = (app('basicauth')->isBatchFlow() === true);

        if ($isBatchFlow === true)
        {
            $batchContext = app('basicauth')->getBatchContext();

            $batchName               = $batchContext['type'] ?? null;
            $autoEnableInternational = $batchContext['data'][Merchant\Entity::AUTO_ENABLE_INTERNATIONAL] ?? false;

            return ($batchName === Batch\Type::SUB_MERCHANT)
                   and ($autoEnableInternational === true);
        }

        return true;
    }

    /*
     * Returns the partner merchant from the partner app entity passed as an argument
     *
     * @param $partnerApp
     *
     * @return null
     */
    public function getPartnerFromApp($partnerApp)
    {
        $partnerId = $partnerApp->getMerchantId();

        $partner = $this->repo->merchant->find($partnerId);

        return $partner;
    }

    /**
     * Toogles international flag on the merchant if appilicable
     *
     * @param Entity    $merchant
     * @param bool      $toggleValue
     *
     * @return Entity
     */
    public function toggleInternational(Entity $merchant, bool $toggleValue): Entity
    {
        if ($toggleValue === true)
        {
            $this->internationalEnable($merchant);
        }
        else
        {
            $this->internationalDisable($merchant);
        }

        return $merchant;
    }

    public function getPartnerBankAccountIdsForSubmerchants(array $merchantIds): array
    {
        $merchants = $this->repo->merchant->getAllPartnerBankAccountsForSubmerchants($merchantIds);

        $submerchants = [];

        // Attributes
        $partnerBankAccountId         = 'partner_bank_account_id';
        $partnerConfigOriginId        = 'partner_config_origin_id';
        $partnerConfigSettleToPartner = 'partner_config_settle_to_partner';

        foreach ($merchants as $merchant)
        {
            $merchantId = $merchant->getId();

            if (array_key_exists($merchantId, $submerchants) === true)
            {
                if (empty($merchant->getAttribute($partnerConfigOriginId)) === false)
                {
                    // App config was applied to the map but now we have a submerchant config, so unset the app config
                    unset($submerchants[$merchantId]);
                }
                else
                {
                    // Submerchant config has been applied to the map. Do nothing for the app config
                    continue;
                }
            }

            $submerchants[$merchantId] = [
                $partnerBankAccountId         => $merchant->getAttribute($partnerBankAccountId),
                $partnerConfigSettleToPartner => (bool) $merchant->getAttribute($partnerConfigSettleToPartner),
            ];
        }

        $merchantIdToPartnerBankAccountMap = [];

        foreach ($submerchants as $merchantId => $merchantObj)
        {
            if ($merchantObj[$partnerConfigSettleToPartner] === true)
            {
                $merchantIdToPartnerBankAccountMap[$merchantId] = $merchantObj[$partnerBankAccountId];
            }
        }

        $this->trace->info(TraceCode::PARTNER_BANK_ACCOUNT_MAP, $merchantIdToPartnerBankAccountMap);

        return $merchantIdToPartnerBankAccountMap;
    }

    protected function internationalEnable(Entity $merchant)
    {
        if ($merchant->isInternational() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_INTERNATIONAL);
        }

        $merchantDetails = $merchant->merchantDetail;

        // Since website is not synced between merchant and merchant_detail,
        // thereofre checking for both
        if ((empty($merchant->getWebsite()) === true) and
            (empty($merchantDetails->getWebsite()) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_WEBSITE_NOT_SET);
        }

        $this->activateInternationalIfApplicable($merchant, $merchantDetails);

        $this->repo->saveOrFail($merchant);
    }

    protected function internationalDisable(Entity $merchant)
    {
        if ($merchant->isInternational() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED);
        }

        $merchant->disableInternational();

        $merchant->setCurrencyConversion(null);

        $this->repo->saveOrFail($merchant);
    }

    /**
     *  Restricted Merchant will have all its users associated to only itself.
     *  If Restricted cannot be applied, will return userIds which are associated
     *  with more than one merchant.
     *
     * @param Entity $merchant
     *
     * @return array
     */
    protected function getMerchantUsersWithMultipleMerchants(Entity $merchant): array
    {
        $users = $merchant->users()
                          ->get();

        $userAssociatedWithMoreMerchants = [];

        foreach ($users as $user)
        {
            $merchantIds = $user->merchants()->distinct()->get()->pluck(Entity::ID)->toArray();

            if (count($merchantIds) !== 1)
            {
                $userAssociatedWithMoreMerchants[] = $user->getId();
            }
        }

        return $userAssociatedWithMoreMerchants;
    }

    /**
     * This method does remove/apply restricted settings.
     *
     * @param Entity $merchant
     * @param string $action
     *
     * @return array
     */
    public function applyRestrictedSettings(Entity $merchant, string $action): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_RESTRICTED_SETTINGS,
            [
                Entity::MERCHANT_ID => $merchant->getId(),
                Entity::ACTION      => $action,
                'admin_id'          => $this->app['basicauth']->getAdmin()->getId(),
            ]);

        if ($action === Constants::REMOVE)
        {
            return $this->addRestrictedSettingsToMerchant($merchant, false);
        }
        else
        {
            $userIds = $this->getMerchantUsersWithMultipleMerchants($merchant);

            // Will add restricted settings only if all users
            // are associated with one merchant itself.

            if (count($userIds) === 0)
            {
                return $this->addRestrictedSettingsToMerchant($merchant, true);
            }

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_RESTRICTED_SETTINGS_NOT_APPLIED,
                                                    null,
                                                    [
                                                        'count_users_with_multiple_merchants' => count($userIds),
                                                        'users_with_mutiple_merchants'        => $userIds,
                                                    ]);
        }
    }

    public function addMerchantEmailToMailingList($merchant)
    {
        $transactionReportEmails = $merchant->getTransactionReportEmail();

        $transactionReportEmails = array_merge($transactionReportEmails, [$merchant->getEmail()]);

        $merchantEmailList = [] ;

        foreach ($transactionReportEmails as $transactionReportEmail)
        {
            if (isset($merchantEmailList[$transactionReportEmail]) === false)
            {
                $merchantEmailList[$transactionReportEmail] = [
                    'address' => $transactionReportEmail,
                    'name'    => $merchant->getName()
                ];
            }
        }

        $merchantEmailList = array_values($merchantEmailList);

        MailingListUpdate::dispatch(
            $this->mode,
            $merchantEmailList);
    }

    public function removeMerchantEmailToMailingList($merchant, $i = 0)
    {
        $transactionReportEmails = $merchant->getTransactionReportEmail();

        $transactionReportEmails = array_merge($transactionReportEmails, [$merchant->getEmail()]);

        $transactionReportEmails = array_unique($transactionReportEmails);

        foreach ($transactionReportEmails as $transactionReportEmail)
        {
            MailingListUpdate::dispatch(
                                    $this->mode,
                                    [$transactionReportEmail],
                                    true)
                            ->delay($i % 901);
        }
    }

    protected function addRestrictedSettingsToMerchant(Entity $merchant, bool $action)
    {
        $merchant->setAttribute(Entity::RESTRICTED, $action);

        $this->repo->saveOrFail($merchant);

        return [
            Entity::MERCHANT_ID => $merchant->getId(),
            Entity::RESTRICTED  => $merchant->getRestricted(),
        ];
    }


    /**
     * Checks if UNREGISTERED_ON_BOARDING razorx experiment enabled for merchant id
     *
     * @param string $merchantId
     * @param null   $mode
     *
     * @return bool
     */
    protected function isUnregisteredOnBoardingRazorxEnabled(string $merchantId, $mode = null): bool
    {
        $mode = $mode ?? $this->mode;

        $status = $this->app['razorx']->getTreatment($merchantId, Merchant\RazorxTreatment::NON_REGISTERED_ONBOARDING, $mode);

        return (strtolower($status) === 'on');
    }

    /**
     * Enable unregistered on-Boarding only for
     *
     * 1. If merchant belongs to Razorpay org Id
     * 2. if operation is being performed from banking dashboard
     * 3. if UNREGISTERED_ON_BOARDING razorx experiment is enabled for mid or merchant is already activated
     *
     *
     * @param Entity $merchant
     * @param bool   $isUnregisteredBusiness
     * @param null   $mode
     *
     * @return bool
     */
    public function isUnRegisteredOnBoardingEnabled(Entity $merchant, bool $isUnregisteredBusiness, $mode = null): bool
    {
        $isRazorpayOrgId = ($merchant->getOrgId() === Org::RAZORPAY_ORG_ID);

        return (($isRazorpayOrgId === true) and
                ($this->app['basicauth']->getRequestOriginProduct() === Product::PRIMARY) and
                ($isUnregisteredBusiness === true) and
                (($merchant->isActivated()) or
                 ($this->isUnregisteredOnBoardingRazorxEnabled($merchant->getId(), $mode))));
    }

    public function getAllMerchantsMappedToMerchantLegalEntity(Merchant\Entity $merchant): Base\PublicCollection
    {
        $legalEntityId = $merchant->getLegalEntityId();

        if (empty($legalEntityId) === false)
        {
            $legalEntity = $this->repo->legal_entity->findOrFailPublic($legalEntityId);

            return $legalEntity->merchants;
        }

        return new Base\PublicCollection;
    }
}

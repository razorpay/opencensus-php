<?php

namespace RZP\Models\Merchant;

use Mail;
use Config;
use ApiResponse;
use Carbon\Carbon;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Batch;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Jobs\MerchantSync;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Admin\Action;
use RZP\Models\Admin\AdminLead;
use RZP\Models\Merchant\Detail;
use RZP\Models\Admin\Permission;
use RZP\Exception\LogicException;
use RZP\Models\Settings\Accessor;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Mail\Payout\Payout as PayoutMail;
use RZP\Models\Schedule\Task as ScheduleTask;
use Razorpay\OAuth\Exception\DBQueryException;
use RZP\Models\Merchant\Request as MerchantRequest;

class Core extends Base\Core
{
    use Notify;

    // This is used in case for
    // IRCTC for sending payout
    // mails
    const MASTER_ID_MAPPING = [
        '8YPFnW5UOM91H7' => 'WMRAZOR00000',
    ];

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

        $merchant->org()->associate($org);

        $this->repo->saveOrFail($merchant);

        $this->addMerchantSupportingEntities($merchant);

        $this->syncHeimdallRelatedEntities($merchant, $input, true);

        // Updating the existing customer info and setting activated to false
        $this->app['drip']->sendDripMerchantInfo($merchant, Merchant\Action::CREATED);

        $this->app['eventManager']->trackEvents($merchant, Merchant\Action::CREATED, $merchant->toArrayEvent());

        return $merchant;
    }

    /**
     * @param array     $input
     * @param Entity    $aggregatorMerchant
     * @param bool      $linkedAccount
     * @param bool      $accountEntity
     *
     * @return Entity|Account\Entity
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

        $subMerchant->setAuditAction(Action::CREATE_SUBMERCHANT);

        $subMerchant->setPricingPlan($aggregatorMerchant->getPricingPlanId());

        // The parent Id has to be linked only when it's a marketplace
        // If both market place and referral are present when creating a referral account we should not link parentId.
        if ($aggregatorMerchant->isMarketplace() === true and $linkedAccount === true)
        {
            // Use Startup Plan as the default for linked accounts
            // where transfer method pricing is 0
            $subMerchant->setPricingPlan(Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID);

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

        $merchant->edit($input);

        $plan = $this->repo->pricing->getMerchantPricingPlan($merchant);

        (new Methods\Core)->validateInternationalPricingForMerchant($merchant, $plan);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $input)
        {
            // This is used to sync fields transaction_report_email and website in merchant and merchantDetail
            (new Detail\Core)->syncToMerchantDetailFields($merchant, $input);

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
        $parterType = $submissions[Entity::PARTNER_TYPE];

        $data[Entity::PARTNER_TYPE] = $parterType;

        $this->trace->info(
            TraceCode::PARTNER_REQUEST_SUBMITTED,
            [
                Entity::PARTNER_TYPE       => $parterType,
                MerchantRequest\Entity::ID => $request->getId(),
            ]);

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
     * @param Entity $merchant
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function getPartnerApp(Entity $merchant)
    {
        // For pure platforms, no internal partner app is created
        (new Validator)->validateIsNonPurePlatformPartner($merchant);

        try
        {
            $app = (new OAuthApp\Repository)->findActivePartnerApplicationByMerchantId($merchant->getId());
        }
        catch (DBQueryException $ex)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_APP_NOT_FOUND,
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        return $app;
    }

    /**
     * @param Request\Entity $merchantRequest
     *
     * @return Entity
     * @throws LogicException
     */
    public function markAsPartner(MerchantRequest\Entity $merchantRequest): Entity
    {
        $submissions = $this->getPartnerSubmissions($merchantRequest);

        if (empty($submissions[Entity::PARTNER_TYPE]) === true)
        {
            throw new LogicException(
                PublicErrorDescription::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
                ErrorCode::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
                $submissions);
        }

        $partnerType = $submissions[Entity::PARTNER_TYPE];

        $merchant = $merchantRequest->merchant;

        $validator = new Validator;

        $validator->validateIsNotLinkedAccount($merchant);

        $validator->validateIfAlreadyPartner($merchant);

        $this->repo->transactionOnLiveAndTest(function() use ($merchant, $partnerType)
        {
            $merchant->setPartnerType($partnerType);

            $this->repo->saveOrFail($merchant);

            $this->createPartnerApp($merchant);
        });

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
            $this->deletePartnerApp($merchant);

            $merchant->setPartnerType();

            $this->repo->saveOrFail($merchant);

        });

        return $merchant;
    }

    /**
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @return array
     * @throws BadRequestException
     */
    public function createPartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant)
    {
        $merchantService = new Service;

        $accessMap = $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant, $merchantService)
        {
            $partnerApp = $this->getPartnerApp($partner);

            $partnerAppId = $partnerApp->getId();

            $submerchantId = $submerchant->getId();

            // Maintained for backward compatibility
            $merchantService->addSubMerchantReferral($partner, $submerchant);

            if ($partner->hasSwitchDashboardAccess() === true)
            {
                // Attaches partners's user to the submerchant account as an owner
                $merchantService->attachSubMerchantOwner($partner->primaryOwner()->getId(), $submerchant);
            }

            // If the mapping already exists, the existing entity is returned
            $accessMap = (new AccessMap\Service)->mapOAuthApplication(
                $submerchantId,
                [
                    AccessMap\Entity::APPLICATION_ID => $partnerAppId,
                ]);

            return $accessMap;
        });

        return $accessMap;
    }

    /**
     * @param Entity $partner
     * @param Entity $submerchant
     *
     * @return array
     * @throws BadRequestException
     */
    public function deletePartnerSubmerchantAccessMap(Entity $partner, Entity $submerchant)
    {
        $merchantService = new Service;

        $response = $this->repo->transactionOnLiveAndTest(function() use ($partner, $submerchant, $merchantService)
        {
            $partnerApp = $this->getPartnerApp($partner);

            $submerchantId = $submerchant->getId();

            $partnerAppId = $partnerApp->getId();

            $response = (new AccessMap\Service)->deleteMapOAuthApplication($submerchantId, $partnerAppId);

            $this->removeSubMerchantReferralTag($submerchant, $partner->getId());

            return $response;
        });

        return $response;
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
        $website = $merchant->getWebsite() ?? 'https://www.razorpay.com';

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
     * @return array
     */
    public function deletePartnerApp(Entity $merchant)
    {
        if ($merchant->isPurePlatformPartner() === true)
        {
            // A dummy application for pure platforms does not exist
            return;
        }

        $app = $this->getPartnerApp($merchant);

        $app = app('authservice')->deleteApplication($app->getId(), $merchant->getId());

        return $app;
    }

    protected function removeSubMerchantReferralTag(Entity $merchant, string $partnerId): array
    {
        $tag = 'ref-' . $partnerId;

        $tags = (new Merchant\Service)->deleteTag($merchant->getPublicId(), $tag);

        return $tags;
    }

}

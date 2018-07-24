<?php

namespace RZP\Models\Merchant;

use DB;
use Mail;
use Config;
use Request;
use Carbon\Carbon;
use Razorpay\OAuth\Token as OAuthToken;
use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApplication;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Offer;
use RZP\Models\Coupon;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Schedule;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Webhook;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Mail\Merchant\CreateSubMerchantPartner;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;

class Service extends Base\Service
{
    use Notify;

    const COUPON_RESPONSE = 'apply_coupon';
    const OAUTH_MAIL      = 'oauth_mail';

    /**
     * Creates a merchant and saves in database
     *
     * @param  array $input
     * @return array
     */
    public function create(array $input): array
    {
        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (empty($input[Entity::ORG_ID]) === true)
        {
            // If the organization ID is not present,
            // assume the organization is razorpay
            $input[Entity::ORG_ID] = Org\Entity::RAZORPAY_ORG_ID;

            $this->trace->info(
                TraceCode::MERCHANT_ORG_NOT_GIVEN,
                [
                    'merchant_email' => $input[Entity::EMAIL],
                    'merchant_name'  => $input[Entity::NAME],
                ]);
        }
        else
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $merchant = (new Merchant\Core)->create($input);

        $merchantData = $this->saveMerchantAndApplyCoupon($merchant, $input);

        return $merchantData;
    }

    public function createSubMerchant(array $input): array
    {
        $merchant = $this->merchant;

        $isLinkedAccount = (bool)($input['account'] ?? false);

        $isPartner = $merchant->isPartner();

        $hasAggregatorFeature = $merchant->hasAggregatorFeature();

        //
        // Cannot create sub-merchant if all following conditions are met:
        // 1. Not a linked account
        // 2. Is neither a partner nor has an aggregator feature (for BC we allow the feature)
        // 3. Is a partner of type pure-platform
        //
        if ($isLinkedAccount === false)
        {
            if ($isPartner === false)
            {
                if ($hasAggregatorFeature === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
                }
            }
            else
            {
                if ($merchant->isPurePlatformPartner() === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT);
                }
            }
        }

        return $this->createSubMerchantAndSetRelations($merchant, $isLinkedAccount, $input);
    }

    /**
     * We create this mapping only in case of non-linked accounts if
     * 1. Is partner of type fully-managed
     * 2. Is partner of type aggregator and has exception given for optional sub-merchant email
     * (For now all aggregators are allowed for backward compatibility till future phases)
     * 3. Is not a partner but has aggregator feature (for backward compatibility)
     *
     * @param string $ownerId
     * @param Entity $subMerchant
     * @param Entity $aggregatorMerchant
     */
    protected function attachSubMerchantOwnerIfApplicable(
        string $ownerId,
        Entity $subMerchant,
        Entity $aggregatorMerchant)
    {
        $isPartner = $aggregatorMerchant->isPartner();

        $hasAggregatorFeature = $aggregatorMerchant->hasAggregatorFeature();

        $isOptionalEmailAllowed = $aggregatorMerchant->isOptionalEmailAllowedAggregator();

        $subMerchantEmailIsSame = ($aggregatorMerchant->getEmail() === $subMerchant->getEmail());

        if (($aggregatorMerchant->isFullyManagedPartner() === true) or
            //Remove the following line later as aggregator isn't supposed to
            //have dashboard access eventually. This is for BC.
            ($aggregatorMerchant->isAggregatorPartner() === true) or
            (($isOptionalEmailAllowed === true) and ($subMerchantEmailIsSame === true)) or
            (($isPartner === false) and ($hasAggregatorFeature === true)))
        {
            (new Core)->attachSubMerchantOwner($ownerId, $subMerchant);
        }
    }

    public function saveMerchantAndApplyCoupon(Entity $merchant, array $input)
    {
        $this->repo->saveOrFail($merchant);

        $merchantData = $merchant->toArrayPublic();

        $couponResponse = $this->applyCouponOnSignUp($input, $merchant);

        $merchantData[self::COUPON_RESPONSE] = $couponResponse;

        return $merchantData;
    }

    protected function applyCouponOnSignUp(array $input, Entity $merchant)
    {
        $result = [];

        if (isset($input[Entity::COUPON_CODE]) === true)
        {
            $couponInput = [
                Coupon\Entity::CODE        => $input[Entity::COUPON_CODE],
                Coupon\Entity::MERCHANT_ID => $merchant->getId()
            ];

            try
            {
                $result = (new Coupon\Service)->apply($couponInput);
            }
            catch (\Throwable $e)
            {
                $result = ['message' => $e->getMessage()];
            }
        }

        return $result;
    }

    public function edit(string $id, array $input): array
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (empty($input[Entity::GROUPS]) === false)
        {
            Group\Entity::verifyIdAndStripSignMultiple($input[Entity::GROUPS]);
        }

        if (empty($input[Entity::ADMINS]) === false)
        {
            Admin\Entity::verifyIdAndStripSignMultiple($input[Entity::ADMINS]);
        }

        if (isset($input[Entity::ORG_ID]) === true)
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $merchant = $this->repo->transactionOnLiveAndTest(function () use ($merchant, $input)
        {
            $merchant = (new Merchant\Core)->edit($merchant, $input);

            if (isset($input[Entity::FEE_BEARER]) === true)
            {
                $merchantId = $merchant->getId();

                // add feebearer tag if fee_bearer field is set to customer
                // else remove feebearer tag
                if ($input[Entity::FEE_BEARER] === 'customer')
                {
                    $this->insertTag($merchantId, 'feebearer');
                }
                else
                {
                    $this->deleteTag($merchantId, 'feebearer');
                }
            }

            return $merchant;
        });

        return $merchant->toArrayPublic();
    }

    /**
     * Old flow: Sends a mail to the sub-merchant email telling them about
     * account creation. Adds aggregator in cc if the sub-merchant
     * email is different.
     *
     * Partners flow: Sends a mail to the partner informing about the account
     * addition. If the sub-merchant email is different then sends an email
     * to the user created with this email with a password reset email.
     *
     * @param Entity      $subMerchant
     * @param Entity      $aggregator
     * @param User\Entity $user
     * @param bool        $createdNewUser
     */
    protected function sendSubMerchantCreationMail(
        Entity $subMerchant,
        Entity $aggregator,
        User\Entity $user = null,
        bool $createdNewUser = false)
    {
        $isPartnerFlow = $aggregator->isPartner();

        $subMerchant = $subMerchant->toArray();

        $aggregator = $aggregator->toArray();

        if ($isPartnerFlow === true)
        {
            $this->sendNewSubMerchantCreationMails($subMerchant, $aggregator, $user, $createdNewUser);
        }
        else
        {
            $createSubMerchantMail = new CreateSubMerchantMail($subMerchant, $aggregator);

            Mail::queue($createSubMerchantMail);
        }
    }

    /**
     * @param array       $subMerchant
     * @param array       $aggregator
     * @param User\Entity $user
     * @param bool        $createdNewUser
     */
    protected function sendNewSubMerchantCreationMails(
        array $subMerchant,
        array $aggregator,
        User\Entity $user = null,
        bool $createdNewUser = false)
    {
        // This mail goes to the partner who has added the sub-merchant
        $createSubMerchantPartnerMail = new CreateSubMerchantPartner($subMerchant, $aggregator);

        Mail::queue($createSubMerchantPartnerMail);

        if ($subMerchant['email'] === $aggregator['email'])
        {
            return;
        }

        $orgId = $this->auth->getOrgId();

        $org = $this->repo->org->findByPublicId($orgId)->toArrayPublic();

        $org['hostname'] = $this->auth->getOrgHostName();

        $mailUserData = $createdNewUser ? $user : null;

        // If user was just created then we need to pass the details to the mailer for sending
        // reset password link.
        $createSubMerchantAffiliateMail =
            new CreateSubMerchantAffiliate($subMerchant, $aggregator, $org, $mailUserData);

        Mail::queue($createSubMerchantAffiliateMail);
    }

    public function editEmail($id, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $orignalEmail = $merchant->getEmail();

        $merchant = (new Merchant\Core)->editEmail($merchant, $input);

        $newEmail = $merchant->getEmail();

        (new Merchant\Core)->changeMerchantUsersEmail($merchant, $orignalEmail, $newEmail);

        return $merchant->toArrayPublic();
    }

    public function editConfig(array $input): array
    {
        // Adds uploaded logo's url to the input.
        $this->uploadLogoIfFound($input);

        (new Merchant\Core)->editConfig($this->merchant, $input);

        return $this->merchant->toArrayConfig();
    }

    public function deleteMerchantLogo(): array
    {
        $this->merchant->setLogoUrl(null);

        $this->repo->saveOrFail($this->merchant);

        return $this->merchant->toArrayConfig();
    }

    protected function uploadLogoIfFound(&$input)
    {
        // if ($input->hasFile('logo') and $input['logo']->isValid())
        if (isset($input['logo']))
        {
            // Store the logos in AWS
            $logoUrl = (new Merchant\Logo)->setUpMerchantLogo($input);

            $input['logo_url'] = $logoUrl;
            unset($input['logo']);
        }
    }

    // This is on internal auth
    public function fetch(string $id): array
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $id, ['methods', Entity::GROUPS, Entity::ADMINS]);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $merchants = $this->repo->merchant->fetch($input);

        return $merchants->toArrayPublic();
    }

    // This is on proxy auth
    public function fetchConfig(): array
    {
        $merchantId = $this->merchant->getId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId, Entity::CONFIG_LIST);

        return $merchant->toArray();
    }

    public function fetchBalance($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->getId();
        }

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        //
        // For non-activated merchants in live mode, simply return 0.
        // For these merchants, balance entity is not yet created so
        // we need to create the exception here.
        //
        if (($this->mode === Mode::LIVE) and
            ($merchant->isActivated() === false) and
            (Account::isNodalAccount($merchantId) === false))
        {
            $balance[Balance\Entity::ID]      = $merchantId;
            $balance[Balance\Entity::BALANCE] = 0;

            return $balance;
        }

        $balance = $this->repo->balance->getMerchantBalance($merchant);

        return $balance->toArray();
    }

    public function editAmountCredits($merchantId, $input)
    {
        (new Merchant\Validator)->validateInput('edit_credits', $input);

        $amountCredits = $input['credits'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $balance = $this->repo->balance->editMerchantAmountCredits($merchant, $amountCredits);

        return $balance->toArray();
    }

    public function assignPricingPlan($id, $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PRICING_PLAN_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $plan = $this->repo->pricing->getPricingPlanByIdOrFailPublic($input['pricing_plan_id']);

        // validate if this plan can be set for this merchant.
        // Refer: https://github.com/razorpay/api/issues/324

        $methods = $this->repo->methods->getMethodsForMerchant($merchant);

        (new Merchant\Methods\Core)->validatePricingPlanForMethods($merchant, $plan, $methods);

        $originalPricingPlan = null;

        if (empty($merchant->pricing) === false)
        {
            $originalPricingPlan = $merchant->pricing->getPlanName();
        }

        list($original, $dirty) = [
            // Current plan
            ['pricing_plan' => $originalPricingPlan],
            // New plan
            ['pricing_plan' => $plan->first()->getPlanName()],
        ];

        $this->app['workflow']
             ->setEntity($merchant->getEntity())
             ->handle($original, $dirty);

        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_PRICING, $input);

        return $plan->toArrayPublic();
    }

    public function assignSettlementSchedule($id, $input)
    {
        $this->trace->info(
            TraceCode::SCHEDULE_ASSIGN_REQUEST,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $input[ScheduleTask\Entity::TYPE] = ScheduleTask\Type::SETTLEMENT;

        $scheduleTask = (new ScheduleTask\Core)->createOrUpdate($merchant, $merchant, $input);

        return $scheduleTask->toArrayPublic();
    }

    public function migrateMerchantToSettlementSchedules($input)
    {
        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_INITIATED);

        if (isset($input['merchant_ids']))
        {
            $merchants = $this->repo->merchant->findMany($input['merchant_ids']);
        }
        else
        {
            $merchants = $this->repo->merchant->getFewMerchantsWithNoCorrespondingScheduleTasks();
        }

        $migrationSummary = [
            'migrated_ids_count' => 0,
            'failed_ids'         => [],
        ];

        foreach ($merchants as $merchant)
        {
            try
            {
                $defaultDelay = Entity::SETTLEMENT_SCHEDULE_DEFAULT_DELAY;

                $schedule = (new Schedule\Core)->getOrCreateDefaultSchedule($defaultDelay);

                $input = [
                    ScheduleTask\Entity::METHOD      => null,
                    ScheduleTask\Entity::TYPE        => ScheduleTask\Type::SETTLEMENT,
                    ScheduleTask\Entity::SCHEDULE_ID => $schedule->getId()
                ];

                (new ScheduleTask\Core)->createOrUpdate($merchant, $merchant, $input);

                $migrationSummary['migrated_ids_count'] += 1;
            }
            catch (\Exception $ex)
            {
                $merchantId = $merchant->getId();

                $this->trace->info(
                    TraceCode::SCHEDULE_MIGRATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'schedule_id' => $schedule->getId(),
                        'error'       => $ex->getMessage(),
                    ]);

                $migrationSummary['failed_ids'][] = $merchantId;
            }
        }

        $migrationSummary['fail_count'] = count($migrationSummary['failed_ids']);

        $this->trace->info(TraceCode::SCHEDULE_MIGRATION_COMPLETE, $migrationSummary);

        return $migrationSummary;
    }

    public function getPricingPlan($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = $this->repo->pricing->getPricingPlanById($pricingPlanId);

        return $plan->toArrayPublic();
    }

    public function activate($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_ACTIVATE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $act = new Activate($this->app);

        $act->activate($merchant);

        return $merchant->toArrayPublic();
    }

    public function sendActivationEmail(array $input)
    {
        $act = new Activate($this->app);

        $response = [];

        foreach ($input['ids'] as $merchantId)
        {
            try
            {
                $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                if ($merchant->isActivated())
                {
                    $act->sendActivationEmail($merchant);

                    $response[$merchantId] = 'Queued merchant activation email';
                }
                else
                {
                    $response[$merchantId] = 'Merchant is not activated';
                }
            }
            catch (\Exception $e)
            {
                $response[$merchantId] = $e->getMessage();
            }
        }

        return $response;
    }

    public function liveEnable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_ENABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }

        if ($merchant->isSuspended() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveEnable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'enable');

        return $merchant->toArrayPublic();
    }

    public function liveDisable($id)
    {
        $this->trace->info(
            TraceCode::MERCHANT_LIVE_DISABLE_REQUEST,
            [
                'merchant_id' => $id,
            ]);
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }

        $oldMerchant = clone $merchant;

        $merchant->liveDisable();

        // Triggering
        $workflow = $this->app['workflow']
                         ->setEntity($merchant->getEntity())
                         ->handle($oldMerchant, $merchant);

        $this->repo->saveOrFail($merchant);

        $this->logActionToSlack($merchant, 'disable');

        return $merchant->toArrayPublic();
    }

    public function action($id, array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_EDIT_ACTION,
            [
                'merchant_id' => $id,
                'input'       => $input,
            ]);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->action($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function addBankAccount($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        // Using Request::input() since we do not want the file as input to log
        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, Request::input());

        return $ba->toArray();
    }

    /**
     * This function returns if there any open workflow actions associated with the current bank account entity of a
     * merchant. @todo: Replace this with a more generic approach based on primary entity
     *
     * @param $id
     *
     * @return bool
     */
    public function getBankAccountChangeStatus($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $oldBankAccount = $this->repo->bank_account->getBankAccount($merchant);

        if (empty($oldBankAccount) === true)
        {
            return false;
        }

        $actions = (new \RZP\Models\Workflow\Action\Core)->fetchOpenActionOnEntityOperation(
            $oldBankAccount->getId(), $oldBankAccount->getEntity(), Permission::EDIT_MERCHANT_BANK_DETAIL);

        $actions = $actions->toArray();

        // If there are any action in progress
        if (empty($actions) === false)
        {
            return true;
        }

        return false;

    }

    public function getBankAccount($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $ba = $this->repo->bank_account->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArray();
    }

    public function getOwnBankAccount()
    {
        $ba = $this->repo->bank_account->getBankAccount($this->merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArrayPublic();
    }

    public function generateTestBankAccounts()
    {
        $merchants = $this->repo->merchant->fetchMerchantWhereTestBankIsNull();
        $fetched   = $merchants->count();

        $core = new BankAccount\Core;

        $count = 0;

        foreach ($merchants as $merc)
        {
            $core->createTestBankAccount($merc);
            $count++;
        }

        return ['fetched' => $fetched, 'processed' => $count];
    }

    public function getBanks($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $banks = (new Methods\Core)->getEnabledAndDisabledBanks($merchant);

        return $banks;
    }

    public function getEnabledBanks()
    {
        $methods = (new Methods\Core)->getEnabledAndDisabledBanks($this->merchant);

        return $methods['enabled'];
    }

    public function setPaymentBanks($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $enabledDisabledBanks = (new Merchant\Methods\Core)->setPaymentBanksForMerchant(
            $merchant, $input);

        $this->logActionToSlack($merchant, SlackActions::ASSIGN_BANKS);

        return $enabledDisabledBanks;
    }

    public function getFeeBearer()
    {
        $feeBearer = $this->merchant->isFeeBearerCustomer();

        return $feeBearer;
    }

    public function getPaymentMethods()
    {
        $formattedMethods = (new Methods\Core)->getFormattedMethods($this->merchant);

        // Licious has dependency on this field in their android app
        if ($this->merchant->getId() === '5yZ76HWrvL9g2l')
        {
            $formattedMethods['http_status_code'] = 200;
        }

        return $formattedMethods;
    }

    public function setPaymentMethods($merchantId, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return (new Merchant\Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function getMerchantWebhooks($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $webhooks = (new Webhook\Core)->getWebhooks($merchant);

        return $webhooks->toArray();
    }

    public function createWebhook($input)
    {
        $webhook = (new Webhook\Core)->createWebhook($this->merchant, $input);

        return $webhook->toArrayPublic();
    }

    public function editWebhook($webhookId, $input)
    {
        $this->trace->info(
            TraceCode::WEBHOOK_EDIT,
            [
                'webhook_id' => $webhookId,
                'input'      => $input,
            ]);

        $webhook = (new Webhook\Core)->editWebhook($this->merchant, $webhookId, $input);

        return $webhook->toArrayPublic();
    }

    public function fetchWebhookEvents()
    {
        $events = (new Webhook\Core)->fetchApplicableWebhookEvents($this->merchant);

        return $events;
    }

    public function getWebhook($id)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($id, $this->merchant);

        return $webhook->toArrayPublic();
    }

    public function getWebhooks(array $params)
    {
        $webhooks = $this->repo->webhook->fetch($params, $this->merchant->getId());

        return $webhooks->toArrayPublic();
    }

    public function createOAuthAppWebhook(string $appId, array $input): array
    {
        $input[Webhook\Entity::ENTITY_TYPE] = AccessMap\Entity::APPLICATION;

        $input[Webhook\Entity::ENTITY_ID] = $appId;

        $webhook = (new Webhook\Core)->createWebhook($this->merchant, $input);

        return $webhook->toArrayPublic();
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = (new BankAccount\Core)->updateBeneficiaryCodes();

        return $data;
    }

    /**
     * Send beneficiary registration request for ALL activated merchants
     *
     * @param array  $input
     * @param string $channel
     *
     * @return array
     */
    public function getMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->register($input, $channel);

        return $response;
    }

    public function getCheckoutPreferences($input)
    {
        $merchant = $this->merchant;

        $preferences = (new Checkout)->getPreferences($merchant, $this->mode, $input);

        return $preferences;
    }

    public function getGSTDetails(): array
    {
        return $this->merchant->merchantDetail->toArrayGST();
    }

    public function editGSTDetails(array $input): array
    {
        $merchantDetail = $this->merchant->merchantDetail;

        $merchantDetail->getValidator()->validateIsGSTEditable($input);

        $merchantDetail->edit($input);

        $this->repo->saveOrFail($merchantDetail);

        return $merchantDetail->toArrayGST();
    }

    /**
     *   Generate and Send the beneficary file to nodal account's bank
     *   if a new merchant has been activated since
     *   if (monday)  - 3 days
     *   else         - 1 day
     *
     * @param array $input
     * @param string $channel
     *
     * @return array
     */
    public function postMerchantBeneficiary(array $input, string $channel): array
    {
        $response = (new BankAccount\Beneficiary)->registerBetweenTimestamps($input, $channel);

        return $response;
    }

    /**
     * sends daily reports for all merchants that are currently live
     * Returns an array with the keys: `skipped`, and `sent`,
     * each containing the number of merchants in each category
     * @return array debug response
     */
    public function sendDailyReportForAllMerchants($input)
    {
        return (new DailyReport)->sendReportForAllMerchants($input);
    }

    public function notifyMerchantsHoliday($input)
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(300);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY);

        $response = (new Merchant\HolidayNotification)->send($input);

        $this->trace->info(TraceCode::MERCHANT_NOTIFY_HOLIDAY, $response);

        return $response;
    }

    public function updateMethodsForMultipleMerchants($input)
    {
        $this->trace->info(TraceCode::MERCHANT_METHODS_BULK_UPDATE);

        $merchantIds = $input['merchants'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $paymentMethod = $this->setPaymentMethods($merchantId, $input['methods']);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response['total']     = count($merchantIds);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        return $response;
    }

    public function updateMerchantsBulk(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_REQUEST,
            $input
        );

        if ((isset($input['attributes']) === true) and
            (isset($input['action']) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Both Action and Attributes should not be sent.');
        }

        (new Validator)->validateInput('updateMerchantsBulk', $input);

        $merchantIds = $input['merchant_ids'];

        unset($input['merchant_ids']);

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                if (isset($input['attributes']) === true)
                {
                    $this->edit($merchantId, $input['attributes']);
                }
                else
                {
                    $this->action($merchantId, $input);
                }

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateChannelForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('update_channel', $input);

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $channel = $input['channel'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                // update channel in merchant entity
                $this->edit($merchantId, ['channel' => $channel]);

                $data = (new Transaction\BulkUpdate)->updateMultipleTransactions($merchantId, $channel);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_CHANNEL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function updateBankAccountForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('updateBankAccount', $input);

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $bankAccount = $input['bank_account'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->addBankAccount($merchantId, $bankAccount);

                $successCount++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'total'     => count($merchantIds),
            'success'   => $successCount,
            'failed'    => $failedCount,
            'failedIds' => $failedIds,
        ];

        $this->trace->info(
            TraceCode::MERCHANT_BANK_ACCOUNT_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    public function getOffers(string $mid)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($mid);

        $offers = (new Offer\Core)->fetchOffers($merchant);

        return $offers->toArrayAdmin();
    }

    public function getMerchantFeatures()
    {
        return (new Feature\Service)->getFeaturesForEntity($this->merchant);
    }

    public function addOrRemoveMerchantFeatures(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_UPDATE,
            $input);

        $merchant = $this->merchant;

        $shouldSync = (bool) ($input[Feature\Entity::SHOULD_SYNC] ?? false);

        $merchant->validateInput('feature', $input);

        $featuresToAdd = $this->getFeatureNamesToAdd($input['features']);

        $featuresToRemove = $this->getFeatureNamesToRemove($input['features']);

        $this->addFeatures($featuresToAdd, $shouldSync);

        $this->removeFeatures($featuresToRemove, $shouldSync);

        $data = (new Feature\Service)->getFeaturesForEntity($merchant);

        return $data;
    }

    /**
     * used for fetching referred merchants of a particular merchant
     */
    public function fetchReferredMerchants()
    {
        $merchantId = $this->merchant->getId();

        return $this->repo->merchant->fetchReferredMerchants($merchantId);
    }

    /**
     * Bulk add or remove tags from a list of merchant_ids
     *
     * Input:
     *
     * name = Tag_Name
     * action = insert/delete
     * merchant_ids = [array, of, ids]
     *
     * @param array $input
     *
     * @return array
     */
    public function bulkTag(array $input): array
    {
        $this->trace->info(TraceCode::MERCHANT_TAGS_BULK_REQUEST, $input);

        (new Validator)->validateInput('bulk_tag', $input);

        $merchantIds = $input['merchant_ids'];
        // Action: 'insert' or 'delete'
        $action  = $input['action'];
        $tagName = $input['name'];

        $tagFunction = $action . 'Tag';

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                //
                // Calls either:
                // $this->insertTag() or $this->deleteTag()
                //
                $this->{$tagFunction}($merchantId, $tagName);
            }
            catch (\Throwable $t)
            {
                $this->trace->error(
                    TraceCode::MERCHANT_TAGS_BULK_EXCEPTION,
                    [
                        'merchant_id' => $merchantId,
                        'tag_name'    => $tagName
                    ]);

                $failedIds[] = $merchantId;
            }
        }

        return [
            'total_count'  => count($merchantIds),
            'failed_count' => count($failedIds),
            'failed_ids'   => $failedIds
        ];
    }

    /**
     * used for getting tags of the merchant
     * @param string $id
     */
    public function getTags($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

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
        return $this->core()->addTags($id, $input, $slackNotify);
    }

    /**
     * used for deleting a single tag of a merchant
     * @param string $id
     * @param string $tagName tag which has to be deleted
     */
    public function deleteTag($id, $tagName)
    {
        return $this->core()->deleteTag($id, $tagName);
    }

    /**
     * Tag a merchant for a single tag
     *
     * @param string $merchantId
     * @param string $tagName
     *
     * @return mixed
     */
    public function insertTag(string $merchantId, string $tagName)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant->tag($tagName);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
    }

    /**
     * This function is used for updating key access of a merchant
     * @param string $merchantId
     * @param array $input
     *
     * @return array
     */
    public function updateKeyAccess(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant = (new Core)->updateKeyAccess($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function markGratisTransactionPostpaid($input)
    {
        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_INPUT,
            $input);

        $merchantIds = $input['merchant_ids'];

        $from = $input['from'];

        $successIds = [];

        $failedIds = [];

        $merchantCore = (new Merchant\Core);

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $merchantCore->markGratisTransactionPostpaid($merchantId, $from);

                $successIds[] = $merchantId;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $failedIds[] = $merchantId;
            }
        }

        $response = [
            'success_ids' => $successIds,
            'failed_ids'  => $failedIds,
        ];

        $this->trace->info(
            TraceCode::GRATIS_TO_POSTPAID_RESPONSE,
            $response);

        return $response;
    }

    public function getUsers()
    {
        $merchantId = $this->merchant->getId();

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = (new Merchant\Core)->getUsers($merchant);

        return $users;
    }

    public function createBatches(string $merchantId, array $input): array
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $batches = (new Merchant\Core)->createBatches($merchant, $input);

        return $batches;
    }

    public function sendPayoutMailForMultipleMerchants(array $input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_REQUEST,
            $input
        );

        (new Validator)->validateInput('payout_mail', $input);

        $merchantsData = $input['content'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantsData as $merchantData)
        {
            try
            {
                $merchantId = $merchantData['merchant_id'];

                $email = $merchantData['email'] ?? null;

                $processed = $this->sendPayoutMail($merchantId, $email);

                if ($processed === true)
                {
                    $successCount++;
                }
                else
                {
                    $failedCount++;

                    $failedIds[] = $merchantId;
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failedCount++;

                $failedIds[] = $merchantId;
            }
        }

        $response['total']     = count($merchantsData);
        $response['success']   = $successCount;
        $response['failed']    = $failedCount;
        $response['failedIds'] = $failedIds;

        $this->trace->info(
            TraceCode::MERCHANT_PAYOUT_NOTIFICATION_RESPONSE,
            $response
        );

        return $response;
    }

    /**
     * Return all submerchants of the master merchant (for aggregator model only)
     *
     * 1. We do not want all the aggregator merchant to download the complete report
     *    so its behind aggregator_report feature
     * 2. We will have to write the logic to fetch all its submerchants based on tags
     * 3. Currently feature will be enabled only for e-Mitra, and merchants will be hard coded.
     *
     * @return array
     */
    public function getSubmerchants(): array
    {
        $merchantId = $this->merchant->getId();

        $merchants = $this->fetchReferredMerchants();

        return array_merge([$merchantId], $merchants->pluck('id')->toArray());
    }


    protected function sendPayoutMail(string $merchantId, string $email = null)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        list($from, $to) = $this->getTimestamps();

        $processed = $this->core()->sendPayoutMail($merchant, $from, $to, $email);

        return $processed;
    }

    private function getTimestamps()
    {
        $from = Carbon::today(Timezone::IST)->getTimestamp();
        $to   = Carbon::tomorrow(Timezone::IST)->getTimestamp() - 1;

        return [$from, $to];
    }

    /**
     * Gets the feature names to be added. A feature needs to be added to merchant
     * only if the value in input is equal to the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToAdd(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value === $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    /**
     * Gets the feature names to be removed. A feature needs to be removed from a
     * merchant only if the value in input is opposite of the default value of the feature
     *
     * @param  array $features
     *
     * @return array
     */
    private function getFeatureNamesToRemove(array $features): array
    {
        $featureNames = [];

        foreach ($features as $name => $value)
        {
            $value = (bool) $value;

            $defaultValue = Feature\Constants::getFeatureValue(
                    Feature\Constants::$visibleFeaturesMap[$name]['feature']);

            if ($value !== $defaultValue)
            {
                $featureNames[] = Feature\Constants::$visibleFeaturesMap[$name]['feature'];
            }
        }

        return $featureNames;
    }

    private function addFeatures(array $featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        if (count($featureNames) > 0)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE  => 'merchant',
                Feature\Entity::NAMES        => $featureNames,
                Feature\Entity::SHOULD_SYNC  => $shouldSync
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }
    }

    private function removeFeatures($featureNames, bool $shouldSync = false)
    {
        $merchant = $this->merchant;

        $entityId = $merchant->getId();

        foreach ($featureNames as $featureName)
        {
            $feature = $this->repo->feature->findByEntityTypeEntityIdAndNameOrFail(
                Feature\Constants::MERCHANT,
                $entityId,
                $featureName);

            if ($feature !== null)
            {
                $this->repo->feature->deleteAndSyncIfApplicableOrFail($feature, $shouldSync);
            }
        }
    }

    public function getMerchantDetails()
    {
        $data = [];

        /**
         * Merchant needs to be set using X-Razorpay-account header.
         * Setting Merchant in header validates admin access to
         * that merchant in admin access middleware.
         */
        if (empty($this->merchant) === false)
        {
            $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
                $this->merchant->getId(), ['methods', Entity::GROUPS, Entity::ADMINS]);

            // Merchant to array public
            $data = $merchant->toArrayPublic();

            // Merchant confirmed details
            $data['confirmed'] = $this->getMerchantConfirmed($merchant);

            // Fetch formatted merchant details.
            $data['merchant_details'] = (new Detail\Service)->getMerchantDetailsForAdmin();

            $data['tags'] = $merchant->tagNames();
        }

        return $data;
    }

    /**
     * Will provide if merchant is confirmed or not.
     *
     * @param  $merchant
     * @return bool
     */
    public function getMerchantConfirmed($merchant)
    {
        $parentId = $merchant->getParentId();

        // Market place sub accounts are confirmed.
        if (empty($parentId) === false)
        {
            return true;
        }
        else
        {
            $owner = $this->core()->getMerchantConfirmedOwner($merchant);

            // True if an confirmed owner is present.
            return !empty($owner);
        }
    }

    /**
     * Sends a mail to the merchant when an action is taken
     * on oauth access to his account
     *
     * @param array $input
     * @param string $type
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function sendOAuthMail(array $input, string $type): array
    {
        $this->trace->info(
            TraceCode::SEND_OAUTH_MAIL_REQUEST,
            [
                'type'  => $type,
                'input' => $input
            ]);

        (new Validator)->validateInput(self::OAUTH_MAIL, $input);

        $merchant = $this->repo->merchant->findOrFail($input[Entity::MERCHANT_ID]);

        $user     = $this->repo->user->findOrFail($input[User\Entity::USER_ID]);

        $client   = (new OAuthClient\Repository)->findOrFail($input[OAuthToken\Entity::CLIENT_ID]);

        $mailer   = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => $merchant->toArrayPublic(),
            'user'        => $user->toArrayPublic(),
            'application' => $client->application->toArrayPublic(),
        ];

        Mail::queue((new $mailer($data)));

        $this->sendCompetitorAppAuthorizedEmail($merchant, $client);

        return ['success' => true];
    }

    /**
     * Sends an email to support team informing them that a merchant has authorized
     * an application owned by a competitor like Juspay.
     *
     * @param Entity             $merchant
     * @param OAuthClient\Entity $client
     */
    protected function sendCompetitorAppAuthorizedEmail(
        Merchant\Entity $merchant,
        OAuthClient\Entity $client)
    {
        // Do not send the email if the application is not a competitor to us
        if (in_array($client->application->getId(), Feature\Type::S2S_APPLICATION_IDS) === false)
        {
            return;
        }

        $type = 'competitor_app_authorized';

        $mailer = $this->getOAuthMailerClassByType($type);

        $data = [
            'merchant'    => [
                Entity::ID            => $merchant->getId(),
                Entity::NAME          => $merchant->getName(),
                Entity::WEBSITE       => $merchant->getWebsite(),
                Entity::BILLING_LABEL => $merchant->getBillingLabel(),
            ],
            'application' => [
                OAuthApplication\Entity::NAME => $client->application->getName(),
            ]
        ];

        Mail::queue((new $mailer($data)));
    }

    /**
     * Returns OAuth mailer class name by event type. Also validates that
     * the same exists. If not throws a bad request exception.
     *
     * @param string $type
     *
     * @return string
     *
     * @throws Exception\BadRequestException
     */
    protected function getOAuthMailerClassByType(string $type): string
    {
        $mailer = 'RZP\\Mail\\OAuth\\' . studly_case($type);

        if (class_exists($mailer) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OAUTH_MAIL_TYPE,
                null,
                [
                    'type' => $type,
                ]);
        }

        return $mailer;
    }

    public function fetchAnalytics(array $input): array
    {
        $input = (new Core())->processMerchantAnalyticsQuery($this->merchant->getId(), $input);

        return $this->app['eventManager']->query($input);
    }

    /**
     * Creates submerchant User and associates with the submerchant as owner.
     *
     * @param string $merchantId
     * @param array  $input
     *
     * @return array
     */
    public function createSubMerchantUser($merchantId, array $input): array
    {
        /** @var Entity $subMerchant */
        $subMerchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $input['email'] = $subMerchant->getEmail();

        $input['merchant_id'] = $this->merchant->getId();

        $this->validateAggregatorSubMerchantRelation($subMerchant, $input['merchant_id']);

        (new Merchant\Validator)->validateInput('createSubMerchantUser', $input);

        unset($input['merchant_id']);

        $subMerchantUser = $this->createUserAndAttachMerchant($subMerchant, $input['email']);

        (new User\Service)->postResetPassword([User\Entity::EMAIL => $subMerchantUser[User\Entity::EMAIL]]);

        $subMerchantUser = $subMerchantUser->toArrayPublic();

        return $subMerchantUser;
    }

    /**
     * In case of partners, we created the sub-merchant's user in case the email
     * is different from partner's. There might be some rare cases where the user
     * with the provided email already exists, in which case we would want to attach
     * that user to the sub-merchant created as owner.
     *
     * @param  Entity $subMerchant
     * @param  string $email
     *
     * @return array
     */
    protected function createOrFetchUserAndAttachMerchant(Entity $subMerchant, string $email): array
    {
        $created = false;

        /** @var User\Entity $subMerchantUser */
        $subMerchantUser = $this->repo->user->getUserFromEmail($email);

        if (empty($subMerchantUser) === true)
        {
            $subMerchantUser = $this->createUserAndAttachMerchant($subMerchant, $email);

            $created = true;
        }
        else
        {
            $this->core()->attachSubMerchantOwner($subMerchantUser->getId(), $subMerchant);
        }

        return [$subMerchantUser, $created];
    }

    protected function createUserAndAttachMerchant(Entity $subMerchant, string $email): User\Entity
    {
        $userData = $this->formatUserCreationData($email, $subMerchant);

        $subMerchantUser = (new User\Core)->create($userData);

        $this->core()->attachSubMerchantOwner($subMerchantUser->getId(), $subMerchant);

        return $subMerchantUser;
    }

    public function formatUserCreationData(string $email, Merchant\Entity $subMerchant)
    {
        $dummyPass = bin2hex(random_bytes(20));

        return [
            User\Entity::NAME                  => $subMerchant->getName(),
            User\Entity::EMAIL                 => $email,
            User\Entity::PASSWORD              => $dummyPass,
            User\Entity::PASSWORD_CONFIRMATION => $dummyPass,
            User\Entity::CAPTCHA_DISABLE       => User\Validator::DISABLE_CAPTCHA_SECRET,
        ];
    }

    public function enableEmiMerchantSubvention(string $id, string $emiPlanId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $emiPlan = $this->repo->emi_plan->findOrFailPublic($emiPlanId);

        return $this->core()->enableEmiMerchantSubvention($merchant, $emiPlan, $input);
    }

    public function getDummyRazorX()
    {
        $variant = $this->app->razorx->getTreatment($this->merchant->getId(), 'dummy', $this->mode);

        return ['variant' => $variant];
    }

    protected function createSubMerchantAndSetRelations(Entity $merchant, bool $isLinkedAccount, array $input)
    {
        $ownerId = $input['user_id'];

        unset($input['user_id']);

        unset($input['account']);

        list($subMerchant, $newUser, $createdNew) = $this->repo->transactionOnLiveAndTest(function () use (
            $input,
            $merchant,
            $isLinkedAccount,
            $ownerId
        )
        {
            $merchantCore = new Merchant\Core;

            $subMerchant = $merchantCore->createSubMerchant($input, $merchant, $isLinkedAccount);

            $newUser = null;

            $createdNew = false;

            if ($isLinkedAccount === false)
            {
                $merchantCore->addSubMerchantReferral($merchant, $subMerchant);

                $this->attachSubMerchantOwnerIfApplicable($ownerId, $subMerchant, $merchant);

                // Partner and sub-merchant are connected via partner's app,
                // this connect is used for multiple validity checks, web-hooks, etc
                $this->mapSubMerchantPartnerAppIfApplicable($merchant, $subMerchant);

                list($newUser, $createdNew) = $this->createAdditionalUserOrFetchIfApplicable($subMerchant, $merchant);
            }

            $this->repo->saveOrFail($subMerchant);

            return [$subMerchant, $newUser, $createdNew];
        });

        // This goes out to the aggregator + sub-merchant(if separate email)
        // (skips if marketplace merchant)
        if (($merchant->isMarketplace() and $isLinkedAccount) === false)
        {
            $this->sendSubMerchantCreationMail($subMerchant, $merchant, $newUser, $createdNew);
        }

        return $subMerchant->toArrayPublic();
    }

    protected function createAdditionalUserOrFetchIfApplicable(Entity $subMerchant, Entity $merchant)
    {
        $subMerchantUser = null;
        $createdNew      = false;

        if (($merchant->isPartner() === true) and ($subMerchant->getEmail() !== $merchant->getEmail()))
        {
            list($subMerchantUser, $createdNew) =
                $this->createOrFetchUserAndAttachMerchant($subMerchant, $subMerchant->getEmail());
        }

        return [$subMerchantUser, $createdNew];
    }

    protected function mapSubMerchantPartnerAppIfApplicable(Entity $merchant, Entity $subMerchant)
    {
        if ($merchant->isPartner() === false)
        {
            return;
        }

        try
        {
            $app = (new Core)->getPartnerApp($merchant);
        }
        catch (\Exception $e)
        {
            throw new Exception\LogicException(
                'Server error app not found',
                ErrorCode::SERVER_ERROR_PARTNER_APP_NOT_FOUND);
        }

        $appId = $app->getId();

        (new AccessMap\Service)->mapOAuthApplication($subMerchant->getId(), ['application_id' => $appId]);
    }

    protected function validateAggregatorSubMerchantRelation(Entity $subMerchant, string $aggregatorMerchantId)
    {
        if ($subMerchant->isLinkedAccount() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $referrer = $subMerchant->getReferrer();

        $referrerNotEmptyAndSame = (empty($referrer) === false) and ($referrer === $aggregatorMerchantId);

        if ($referrerNotEmptyAndSame === true)
        {
            return;
        }

        /** @var Entity $aggregatorMerchant */
        $aggregatorMerchant = $this->repo->merchant->findOrFailPublic($aggregatorMerchantId);

        $isNonPurePlatformAggregator = $aggregatorMerchant->isNonPurePlatformPartner();

        $isPartnerMerchantMapped = $this->isPartnerMerchantMapped($subMerchant->getId(), $aggregatorMerchantId);

        if (($isNonPurePlatformAggregator === true) and ($isPartnerMerchantMapped === true))
        {
            return;
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
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
    public function isPartnerMerchantMapped(string $merchantId, string $partnerId): bool
    {
        $app = $this->getPartnerAppByMerchantId($partnerId);

        $mapping = (new AccessMap\Repository)
                        ->findMerchantAccessMapOnEntityId($merchantId, $app->getId(), AccessMap\Entity::APPLICATION);

        return (empty($mapping) === false);
    }

    /**
     * TODO: Check if the core function (getPartnerApp) is needed at all and remove it if not
     * @param  string $merchantId
     *
     * @return null|OAuthApplication\Entity
     */
    public function getPartnerAppByMerchantId(string $merchantId)
    {
        return (new OAuthApplication\Repository)->findActivePartnerApplicationByMerchantId($merchantId);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    public function createPartnerAccessMap(string $merchantId): array
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $accessMap = $this->core()->createPartnerSubmerchantAccessMap($partner, $submerchant);

        return $accessMap;
    }

    public function getSubmerchant(string $submerchantId): array
    {
        Account\Entity::verifyIdAndSilentlyStripSign($submerchantId);

        $partner = $this->fetchPartner();

        $submerchant = $this->core()->getSubmerchant($partner, $submerchantId);

        return $submerchant->toArrayPartner();
    }

    public function listSubmerchants(): array
    {
        $partner = $this->fetchPartner();

        $submerchants = $this->core()->listSubmerchants($partner);

        return $submerchants->toArrayPartner();
    }

    /**
     * @param string $merchantId
     */
    public function deletePartnerAccessMap(string $merchantId)
    {
        $partner = $this->fetchPartner();

        $submerchant = $this->fetchSubmerchant($merchantId);

        $this->core()->deletePartnerSubmerchantAccessMap($partner, $submerchant);
    }

    /**
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchPartner(): Entity
    {
        //
        // In the context of partners and submerchants -
        //
        // $merchant_id here corresponds to the submerchant's id. This is because the merchant_access_map entity maps
        // the submerchant id to the application entity (entity_type = application and entity_id = application_id),
        // which makes the submerchant as the primary entity in the merchant_access_map
        //
        $partner = $this->merchant;

        if ($partner === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                Entity::PARTNER_TYPE);
        }

        return $partner;
    }

    /**
     * @param string $submerchantId
     *
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function fetchSubmerchant(string $submerchantId): Entity
    {
        // The submerchant should belong to the same org as of the admin
        /** @var Entity $submerchant */
        $submerchant = $this->repo->merchant->findByIdAndOrgId($submerchantId, $this->auth->getOrgId());

        /** @var Admin\Entity $admin */
        $admin = $this->auth->getAdmin();

        // The current admin should have access to the submerchant before the mapping can be created/deleted
        $hasSubmerchantAccess = (new Group\Core)->groupCheck($admin, $submerchant);

        if ($hasSubmerchantAccess === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                Entity::MERCHANT_ID,
                [
                    'admin_id'       => $admin->getId(),
                    'partner_id'     => $submerchantId,
                    'submerchant_id' => $submerchant->getId(),
                ]);
        }

        return $submerchant;
    }
}

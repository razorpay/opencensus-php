<?php

namespace RZP\Models\Merchant;

use DB;
use Mail;
use Config;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Key;
use RZP\Models\Base;
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
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Settlement\Holidays;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Schedule\Task as ScheduleTask;
use RZP\Models\Merchant\SlackActions as SlackActions;
use RZP\Mail\Merchant\CreateSubMerchant as CreateSubMerchantMail;

class Service extends Base\Service
{
    use Notify;

    const COUPON_RESPONSE = 'apply_coupon';

    /**
     * Creates a merchant and saves in database
     *
     * @param  array            $input
     * @return Merchant\Entity
     */
    public function create(array $input)
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

    public function createSubMerchant(array $input)
    {
        $merchant = $this->merchant;

        $subMerchant = (new Merchant\Core)->createSubMerchant($input, $merchant);

        // This goes out to the aggregator
        // (skip if marketplace merchant)
        if ($merchant->isMarketplace() === false)
        {
            $this->sendSubMerchantCreationMail($subMerchant, $merchant);
        }

        $subMerchantData = $this->saveMerchantAndApplyCoupon($subMerchant, $input);

        return $subMerchantData;
    }

    protected function saveMerchantAndApplyCoupon(Entity $merchant, array $input)
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

    public function edit($id, array $input)
    {
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

        $merchant = (new Merchant\Core)->edit($merchant, $input);

        return $merchant->toArrayPublic();
    }

    /**
     * Sends a mail to the aggregator telling them about
     * sub-merchant account creation
     */
    protected function sendSubMerchantCreationMail($subMerchant, $aggregator)
    {
        $subMerchant = $subMerchant->toArray();

        $aggregator = $aggregator->toArray();

        $createSubMerchantMail = new CreateSubMerchantMail($subMerchant, $aggregator);

        Mail::queue($createSubMerchantMail);
    }

    public function editEmail($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->editEmail($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function editConfig(array $input)
    {
        // Adds uploaded logo's url to the input.
        $this->uploadLogoIfFound($input);

        (new Merchant\Core)->editConfig($this->merchant, $input);

        return $this->merchant->toArrayConfig();
    }

    public function deleteMerchantLogo()
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
    public function fetch($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $id, ['methods', Entity::GROUPS, Entity::ADMINS]);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $merchants = $this->repo->merchant->fetch($input);

        return $merchants->toArrayPublic();
    }

    // This is on proxy auth
    public function fetchConfig()
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
            $balance[Balance\Entity::ID] = $merchantId;
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

    public function createKey($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $keyData = (new Key\Core)->createFirstKey($merchant, $this->mode);

        if ($this->mode === MODE::LIVE)
        {
            $action = Merchant\Action::LIVE_KEYS_CREATED;
        }
        elseif ($this->mode === MODE::TEST)
        {
            $action = Merchant\Action::TEST_KEYS_CREATED;
        }

        if ($action !== null)
        {
            $this->app['eventManager']->trackEvents($merchant, $action, $merchant->toArrayEvent());
        }

        return $keyData;
    }

    public function updateKey($merchantId, $keyId, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        return (new Key\Core)->rollKey($merchantId, $keyId, $input, $this->mode);
    }

    public function fetchKeys($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $keys = $this->repo->key->getKeysForMerchant($merchantId);

        return $keys->toArrayPublic();
    }

    public function assignPricingPlan($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $plan = $this->repo->pricing->getPricingPlanByIdOrFailPublic(
                                            $input['pricing_plan_id']);

        // validate if this plan can be set for this merchant.
        // Refer: https://github.com/razorpay/api/issues/324

        (new Merchant\Methods\Core)->validatePricingPlanForMethods($merchant, $plan);

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
            catch(\Exception $ex)
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
            catch(\Exception $e)
            {
                $response[$merchantId] = $e->getMessage();
            }
        }

        return $response;
    }

    public function liveEnable($id)
    {
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
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->action($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function addBankAccount($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        $this->logActionToSlack($merchant, SlackActions::EDIT_BANK_DETAILS, $input);

        return $ba->toArray();
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
        $fetched = $merchants->count();

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

        // licious has dependency on this field in their android app
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
                'webhook_id'    => $webhookId,
                'input'         => $input,
            ]);

        $webhook = (new Webhook\Core)->editWebhook($this->merchant, $webhookId, $input);

        return $webhook->toArrayPublic();
    }

    public function getWebhook($id)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($id, $this->merchant);

        return $webhook->toArrayPublic();
    }

    public function getWebhooks()
    {
        $webhooks = $this->repo->webhook->fetch([], $this->merchant->getId());

        return $webhooks->toArrayPublic();
    }

    public function patchMerchantBeneficiaryCode()
    {
        $data = (new BankAccount\Core)->updateBeneficiaryCodes();

        return $data;
    }

    public function getMerchantBeneficiaryFile()
    {
        $file = (new BankAccount\BeneficiaryFile)->generate();

        return $file;
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
    */
    public function postMerchantBeneficiaryFile($input)
    {
        if (isset($input['on']))
        {
            $today = Carbon::createFromTimestamp($input['on'], Timezone::IST);
        }
        else
        {
            $today = Carbon::today(Timezone::IST);
        }

        if (Holidays::isWorkingDay($today) === false)
        {
            return ['message' => 'Today is a holiday! Happy holidays :)'];
        }

        $from = Holidays::getPreviousWorkingDay($today);

        $newBeneficiaryCount = $this->repo->bank_account->getCountOfBankAccountsCreatedBetween(
                                                        $from->getTimestamp(),
                                                        $today->getTimestamp());

        if ($newBeneficiaryCount > 0)
        {
            (new BankAccount\BeneficiaryFile)->generateBetweenTimestamps(
                                                        $from->getTimestamp(),
                                                        $today->getTimestamp());
        }

        $message = "Merchant Beneficiary file generated. Beneficiary added since".
                " last report is ". $newBeneficiaryCount;

        $this->slack->queue($message,[],['channel' => Config::get('slack.channels.settlements')]);

        //Log response in trace
        $this->trace->info(
            TraceCode::MERCHANT_BENEFICIARY_FILE_GENERATE,
            array('new_beneficiaries_added' => $newBeneficiaryCount));

        return $newBeneficiaryCount;
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

        $response['total'] = count($merchantIds);
        $response['success'] = $successCount;
        $response['failed'] = $failedCount;
        $response['failedIds'] = $failedIds;

        return $response;
    }

    public function updateHoldFundsForMultipleMerchants(array $input)
    {
        (new Validator)->validateInput('updateHoldFunds', $input);

        $this->trace->info(
            TraceCode::MERCHANT_HOLD_FUNDS_BULK_UPDATE_REQUEST,
            $input
        );

        $merchantIds = $input['merchant_ids'];

        $holdFunds = $input['hold_funds'];

        $successCount = $failedCount = 0;

        $failedIds = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $this->updateHoldFunds($merchantId, $holdFunds);

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
            TraceCode::MERCHANT_HOLD_FUNDS_BULK_UPDATE_RESPONSE,
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
        $merchant = $this->merchant;

        $data = (new Feature\Service)->getFeaturesForEntity($merchant);

        return $data;
    }

    public function addOrRemoveMerchantFeatures($input)
    {
        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_UPDATE,
            $input);

        $merchant = $this->merchant;

        $shouldSyncKey = EntityConstants::SHOULD_SYNC;

        $shouldSync = boolval($input[$shouldSyncKey] ?? false);

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
     * @param string $id
     * @param array $input which contains the tags of the merchant
     */
    public function addTags($id, $input)
    {
        (new Validator)->validateInput('addTags', $input);

        $this->trace->info(TraceCode::MERCHANT_TAGS_ADD, $input);

        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $tags = $input['tags'];

        $merchant->retag($tags);

        $this->repo->merchant->syncToEsLiveAndTest($merchant, EsRepository::UPDATE);

        return $merchant->tagNames();
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

        foreach ($merchantIds as $merchantId) {
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

    protected function updateHoldFunds(string $merchantId, bool $holdFunds)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $merchant->setHoldFunds($holdFunds);

        $this->repo->saveOrFail($merchant);
    }

    public function getUsers(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $users = (new Merchant\Core)->getUsers($merchant);

        return $users;
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

    /**
     * Gets the feature names to be added. A feature needs to be added to merchant
     * only if the value in input is equal to the default value of the feature
     */
    private function getFeatureNamesToAdd($features)
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
     */
    private function getFeatureNamesToRemove($features)
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

    private function addFeatures($featureNames, $shouldSync = false)
    {
        $merchant = $this->merchant;

        if (count($featureNames) > 0)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID    => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE  => 'merchant',
                'names'                      => $featureNames,
                EntityConstants::SHOULD_SYNC => intval($shouldSync)
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }
    }

    private function removeFeatures($featureNames, $shouldSync = false)
    {
        $merchant = $this->merchant;

        $entityId = $merchant->getId();

        foreach ($featureNames as $featureName)
        {
            $feature = $this->repo->feature->findByEntityIdAndNameOrFail(
                $entityId,
                $featureName);

            if ($feature !== null)
            {
                (new Feature\Core)->delete($entityId, $feature, $shouldSync);
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
     * @param $merchant
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
}

<?php

namespace RZP\Models\Merchant;

use DB;
use Mail;
use Config;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Emi;
use RZP\Models\Key;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Offer;
use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Schedule;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Group;
use RZP\Constants\MailTags;
use RZP\Models\BankAccount;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Settlement\Holidays;
use RZP\Models\Schedule\Task as ScheduleTask;

class Service extends Base\Service
{
    use Notify;

    /**
     * Creates a merchant and saves in database
     *
     * @param  array            $input
     * @return Merchant\Entity
     */
    public function create(array $input)
    {
        if (empty($input['admin_id']) === false)
        {
            $adminId = $input['admin_id'];

            $adminId = Admin\Admin\Entity::verifyIdAndStripSign($adminId);

            unset($input['admin_id']);
        }

        if (empty($input['org_id']) === true)
        {
            // If the organization ID is not present,
            // assume the organization is razorpay
            $orgId = Admin\Org\Entity::RAZORPAY_ORG_ID;

            $this->trace->info(
                TraceCode::MERCHANT_ORG_NOT_GIVEN,
                [
                    'merchant_email' => $input[Entity::EMAIL],
                    'merchant_name'  => $input[Entity::NAME],
                ]);
        }
        else
        {
            $orgId = $input['org_id'];

            $orgId = Admin\Org\Entity::verifyIdAndStripSign($orgId);

            unset($input['org_id']);
        }

        $merchant = (new Merchant\Core)->create($input);

        //
        // Once the merchant is created we must
        // tag him to the admin referral
        //
        if (empty($adminId) === false)
        {
            //
            // This step is important to ensure that we are
            // attaching a valid admin in merchant_map table.
            // This will throw an exception if adminId doesn't exist.
            //
            $admin = $this->repo->admin->findOrFailPublic($adminId);

            // Attach merchant to admin
            $this->repo->sync($merchant, 'admins', [$adminId]);
        }

        $org = $this->repo->org->findOrFailPublic($orgId);

        // Update merchant org
        $merchant->org()->associate($org);

        $this->repo->saveOrFail($merchant);

        return $merchant->toArrayPublic();
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

        $this->repo->saveOrFail($subMerchant);

        return $subMerchant->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        if (isset($input['groups']) === true)
        {
            $groupIds = [];

            foreach ($input['groups'] as $id)
            {
                $groupIds[] = Group\Entity::verifyIdAndStripSign($id);
            }

            $input['groups'] = $groupIds;
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
        $data = [
            'name'  =>  $subMerchant->name,
            'email' =>  $subMerchant->email
        ];

        if ($subMerchant->email !== $aggregator->email)
        {
            $data['cc_email'] = $aggregator->email;
        }

        $this->sendEmail(
            'emails.merchant.welcome',
            'Welcome to Razorpay',
            $data);
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
            $id, ['methods', 'groups']);

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

        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->repo->saveOrFail($merchant);

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

        // this is a hack until dashboard starts using the route with new values
        if (isset($input[Entity::SETTLEMENT_SCHEDULE_ID]) === true)
        {
            $scheduleId = $input[Entity::SETTLEMENT_SCHEDULE_ID];

            $input = [
                ScheduleTask\Entity::METHOD      => null,
                ScheduleTask\Entity::TYPE        => ScheduleTask\Type::SETTLEMENT,
                ScheduleTask\Entity::SCHEDULE_ID => $scheduleId
            ];
        }

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
                $schedule = $merchant->schedule;

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
                        'schedule_id' => $merchant->getSettlementScheduleId(),
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

        $merchant->liveEnable();

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

        $merchant->liveDisable();

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
        $methods = (new Methods\Core)->getMethods($this->merchant);

        if ($methods === null)
        {
            return [];
        }

        return $methods->toArrayWithBankNames();
    }

    public function setPaymentBanks($id, $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        return (new Merchant\Methods\Core)->setPaymentBanksForMerchant(
            $merchant, $input);
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
        return $webhook->toArray();
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

        return $webhook->toArray();
    }

    public function getWebhook($id)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchant($id, $this->merchant);

        return $webhook->toArray();
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
            $today = Carbon::createFromTimestamp($input['on'], 'Asia/Kolkata');
        }
        else
        {
            $today = Carbon::today('Asia/Kolkata');
        }

        if (Holidays::isWorkingDay($today) == false)
        {
            return ['message' => 'Today is a holiday! Happy holidays :)'];
        }

        $from = Holidays::getPreviousWorkingDay($today);

        $newBeneficiaryCount = $this->repo->bank_account->getCountOfBankAccountsCreatedBetween(
                                                        $from->timestamp,
                                                        $today->timestamp);

        if ($newBeneficiaryCount > 0)
        {
            (new BankAccount\BeneficiaryFile)->generateBetweenTimestamps(
                                                        $from->timestamp,
                                                        $today->timestamp);
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

    protected function sendEmail($template, $subject, $data)
    {
        Mail::queue($template, $data, function($message) use ($data, $subject)
        {
            $message = $message->to($data['email'], $data['name'])
                               ->subject($subject);

            if (isset($data['cc_email']))
            {
                $message->cc($data['cc_email'], $data['name']);
            }

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::WELCOME);
        });
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

        $merchant->validateInput('feature', $input);

        $featuresToAdd = $this->getFeatureNamesToAdd($input['features']);

        $featuresToRemove = $this->getFeatureNamesToRemove($input['features']);

        $this->addFeatures($featuresToAdd);

        $this->removeFeatures($featuresToRemove);

        $data = (new Feature\Service)->getFeaturesForEntity($merchant);

        return $data;
    }

    public function getUsers(string $merchantId)
    {
        $merchants = (new Merchant\Core)->getUsers($merchantId);

        return $merchants;
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

    private function addFeatures($featureNames)
    {
        $merchant = $this->merchant;

        if (count($featureNames) > 0)
        {
            $featureParams = [
                Feature\Entity::ENTITY_ID => $merchant->getId(),
                Feature\Entity::ENTITY_TYPE => 'merchant',
                'names' => $featureNames
            ];

            (new Feature\Service)->addFeatures($featureParams);
        }
    }

    private function removeFeatures($featureNames)
    {
        $merchant = $this->merchant;

        foreach ($featureNames as $featureName)
        {
            $feature = $this->repo->feature->findByEntityIdAndNameOrFail(
                $merchant->getId(),
                $featureName);

            if ($feature !== null)
            {
                $this->repo->feature->delete($feature);
            }
        }
    }
}

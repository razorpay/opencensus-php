<?php

namespace RZP\Models\Merchant;

use RZP\Constants\Mode;
use Carbon\Carbon;
use Mail;

use RZP\Base\RuntimeManager;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Merchant;
use RZP\Models\Key;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Webhook;
use RZP\Models\Admin\Newsletter;
use RZP\Models\Settlement\Holidays;

use RZP\Exception;
use RZP\Error\ErrorCode;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    /**
     * Creates a merchant and saves in database
     *
     * @param  array            $input
     * @return Merchant\Enitty
     */
    public function create(array $input)
    {
        $merchant = (new Merchant\Core)->create($input);

        return $merchant->toArrayPublic();
    }

    public function createSubMerchant(array $input)
    {
        $merchant = $this->merchant;

        $subMerchant = (new Merchant\Core)->createSubMerchant($input, $merchant);

        // This goes out to the aggregator
        $this->sendSubMerchantCreationMail($subMerchant, $merchant);

        return $subMerchant->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

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

    public function addOrUpdateMerchantFeatures($id, array $input)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        foreach ($input as $key => $value)
        {
            $input[$key] = strtolower($input[$key]);
        }

        $merchant = (new Merchant\Core)->addOrUpdateMerchantFeatures($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function getMerchantFeatures($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $features = $merchant->getFeatures();

        return $features;
    }

    // This is on internal auth
    public function fetch($id)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($id);

        $methods = $merchant->methods;

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

    public function editFreeCredits($merchantId, $input)
    {
        (new Merchant\Validator)->validateInput('edit_credits', $input);

        $freeCredits = $input['credits'];

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $balance = $this->repo->balance->editMerchantFreeCredits($merchant, $freeCredits);

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

        $merchant->liveEnable();

        $this->repo->saveOrFail($merchant);

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
        $repo = $this->repo;

        $merchants = $repo->fetchMerchantWhereTestBankIsNull();
        $fetched = $merchants->count();

        $core = new Merchant\Core;

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

        $methods = new Merchant\Methods\Entity;

        return (new Merchant\Methods\Core)->setPaymentBanksForMerchant(
            $merchant, $input);
    }

    public function setBanksForAllMerchants($input)
    {
        // @todo: finish this.
        // return (new Merchant\Methods\Core)->setPaymentBanksForAllMerchants($input);
    }

    public function getFeeBearer()
    {
        $feeBearer = $this->merchant->isFeeBearerCustomer();

        return $feeBearer;
    }

    public function getPaymentMethods()
    {
        $data = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [],
            'emi'           => false,
            'upi'           => false,
        );

        $methods = (new Methods\Core)->getMethods($this->merchant);

        if ($methods !== null)
        {
            $data['card'] = $methods->isCardEnabled();
            $netbankingEnabled = $methods->isNetbankingEnabled();
            if ($netbankingEnabled === true)
            {
                $data['netbanking'] = $methods->toArrayWithBankNames();
            }
            $data['wallet'] = $methods->getEnabledWallets();
            $data['emi'] = $methods->isEmiEnabled();
            $data['upi'] = $methods->isUpiEnabled();
        }

        if ($this->mode === Mode::TEST)
        {
            $data['card'] = true;
        }

        return $data;
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
        $webhook = (new Webhook\Core)->editWebhook($this->merchant, $webhookId, $input);

        return $webhook->toArray();
    }

    public function getWebhook($id)
    {
        $webhook = $this->repo->webhook->findByIdAndMerchantId($id, $this->merchant->getId());

        return $webhook->toArray();
    }

    public function getWebhooks()
    {
        $webhooks = $this->repo->webhook->fetch([], $this->merchant->getId());

        return $webhooks->toArrayPublic();
    }

    public function getMerchantBeneficiaryFile()
    {
        $file = (new BankAccount\BeneficiaryFile3)->generate();

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
            (new BankAccount\BeneficiaryFile3)->generateBetweenTimestamps(
                                                        $from->timestamp,
                                                        $today->timestamp);
        }

        $message = "Merchant Beneficiary file generated. Beneficiary added since".
                " last report is ". $newBeneficiaryCount;

        $this->slack->queue($message,[],['channel' => '#settlements']);

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
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(300);

        // Trace to indicate start of mailing
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_MAILING,
            array()
        );

        $merchants = Base\PublicCollection;

        if (isset($input[Entity::ID]) === true)
        {
            $merchant = $this->repo->merchant->findOrFailPublic($input[Entity::ID]);

            $merchants->push($merchant);
        }
        else
        {
            $merchants = $this->repo->merchant->fetchAllLiveMerchants()
                                              ->select(Entity::ID)
                                              ->get();
        }

        // sent will hold array of merchant data
        $response = ['sent' => [], 'skipped' => 0];

        // Summary of merchants mailed
        $mailedMerchantsSummary = ['sent' => [], 'sentCount' => 0, 'skippedCount' => 0];

        foreach ($merchants as $merchant)
        {
            try
            {
                $dailyReport = new DailyReport($merchant->getId());

                $sent = $dailyReport->send();

                if (empty($sent))
                {
                    $response['skipped']++;
                    $mailedMerchantsSummary['skippedCount']++;
                }
                else
                {
                    $response['sent'][] = $sent;
                    $mailedMerchantsSummary['sentCount']++;
                    $mailedMerchantsSummary['sent'][] = $sent['merchant']['id'];
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex, Trace::WARNING, TraceCode::SETTLEMENT_DAILY_REPORT_FAILURE);
            }
        }

        // Log just the result of the settlement reports
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_RESULT,
            $mailedMerchantsSummary
        );

        return $response;
    }

    /**
     * Send newsletter to a particular merchant
     *
     * @param  string $merchantId Merchant Id
     * @param  [type] $input      [description]
     * @return null
     */
    public function sendNewsletter($merchantId, $input)
    {
        (new Merchant\Validator)->validateInput('send_email', $input);

        $template = $input['template'];

        $merchant = $this->repo->merchant->findOrFailPublic($id)->toArray();
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
}

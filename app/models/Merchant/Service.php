<?php

namespace Models\Merchant;

use Constants\Mode;
use Carbon\Carbon;
use Mail;

use Models\Base;
use Models\Merchant;
use Models\Key;
use Models\Payment;
use Models\Pricing;
use Models\Terminal;
use Models\Merchant\Webhook;
use Models\Admin\Newsletter;
use Models\Settlement\Holidays;
use EE\Exception;
use EE\Error\ErrorCode;

use Trace\TraceCode;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository;
        $this->balanceRepo = new Merchant\Balance\Repository;
    }

    /**
     * Creates a merchant and saves in database
     *
     * @param  array            $input
     * @return Merchant\Enitty
     */
    public function create(array $input)
    {
        $merchant = (new Merchant\Core)->create($input);

        // The merchant is created on email confirmation on dashboard side
        // This is when we send the welcome email

        $this->sendEmail(
            'emails.merchant.welcome',
            'Welcome to Razorpay',
            $merchant->toArray());

        return $merchant->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->edit($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function editEmail($id, array $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->editEmail($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function editConfig(array $input)
    {
        $merchant = (new Merchant\Core)->editConfig($this->merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function addOrUpdateMerchantFeatures($id, array $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        foreach ($input as $key => $value)
        {
            $input[$key] = strtolower($input[$key]);
        }

        $merchant = (new Merchant\Core)->addOrUpdateMerchantFeatures($merchant, $input);

        return $merchant->toArrayPublic();
    }

    public function getMerchantFeatures($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $features = $merchant->getFeatures();

        return $features;
    }

    // This is on internal auth
    public function fetch($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $methods = $merchant->methods;

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $merchants = $this->repo->fetch($input);

        return $merchants->toArrayPublic();
    }

    // This is on proxy auth
    public function fetchConfig()
    {
        $merchantId = $this->merchant->getId();

        $merchant = $this->repo->findOrFailPublic($merchantId, Entity::CONFIG_LIST);

        return $merchant->toArray();
    }

    public function fetchBalance($merchantId = null)
    {
        if(null === $merchantId)
        {
            $merchantId = $this->merchant->getId();
        }

        $merchant = $this->repo->findOrFailPublic($merchantId);

        //
        // For non-activated merchants in live mode, simply return 0.
        // For these merchants, balance entity is not yet created so
        // we need to create the exception here.
        //
        if (($this->mode === Mode::LIVE) and
            ($merchant->getActivatedAttribute() === false) and
            (Account::isNodalAccount($merchantId) === false))
        {
            $balance[Balance\Entity::ID] = $merchantId;
            $balance[Balance\Entity::BALANCE] = 0;

            return $balance;
        }

        $balance = $this->balanceRepo->getMerchantBalance($merchant);

        return $balance->toArray();
    }

    public function editFreeCredits($merchantId, $input)
    {
        (new Merchant\Validator)->validateInput('edit_credits', $input);

        $freeCredits = $input['credits'];

        $merchant = $this->repo->findOrFailPublic($merchantId);

        $balance = $this->balanceRepo->editMerchantFreeCredits($merchant, $freeCredits);

        return $balance->toArray();
    }

    public function createKey($merchantId)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        $keyData = (new Key\Core)->createFirstKey($merchant, $this->mode);

        return $keyData;
    }

    public function updateKey($merchantId, $keyId, array $input)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        return (new Key\Core)->rollKey($merchantId, $keyId, $input, $this->mode);
    }

    public function fetchKeys($merchantId)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        $keys = (new Key\Repository)->getKeysForMerchant($merchantId);

        return $keys->toArrayPublic();
    }

    public function assignPricingPlan($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $plan = (new Pricing\Repository)->getPricingPlanByIdOrFailPublic(
                                            $input['pricing_plan_id']);

        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->repo->saveOrFail($merchant);

        return $plan->toArrayPublic();
    }

    public function getPricingPlan($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = (new Pricing\Repository)->getPricingPlanById($pricingPlanId);

        return $plan->toArrayPublic();
    }

    public function activate($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $act = new Activate($this->app);
        $act->activate($merchant);

        return $merchant->toArrayPublic();
    }

    public function liveEnable($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

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
        $merchant = $this->repo->findOrFailPublic($id);

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
        $merchant = $this->repo->findOrFailPublic($id);

        $ba = (new BankAccount\Core)->createOrChangeBankAccount($input, $merchant);

        return $ba->toArray();
    }

    public function getBankAccount($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $ba = (new BankAccount\Repository)->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        return $ba->toArray();
    }


    public function generateBankAccountIds()
    {
        $bankAccountRepo = new BankAccount\Repository();

        $bankAccounts = $bankAccountRepo->bankAccountsWhereIdNullOrBlank();

        $fetched = $bankAccounts->count();

        $bankAccountRepo->beginTransaction();

        $count = 0;

        try {
            foreach ($bankAccounts as $bankAcc)
            {
                $bankAcc->generateIdFromCreatedAt();
                $bankAccountRepo->save($bankAcc);
                $count++;
            }

            $bankAccountRepo->commit();
        }
        catch (Exception $e)
        {
            $bankAccountRepo->rollback();
            throw new Exception\RuntimeException(
                        'Failed generating BankAccount id',
                        $e->getTrace());
        }

        return ['fetched' => $fetched, 'processed' => $count];

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
        $merchant = $this->repo->findOrFailPublic($id);

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
        $merchant = $this->repo->findOrFailPublic($id);

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
            'emi'           => false
        );

        $methods = (new Methods\Core)->getMethods($this->merchant);

        if ($methods !== null)
        {
            $data['card'] = $methods->isCardEnabled();
            $data['netbanking'] = $methods->toArrayWithBankNames();
            $data['wallet'] = $methods->getEnabledWallets();
            $data['emi'] = $methods->isEmiEnabled();
        }

        if ($this->mode === Mode::TEST)
        {
            $data['card'] = true;
        }

        return $data;
    }

    public function setPaymentMethods($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return (new Merchant\Methods\Core)->setPaymentMethods($merchant, $input);
    }

    public function getMerchantWebhooks($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

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
        $webhook = (new Webhook\Repository)->findByIdAndMerchantId($id, $this->merchant->getId());

        return $webhook->toArray();
    }

    public function getWebhooks()
    {
        $webhooks = (new Webhook\Repository)->fetch([], $this->merchant->getId());

        return $webhooks->toArrayPublic();
    }

    public function getMerchantBeneficiaryFile()
    {
        $file = (new BankAccount\BeneficiaryFile)->generate();

        return $file;
    }

    public function getCheckoutPreferences()
    {
        $merchant = $this->merchant;

        return (new Checkout)->getPreferences($merchant, $this->mode);
    }

    /**
    *   Generate and Send the beneficary file to nodal account's bank
    *   if a new merchant has been activated since
    *   if (monday)  - 3 days
    *   else         - 1 day
    */
    public function postMerchantBeneficiaryFile()
    {
        $filterDays = 1;
        $today = Carbon::today('Asia/Kolkata');
        $filterDate = Carbon::today('Asia/Kolkata');

        $dayToday = $today->dayOfWeek;
        if ($dayToday === Carbon::MONDAY)
        {
            $filterDays = 3;
        }

        $filterDate = $filterDate->subDays($filterDays);

        $merchantsActivatedSinceLastReport = $this->repo->getCountOfMerchantsActivatedBetween(
                                                        $filterDate->timestamp,
                                                        $today->timestamp);

        if ($merchantsActivatedSinceLastReport > 0)
        {
            (new BankAccount\BeneficiaryFile)->generate();

            $message = "Merchant Beneficiary file generated. Merchants activated since last".
                    " report is ".$merchantsActivatedSinceLastReport;

            $this->slackPost($message,[],['channel' => '#tech_logs']);
        }

        //Log response in trace
        $this->trace->info(
            TraceCode::MERCHANT_BENEFICIARY_FILE_GENERATE,
            array('new_merchants_activated' => $merchantsActivatedSinceLastReport));

        return $merchantsActivatedSinceLastReport;
    }

    /**
     * sends daily reports for all merchants that are currently live
     * Returns an array with the keys: `skipped`, and `sent`,
     * each containing the number of merchants in each category
     * @return array debug response
     */
    public function sendDailyReportForAllMerchants()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $filter = [];

        // In test, none of the merchants are activated
        if ($this->mode === Mode::LIVE)
        {
            $filter = [Entity::ACTIVATED => 1];
        }

        $merchants = $this->repo->fetch($filter);

        // sent will hold array of merchant data
        $response = ['sent' => [], 'skipped' => 0];

        $counts = ['sent' => 0, 'skipped' => 0];

        foreach ($merchants as $merchant)
        {
            $dailyReport = new DailyReport($merchant->getId());

            $sent = $dailyReport->send();

            if(empty($sent))
            {
                $response['skipped']++;
            }
            else
            {
                $response['sent'][] = $sent;
            }
        }

        // Log just the result of the settlement reports
        $this->trace->info(
            TraceCode::SETTLEMENT_DAILY_REPORT_RESULT,
            $response
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

        $merchant = $this->repo->findOrFailPublic($id)->toArray();
    }

    protected function sendEmail($template, $subject, $data)
    {
        Mail::queue($template, $data, function($message) use ($data, $subject){

            $message->to($data['email'], $data['name'])
                ->subject($subject);
        });
    }

    public function notifyMerchantsHoliday($input)
    {
        if (Holidays::isThisDayHoliday($this->mode, 'tomorrow') === false)
        {
            return ['message' => 'Not a holiday tomorrow! Nothing to send.'];
        }

        if ($input['test'] === 'true')
        {
            $response = $this->sendTestHolidayNotificationMail($input);
        }
        else
        {
            $response = $this->sendHolidayNotificationMail($input);
        }


        // Log just the result of the settlement reports
        $this->trace->info(
            TraceCode::MERCHANT_NOTIFY_HOLIDAY,
            $response
        );

        return $response;
    }

    protected function sendHolidayNotificationMail($input)
    {
        $msg = $this->getHolidayNotificationMsg();

        if (empty($errors))
        {
            $mailer = new Newsletter(
                $input['lists'],
                'Notification of Bank Holiday',
                $msg,
                'newsletter'
                );

            $mailer->setMailingListName('bank-holiday-notification');

            if ($input['test_list_add'] === 'true')
            {
                $mailer->setTestListMembersAdd();
            }

            return $mailer->send();
        }
        else
        {
            return $errors;
        }
    }

    protected function sendTestHolidayNotificationMail($input)
    {
        $msg = $this->getHolidayNotificationMsg();

        if (empty($errors))
        {
            $mailer = new Newsletter(
                $input['lists'],
                'Notification of Bank Holiday',
                $msg,
                'newsletter',
                true
                );

            return $mailer->send();
        }
        else
        {
            return $errors;
        }
    }

    protected function getHolidayNotificationMsg()
    {
        $date = Carbon::tomorrow('Asia/Kolkata')->toFormattedDateString();

        $msg  = <<<EOT
<b>As $date is a bank holiday, settlements will not be processed tomorrow.</b>
Settlements expected on this date will be processed on the next working day.
<p>Thank you for partnering with Razorpay.</p>
EOT;

        return $msg;
    }
}

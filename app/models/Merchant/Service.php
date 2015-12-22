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

    public function fetchBalance($merchantId)
    {
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

        return (new Key\Core)->rollKey($keyId, $input, $this->mode);
    }

    public function fetchKeys($merchantId)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        $keys = (new Key\Repository)->getKeysForMerchant($merchantId);

        return $keys->toArrayPublic();
    }

    public function retrieveById($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return $merchant->toArrayPublic();
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

        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $pricing = $this->repo->getPricingPlanOrFailPublic($merchant);

        // $terminal = (new Terminal\Repository)->getByMerchantId($id);

        // if ($terminal === null)
        // {
        //     throw new Exception\BadRequestException(
        //         ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        // }

        $ba = (new BankAccount\Repository)->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        (new Merchant\Validator)->validateBeforeActivate($merchant);

        (new Merchant\Core)->createBalance($merchant, 'live');

        $merchant->activate();

        $this->repo->saveOrFail($merchant);

        $this->sendActivationEmail($merchant);

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
        $banks = (new Methods\Core)->getMerchantBanks($this->merchant);

        if ($banks === null)
            return [];

        return $banks->toArrayWithBankNames();
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

    public function getPaymentMethods()
    {
        $picker = new Payment\Processor\TerminalPicker;

        $data = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => []);

        $methods = (new Merchant\Methods\Core)->getMerchantBanks($this->merchant);

        if ($methods !== null)
        {
            $data['card'] = $methods->isCardEnabled();
            $data['netbanking'] = $methods->toArrayWithBankNames();
            $data['wallet'] = $methods->getEnabledWallets();
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

    public function getMerchantBeneficiaryFile()
    {
        $file = (new BankAccount\BeneficiaryFile)->generate();

        return $file;
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
        }

        //Log response in trace
        $this->trace->info(
            TraceCode::MERCHANT_BENEFICIARY_FILE_GENERATE,
            array('new_merchants_activated' => $merchantsActivatedSinceLastReport));

        return $merchantsActivatedSinceLastReport;
    }

    /**
     * Sends activation email to the merchant, cc's notifications
     * Includes pricing details in the email (properly formatted)
     * @param  Models\Merchant\Entity $merchant merchant entity
     * @return null
     */
    protected function sendActivationEmail($merchant)
    {
        $plan = $merchant->getPricingPlan();

        $subjectName = $merchant->getBillingLabelElseName();

        $subject = "Razorpay | Account activated for $subjectName";

        $data = [
            'merchant'  =>  $merchant->toArray(),
            'plan'      =>  $plan,
            'rules'     =>  $this->formatPricingRules($plan['rules']),
            'subject'   =>  $subject,
        ];

        $config = $this->app->config->get('applications.mailgun');

        // Send the activation email
        $this->app['mailer']->queue(
            [
                'html' => 'emails.merchant.activation',
                'text' => 'emails.merchant.activation_text'
            ],
            $data,
            function ($message) use ($data, $config)
            {
                $message->to($data['merchant']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->cc('notifications@razorpay.com');
                $message->subject($data['subject']);
            }
        );
    }

    /**
     * Returns formatted pricing rules with proper display text
     * as an array with the display text as the key
     * [
     *   "2%" => ["Credit Cards", "Wallets"],
     *   "1.8%" => ["Wallets"],
     *   "2.1%" => ["Net Banking"]
     * ]
     * @param  array $rules Array of rules
     * @return array Formatted rules with flipped keys
     */
    protected function formatPricingRules($rules)
    {
        $newRules = [];

        $rules = $this->rearrangeRules($rules);

        foreach ($rules as $rule)
        {
            $rule['pricing_display'] = Pricing\Plan::formattedPricing($rule);

            // This just holds Wallet/Card/Net Banking as of now
            $display = Payment\Method::formatted($rule['payment_method']);

            // This now holds Credit/Debit/All
            $method = $rule['payment_method_type'] ? : 'Visa/MasterCard/Discover/Diners';

            // If we have a payment_network (such as AMEX/DICL)
            if ($rule['payment_network'] !== null)
            {
                // This becomes "American Express Cards"
                $display = $rule['payment_network_name'] . ' Cards';
            }
            elseif ($method !== null and $rule['payment_method'] === 'card')
            {
                // This is Credit/Debit/[ Visa/Master Card/Diners ] Cards
                $display = ucfirst($method) . ' Cards';
            }

            // We flip this around to store the rules as an array with the
            // pricing display as the key. Since the pricing display is
            // deterministic (see Pricing\Plan::formattedPricing)
            // The same pricing gives the same display
            //
            // Now we can iterate over the newRules array and display
            // the list of pricing options at the same pricing in the same
            // line easily
            $newRules[$rule['pricing_display']][] = $display;
        }

        return $newRules;
    }

    /**
     * Rearrange $rules to display in emails in appropriate order
     * Rules are arranged on basis of usage:
     *  Basic Card Rules,
     *  Basic Netbanking Rules,
     *  Basic Wallet Rules,
     *  Any Other Exceptional Cases,
     * @param array $rules
     * @return array
     */
    protected function rearrangeRules(array $rules)
    {
        $arrangedRules = array();

        $orderOfRules = array('card', 'netbanking', 'wallet', 'exceptional');

        $exceptionalRules = $cardRules = $netbankingRules = $walletRules = [];

        foreach ($rules as $rule)
        {
            switch ($rule['payment_method'])
            {
                case 'card':
                    if ($rule['payment_network'] === null)
                    {
                        $cardRules[] = $rule;
                    }
                    else
                    {
                        $exceptionalRules[] = $rule;
                    }

                    break;

                case 'netbanking':
                    $netbankingRules[] = $rule;
                    break;

                case 'wallet':
                    $walletRules[] = $rule;
                    break;

                default:
                    $exceptionalRules[] = $rule;
                    break;
            }
        }

        foreach ($orderOfRules as $ruleType)
        {
            $arrangedRules = array_merge($arrangedRules, ${$ruleType.'Rules'});
        }

        return $arrangedRules;
    }

    /**
     * sends daily reports for all merchants that are currently live
     * Returns an array with the keys: `skipped`, and `sent`,
     * each containing the number of merchants in each category
     * @return array debug response
     */
    public function sendDailyReportForAllMerchants()
    {
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
}

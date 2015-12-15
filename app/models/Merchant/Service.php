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

        $this->sendEmail('emails.merchant.welcome', 'Welcome to Razorpay', $merchant->toArray());

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

        $bankAccountRepo = new BankAccount\Repository;
        $ba = $bankAccountRepo->getBankAccount($merchant);

        if ($ba !== null)
        {
            $baCopy = (new BankAccount\Entity)->build($input);
            $baCopy->merchant()->associate($merchant);

            if ($ba->equals($baCopy))
            {
                return $ba->toArray();
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_BANK_ACCOUNT_ALREADY_PROVIDED);
        }

        $ba = (new BankAccount\Entity)->build($input);

        $code = $ba->beneficiary_code;

        $count = $bankAccountRepo->getBeneficiaryCodeCountByPattern($code);

        if ($count === 0)
            $count = '';
        else
            $count++;

        $code .= $count;

        $ba->beneficiary_code = $code;

        $ba->merchant()->associate($merchant);

        $bankAccountRepo->saveOrFail($ba);

        return $ba->toArray();
    }

    public function getBankAccount($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $ba = (new BankAccount\Repository)->getBankAccount($merchant);

        return $ba->toArray();
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

        $dayToday = $today->dayOfWeek;
        if ($dayToday === Carbon::MONDAY)
        {
            $filterDays = 3;
        }

        $filterDate = $today->subDays($filterDays);
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

    protected function sendActivationEmail($merchant)
    {

        $plan = $merchant->getPricingPlan();

        $data = [
            'merchant'  =>  $merchant->toArray(),
            'plan'      =>  $plan,
            'rules'     => $this->formatPricingRules($plan['rules'])
        ];

        $config = $this->app->config->get('applications.mailgun');
        $subject = "Razorpay | Account activated for {$data['merchant']['name']}";

        Mail::queue(
            [
                'html' => 'emails.merchant.activation',
                'text' => 'emails.merchant.activation_text'
            ],
            $data,
            function ($message) use ($data, $config, $subject)
            {
                $message->to($data['merchant']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->cc('notifications@razorpay.com');
                $message->subject($subject);
            }
        );
    }

    protected function formatPricingRules($rules)
    {
        $newRules = [];

        foreach ($rules as $rule)
        {
            $rule['pricing_display'] = Pricing\Plan::formattedPricing($rule);

            // This just holds Wallet/Card/Net Banking as of now
            $display = Payment\Method::formatted($rule['payment_method']);

            // This now holds Credit/Debit/All
            $method = $rule['payment_method_type'] ? : 'All';

            // If we have a payment_network (such as AMEX/DICL)
            if ($rule['payment_network'] !== null)
            {
                // This becomes "American Express Cards"
                $display = $rule['payment_network_name'] . ' Cards';
            }
            elseif ($method !== null and $rule['payment_method'] === 'card')
            {
                // This is Credit/Debit/All Cards
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
     * sends daily reports for all merchants that are currently live
     */
    public function sendDailyReportForAllMerchants()
    {
        $filter = [];

        //In test, none of the merchants are activated
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

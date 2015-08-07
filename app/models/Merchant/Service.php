<?php

namespace Models\Merchant;

use Constants\Mode;
use Mail;
use Models\Base;
use Models\Merchant;
use Models\Key;
use Models\Payment;
use Models\Pricing;
use Models\Terminal;
use EE\Exception;
use EE\Error\ErrorCode;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository();
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

        return $merchant->toArrayPublic();
    }

    public function edit($id, array $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $merchant = (new Merchant\Core)->edit($merchant, $input);

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

        $balance = $this->repo->getMerchantBalance($merchant);

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
            $baCopy = (new BankAccount)->build($input);

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

        $banks = (new Banks\Core)->getEnabledAndDisabledBanks($merchant);

        return $banks;
    }

    public function getEnabledBanks()
    {
        $banks = (new Banks\Core)->getMerchantBanks($this->merchant);

        if ($banks === null)
            return [];

        return $banks->toArrayWithBankNames();
    }

    public function setPaymentBanks($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return (new Merchant\Banks\Core)->setPaymentBanksForMerchant(
            $merchant, $input
        );
    }

    public function setBanksForAllMerchants($input)
    {
        // @todo: finish this.
        // return (new Merchant\Banks\Core)->setPaymentBanksForAllMerchants($input);
    }

    public function getPaymentMethods()
    {
        $picker = new Payment\Processor\TerminalPicker;

        $data = array(
            'entity'        => 'methods',
            'card'          => true,
            'netbanking'    => [],
            'wallet'        => [
                'paytm'     => false,
            ]);

        $methods = (new Merchant\Banks\Core)->getMerchantBanks($this->merchant);

        if ($methods !== null)
        {
            $data['card'] = $methods->isCardEnabled();
            $data['netbanking'] = $methods->toArrayWithBankNames();
            $data['wallet']['paytm'] = $methods->isPaytmEnabled();
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

        return (new Merchant\Banks\Core)->setPaymentMethods($merchant, $input);
    }

    public function getMerchantBeneficiaryFile()
    {
        $file = (new BankAccount\BeneficiaryFile)->generate();

        return $file;
    }

    protected function sendActivationEmail($merchant)
    {
        //TODO: This needs to be refactored when we go for differentiated pricing
        $plan = $merchant->getPricingPlan();

        // array_values resets the array numeric keys and then we can pick the first rule
        // @todo: explain this part
        $plan = array_values(array_filter(
            $plan['rules'],
            function($rule)
            {
                return $rule['payment_method']  == 'card';
            }
        ))[0];

        $data = [
            'merchant'  =>  $merchant->toArray(),
            'plan'      =>  $plan,
        ];

        $config = $this->app->config->get('applications.mailgun');
        $subject = "Your Razorpay account has been activated";

        $this->app['mailer']->queue(
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

    /**
     * sends daily reports for all merchants that are currently live
     */
    public function sendDailyReportForAllMerchants()
    {
        $merchants = $this->repo->fetch([Entity::ACTIVATED => 1]);

        $counts = ['sent' => 0, 'skipped' => 0];

        foreach ($merchants as $merchant)
        {
            $dailyReport = new DailyReport($merchant->getId());

            $sent = $dailyReport->send();

            if($sent)
            {
                $counts['sent']++;
            }
            else
            {
                $counts['skipped']++;
            }
        }

        return $counts;
    }
}

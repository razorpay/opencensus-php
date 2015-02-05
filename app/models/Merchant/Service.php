<?php

namespace Models\Merchant;

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
        $merchant = (new Merchant\Entity)->build($input);

        $merchant->setPricingPlan(Pricing\DefaultPlan::PROMOTIONAL_PLAN_ID);

        $this->repo->saveOrFail($merchant);

        $merchantBalance = Merchant\Balance::buildFromMerchant($merchant);

        $this->repo->updateBalance($merchantBalance);

        (new Terminal\Core)->createTerminalsInTestMode($merchant);

        (new Banks\Core)->setAllPaymentBanks($merchant);

        return $merchant->toArrayPublic();
    }

    public function fetch($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $merchants = $this->repo->fetch($input);

        return $merchants->toArrayPublic();
    }

    public function createKey($merchantId)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        $keyData = (new Key\Core)->createFirstKey($merchantId, $this->mode);

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

    public function createTerminal($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $terminal = (new Terminal\Core)->create($input, $merchant);

        return $terminal->toArrayPublic();
    }

    public function getTerminals($mid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminals = (new Terminal\Repository)->getByMerchantId($mid);

        return $terminals->toArrayPublic();
    }

    public function getTerminal($mid, $tid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminal = (new Terminal\Repository)->getByIdAndMerchantId($mid, $id);

        return $terminal->toArrayPublic();
    }

    public function deleteTerminal($mid, $tid)
    {
        $merchant = $this->repo->findOrFailPublic($mid);

        $terminalRepo = new Terminal\Repository;
        $terminal = $terminalRepo->getByIdAndMerchantId($mid, $id);

        $terminalRepo->deleteOrFail($terminal);

        return $terminal->toArrayPublic();
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

        $terminal = (new Terminal\Repository)->getByMerchantId($id);

        if ($terminal === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        }

        $ba = $this->repo->getBankAccount($merchant);

        if ($ba === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND);
        }

        $merchant->activate();

        $this->repo->saveOrFail($merchant);

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

        $ba = $this->repo->getBankAccount($merchant);

        if ($ba !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_BANK_ACCOUNT_ALREADY_PROVIDED);
        }

        $ba = (new Merchant\BankAccount)->build($input);

        $ba->merchant()->associate($merchant);

        $this->repo->updateBankAccount($ba);

        return $ba->toArray();
    }

    public function getBankAccount($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $ba = $this->repo->getBankAccount($merchant);

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

        return (new Merchant\Banks\Core)->setPaymentBanksForMerchant($merchant, $input);
    }
}
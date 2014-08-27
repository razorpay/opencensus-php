<?php

namespace Models\Merchant;

use Models\Base;
use Models\Merchant;
use Models\Key;
use Models\Pricing;
use Models\Terminal;
use EE\Exception;
use EE\Error\ErrorCode;

class Service extends Base\Service
{
    protected $keyRepository;

    protected $merchantRepository;

    protected $merchant;

    public function __construct()
    {
        $this->merchantRepository = new Merchant\Repository();

        $this->keyRepository = new Key\Repository();
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

        $merchantIdArr['id'] = $input['id'];

        $this->merchantRepository->saveOrFail($merchant);

        $merchantBalance = new Merchant\Balance($merchantIdArr);

        $this->merchantRepository->updateBalance($merchantBalance);

        return $merchant->toArray();
    }

    public function fetch($id)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        return array_merge($merchant->toArray(), ['entity' => 'merchant']);
    }

    public function createKey($merchantId)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($merchantId);

        $keys = $this->keyRepository->getKeysForMerchant($merchantId);

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        $keyData = (new Key\Core)->createAndReturnWithSecret($merchantId);

        return $keyData;
    }

    public function updateKey($merchantId, $keyId, array $input)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($merchantId);

        $old = $this->keyRepository->findOrFailPublic($keyId);

        $keyCore = new Key\Core;

        $delay = false;

        if (isset($input['delay_roll']))
        {
            $delay = ($input['delay_roll'] === '1') ? true : false;
        }

        unset($input['delay_roll']);

        $keyCore->setExpired($old, $delay);

        $keyData = $keyCore->createAndReturnWithSecret($merchantId);

        $keysData['old'] = $old->toArray();

        $keysData['new'] = $keyData;

        return $keysData;
    }

    public function fetchKeys($merchantId)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($merchantId);

        $keys = $this->keyRepository->getKeysForMerchant($merchantId);

        return array('count' => count($keys), 'data' => $keys);
    }

    public function retrieveById($id)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        return $merchant->toArray();
    }

    public function assignPricingPlan($id, $input)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        if (isset($input['pricing_plan_id']) === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_ID_REQURED,
                'pricing_plan_id');
        }

        $plan = (new Pricing\Repository)->getPricingPlanByIdOrFailPublic(
                                            $input['pricing_plan_id']);

        $merchant->setPricingPlan($input['pricing_plan_id']);

        $this->merchantRepository->saveOrFail($merchant);

        $plan = $plan->toArrayPublic();

        return $plan;
    }

    public function getPricingPlan($id)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        $pricingPlanId = $merchant->getPricingPlanId();

        $plan = (new Pricing\Repository)->getPricingPlanById($pricingPlanId);

        return $plan->toArrayPublic();
    }

    public function createTerminal($id, $input)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        $terminal = (new Terminal\Core)->create($input, $merchant);

        return $terminal->toArray();
    }

    public function getTerminal($id)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        $terminal = (new Terminal\Repository)->getByMerchantId($id);

        if ($terminal === null)
        {
            return array();
        }

        return $terminal->toArray();
    }

    public function activate($id)
    {
        $merchant = $this->merchantRepository->findOrFailPublic($id);

        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $pricing = (new Merchant\Repository)->getPricingPlan($merchant);

        $terminal = (new Terminal\Repository)->getByMerchantId($id);

        if ($terminal === null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        }

        $merchant->activate();

        $this->merchantRepository->saveOrFail($merchant);

        return $merchant->toArray();
    }
}
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
    protected $repo;

    public function __construct()
    {
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

        $merchantIdArr['id'] = $input['id'];

        $this->repo->saveOrFail($merchant);

        $merchantBalance = new Merchant\Balance($merchantIdArr);

        $this->repo->updateBalance($merchantBalance);

        return $merchant->toArray();
    }

    public function fetch($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return array_merge($merchant->toArray(), ['entity' => 'merchant']);
    }

    public function createKey($merchantId)
    {
        $merchant = $this->repo->findOrFailPublic($merchantId);

        $keys = (new Key\Repository)->getKeysForMerchant($merchantId);

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        $keyData = (new Key\Core)->createAndReturnWithSecret($merchantId, $this->mode);

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

        $keysArray = array();

        foreach ($keys->all() as $key)
        {
            array_push($keysArray, $key->toArrayPublic());
        }

        return array('entity' => 'collection', 'count' => count($keys), 'data' => $keysArray);
    }

    public function retrieveById($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        return $merchant->toArray();
    }

    public function assignPricingPlan($id, $input)
    {
        $merchant = $this->repo->findOrFailPublic($id);

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

        return $terminal->toArray();
    }

    public function getTerminal($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        $terminal = (new Terminal\Repository)->getByMerchantId($id);

        if ($terminal === null)
        {
            return array();
        }

        return $terminal->toArray();
    }

    public function activate($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        if ($merchant->isActivated())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ACTIVATED);
        }

        $pricing = (new Merchant\Repository)->getPricingPlanOrFailPublic($merchant);

        $terminal = (new Terminal\Repository)->getByMerchantId($id);

        if ($terminal === null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NO_TERMINAL_ASSIGNED);
        }

        $merchant->activate();

        $this->repo->saveOrFail($merchant);

        return $merchant->toArray();
    }

    public function liveEnable($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive())
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_LIVE);
        }

        $merchant->liveEnable();

        $this->repo->saveOrFail($merchant);

        return $merchant->toArray();
    }

    public function liveDisable($id)
    {
        $merchant = $this->repo->findOrFailPublic($id);

        if ($merchant->isActivated() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED);
        }

        if ($merchant->isLive() === false)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE);
        }

        $merchant->liveDisable();

        $this->repo->saveOrFail($merchant);

        return $merchant->toArray();
    }
}
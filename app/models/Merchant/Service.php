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

    public function register(array $input)
    {
        $merchant = $this->create($input);

        $merchantId = $merchant->getKey();

        $keyData = (new Key\Core)->createAndReturnWithSecret($merchantId);

        $data = $merchant->toArray();

        $data['key'] = $keyData;

        return $data;
    }

    /**
     * Creates a merchant and saves in database
     *
     * @param  array            $input
     * @return Merchant\Enitty
     */
    public function create(array $input)
    {
        $merchantIdArr['id'] = $input['id'];

        $merchant = (new Merchant\Entity)->build($input);

        $this->merchantRepository->saveOrFail($merchant);

        $merchantBalance = new Merchant\Balance($merchantIdArr);

        $this->merchantRepository->saveOrFail($merchantBalance);

        return $merchant;
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

        $p = $plan->toArrayPublic();
        $id = $merchant->getPricingPlanId();

        return $p;
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
}
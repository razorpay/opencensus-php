<?php

namespace Models\Merchant;

use Models\Base;
use Models\Merchant;
use Models\Key;

class Service extends Base\Service
{
    protected $keyRepository;

    protected $merchantRepository;

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
        $merchantId['id'] = $input['id'];

        $merchant = (new Merchant\Entity)->build($merchantId);

        $this->merchantRepository->saveOrFail($merchant);

        return $merchant;
    }

    public function updateKey($id, array $input)
    {
        $old = $this->keyRepository->findOrFail($id);

        $keyCore = new Key\Core;

        $delay = false;

        if (isset($input['delay_roll']))
        {
            $delay = ($input['delay_roll'] === '1') ? true : false;
        }

        unset($input['delay_roll']);

        $keyCore->setExpired($old, $delay);

        $keyData = $keyCore->createAndReturnWithSecret($input['merchant_id']);

        $keysData['old'] = $old->toArray();

        $keysData['new'] = $keyData;

        return $keysData;
    }

    public function fetchKeys($merchantId)
    {
        return $this->keyRepository->getKeysForMerchant($merchantId);
    }
}
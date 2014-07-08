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

    public function create(array $input)
    {
        $merchantId['id'] = $input['merchant_id'];

        $merchant = (new Merchant\Entity)->build($input);

        $key = (new Key\Entity)->build($key);

        $this->merchantRepository->saveOrFail($merchant);

        $this->keyRepository->createOrFail($keyData);
    }

    public function updateKey(array $input)
    {
        $old = $this->keyRepository->find($input['old_id']);

        if ($old === null)
        {
            return ['status' => false];
        }

        //
        // @todo: remove the magic number
        //
        $time = ($input['delay_roll'] == 'true') ? 86400 : 0;

        $old->setExpired($time);
        $old->save();

        unset($input['delay_roll']);
        unset($input['old_id']);

        try
        {
            $this->keyRepository->createOrFail($input);
        }
        catch (Exception $e)
        {
            return ['status' => false];
        }

        return ['status' => true];
    }

    public function fetchKeys($merchantId)
    {
        return $this->keyRepository->getKeysForMerchant($merchantId);
    }
}
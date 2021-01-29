<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Address;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const STAKEHOLDER_CREATE_MUTEX_PREFIX = 'api_stakeholder_create_';

    public function syncMerchantDetailFieldsToStakeholder(Detail\Entity $merchantDetails, array $input)
    {
        $fields = Constants::MERCHANT_DETAILS_COMMON_EDITABLE_FIELDS;

        $stakeholderInput = [];

        foreach ($fields as $key => $val)
        {
            if (array_key_exists($val, $input) === true)
            {
                $stakeholderInput[$key] = $input[$val];
            }
        }

        if (empty($stakeholderInput) === true)
        {
            return;
        }

        $stakeholder = $this->createOrFetchStakeholder($merchantDetails);

        $this->trace->info(TraceCode::MERCHANT_SAVE_STAKEHOLDER_DETAILS, $stakeholderInput);

        $stakeholder->edit($stakeholderInput);

        $this->repo->stakeholder->saveOrFail($stakeholder);
    }

    public function create(string $merchantId, array $input): Entity
    {
        $this->trace->info(TraceCode::MERCHANT_CREATE_STAKEHOLDER_REQUEST, [
            'merchant_id' => $merchantId,
            'input'       => $input,
        ]);

        $stakeholders = $this->repo->stakeholder->fetchStakeholders($merchantId);
        if ($stakeholders->isNotEmpty() === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_STAKEHOLDER_ALREADY_EXISTS);
        }

        return $this->saveStakeholder(null, $merchantId, $input);
    }

    private function saveStakeholder($id, string $merchantId, array $input): Entity
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($id, $merchantId, $input) {
            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $merchantDetailInput = Helper::getMerchantDetailInput($input);

            $merchantDetailCore  = new Detail\Core;

            if (empty($merchantDetailInput) === false)
            {
                $merchantDetailCore->saveMerchantDetails($merchantDetailInput, $merchant);
            }

            $stakeholderInput = Helper::getStakeholderInput($input);

            if (empty($id) === false)
            {
                $stakeholder = $this->repo->stakeholder->findByIdAndMerchantId($id, $merchantId);
            }
            else
            {
                $merchantDetails = $merchantDetailCore->getMerchantDetails($merchant);
                $stakeholder = $this->createOrFetchStakeholder($merchantDetails);
            }

            $this->editStakeholder($stakeholder, $stakeholderInput);

            return $stakeholder;
        });
    }

    public function fetch(string $merchantId, string $id): Entity
    {
        return $this->repo->stakeholder->findByIdAndMerchantId($id, $merchantId);
    }

    public function update(string $merchantId, string $id, array $input): Entity
    {
        $this->trace->info(TraceCode::MERCHANT_UPDATE_STAKEHOLDER_REQUEST, [
            'merchant_id' => $merchantId,
            'input'       => $input,
            'id'          => $id,
        ]);

        return $this->saveStakeholder($id, $merchantId, $input);
    }

    protected function editStakeholder(Entity $stakeholder, $input)
    {
        if (isset($input[Constants::ADDRESSES]) === true)
        {
            $addressCore = new Address\Core;

            foreach ($input[Constants::ADDRESSES] as $addressArr)
            {
                $address = $this->repo->address->fetchPrimaryAddressOfEntityOfType($stakeholder, $addressArr[Address\Entity::TYPE]);

                if (empty($address) === true)
                {
                    $addressCore->create($stakeholder, $stakeholder->getEntity(), $addressArr);
                }
                else
                {
                    $addressCore->edit($address, $addressArr);
                }
            }
        }
        unset($input[Constants::ADDRESSES]);

        $stakeholder->edit($input);

        $this->repo->saveOrFail($stakeholder);
    }

    /**
     * Fetch stakeholder if exists else creates one
     *
     * @param Detail\Entity $merchantDetails
     * @return Entity
     */
    public function createOrFetchStakeholder(Detail\Entity $merchantDetails)
    {
        $stakeholder = $merchantDetails->stakeholder;

        if ($stakeholder === null)
        {
            $this->trace->info(
                TraceCode::STAKEHOLDER_DOES_NOT_EXIST,
                [
                    'merchant_id' => $merchantDetails->getMerchantId(),
                ]
            );

            $stakeholder = $this->createStakeholderFromMerchantDetails($merchantDetails);

            $merchantDetails->setRelation(Detail\Entity::STAKEHOLDER, $stakeholder);
        }

        return $stakeholder;
    }

    public function checkIfStakeholderExists(Detail\Entity $merchantDetails)
    {
        $stakeholder = $merchantDetails->stakeholder;

        return (empty($stakeholder) === false);
    }

    protected function createStakeholderFromMerchantDetails(Detail\Entity $merchantDetails): Entity
    {
        $mutexResource = self::STAKEHOLDER_CREATE_MUTEX_PREFIX . $merchantDetails->getMerchantId();

        return $this->app['api.mutex']->acquireAndRelease($mutexResource, function () use ($merchantDetails) {

            // this is required if another thread gets the lock immediately
            // after the previous thread releases the lock. So we refresh the relation and if found, we return
            $merchantDetails->load(Detail\Entity::STAKEHOLDER);

            $stakeholder = $merchantDetails->stakeholder;

            if (empty($stakeholder) === false)
            {
                return $stakeholder;
            }

            $stakeholder = new Entity;

            $stakeholder->generateId();

            $fields = Constants::MERCHANT_DETAILS_COMMON_FIELDS;

            $stakeholderInput = [];

            foreach ($fields as $key => $val)
            {
                if (empty($merchantDetails[$val]) === false)
                {
                    $stakeholderInput[$key] = $merchantDetails[$val];
                }
            }

            $this->trace->info(TraceCode::MERCHANT_CREATE_STAKEHOLDER_DETAILS, $stakeholderInput);

            $stakeholder->build($stakeholderInput);

            $this->repo->stakeholder->saveOrFail($stakeholder);

            return $stakeholder;
        });
    }
}

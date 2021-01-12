<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Merchant\Detail;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    const STAKEHOLDER_CREATE_MUTEX_PREFIX = 'api_stakeholder_create_';

    public function syncMerchantDetailFieldsToStakeholder(Detail\Entity $merchantDetails, array $input)
    {
        $stakeholder = $this->createOrFetchStakeholder($merchantDetails);

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

        $this->trace->info(TraceCode::MERCHANT_SAVE_STAKEHOLDER_DETAILS, $stakeholderInput);

        $stakeholder->edit($stakeholderInput);

        $this->repo->stakeholder->saveOrFail($stakeholder);
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

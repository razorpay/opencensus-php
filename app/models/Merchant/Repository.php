<?php

namespace Models\Merchant;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'Merchant';

    public function getBalanceLockForUpdate($id)
    {
        return Merchant\Balance::lockForUpdate()->findOrFail($id);
    }

    public function updateBalance($balance)
    {
        $balance->saveOrFail();
    }

    public function getEscrowBalanceLockForUpdate()
    {
        $apiId = 'dd';

        return $this->getBalanceLockForUpdate($apiId);
    }

    public function getPricingPlan($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return $merchant->pricingPlan();
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail($entity, array $options = array())
    {
        if (get_class($entity) === 'Models\Merchant\Balance')
        {
            $entity->saveOrFail($options);
            return;
        }

        parent::saveOrFail($entity, $options);
    }
}
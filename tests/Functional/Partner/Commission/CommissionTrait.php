<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Models\Pricing\Calculator\Base;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;

trait CommissionTrait
{
    use PartnerTrait;

    /**
     * include DbEntityFetchTrait to use this function
     *
     * @param $sourceId
     */
    public function getCommissionsForSourceEntity($sourceId)
    {
        $commissions = $this->getDbEntities('commission');

        $commissions = $commissions->reject(
            function ($commission) use ($sourceId)
            {
                return ($commission['source_id'] === $sourceId);
            });

        return $commissions;
    }

    public function createEntityOrigin($entityType,
                                       $entityId,
                                       $originType = 'application',
                                       $originId = Constants::DEFAULT_PLATFORM_APP_ID)
    {
        return $this->fixtures->create(
            'entity_origin',
            [
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'origin_type'     => $originType,
                'origin_id'       => $originId,
            ]
        );
    }

    protected function getFee(int $amount, float $rate)
    {
        return ($this->getFeeWithoutTax($amount, $rate) + $this->getTax($amount, $rate));
    }

    protected function getFeeWithoutTax(int $amount, float $rate)
    {
        return ($amount * ($rate / 100));
    }

    protected function getTax(int $amount, float $rate)
    {
        if ($amount < Base::CARD_TAX_CUT_OFF)
        {
            return 0;
        }

        return ($this->getFeeWithoutTax($amount, $rate) * Constants::GST_RATE / 100);
    }
}

<?php

namespace RZP\Tests\Functional\Partner\Commission;

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
        return ($this->getFeeWithoutTax($amount, $rate) * Constants::GST_RATE / 100);
    }
}

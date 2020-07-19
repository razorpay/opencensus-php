<?php

namespace RZP\Models\UpiMandate;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = 'upi_mandate';

    public function findByOrderId(string $orderId)
    {
        $upiMandate = $this->newQuery()
                           ->where(Entity::ORDER_ID, '=', $orderId)
                           ->first();

        return $upiMandate;
    }

    public function findByTokenId(string $tokenId): Entity
    {
        $upiMandate = $this->newQuery()
                           ->where(Entity::TOKEN_ID, '=', $tokenId)
                           ->firstOrFail();

        return $upiMandate;
    }

    public function findByUmn(string $umn): Entity
    {
        $upiMandate = $this->newQuery()
                           ->where(Entity::UMN, '=', $umn)
                           ->firstOrFail();

        return $upiMandate;
    }

    public function determineIdAndLiveOrTestModeForEntityWithUMN($umn)
    {
        $obj = $this->connection(Mode::LIVE)->newQuery()->where(Entity::UMN, $umn)->first();

        if ($obj !== null)
        {
            return [$obj->getId(), Mode::LIVE];
        }

        $obj = $this->connection(Mode::TEST)->newQuery()->where(Entity::UMN, $umn)->first();

        if ($obj !== null)
        {
            return [$obj->getId(), Mode::TEST];
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $this->connection(null);

        return ['', null];
    }
}

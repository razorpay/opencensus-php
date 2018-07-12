<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Constants\Mode;

class Repository extends Base\Repository
{
    protected $entity = 'qr_code';

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::REFERENCE, '=', $merchantReference)
                    ->first();
    }

    public function determineLiveOrTestModeForEntityByMerchantReference($merchantReference)
    {

        $obj = $this->connection(Mode::LIVE)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $this->connection(Mode::TEST)->findByMerchantReference($merchantReference);

        if ($obj !== null)
        {
            return Mode::TEST;
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

        return null;
    }
}

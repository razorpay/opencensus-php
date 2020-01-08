<?php

namespace RZP\Models\PaymentsUpi\Base;

use RZP\Models\Base;
use Illuminate\Database\MySqlConnection;

class Entity extends Base\PublicEntity
{
    public function getConnection()
    {
        $connection =  parent::getConnection();

        if ($connection instanceof MySqlConnection)
        {
            switch ($connection->getName())
            {
                case 'live':
                    return static::resolveConnection('payments_upi_live');

                case 'test':
                    return static::resolveConnection('payments_upi_test');
            }
        }

        return $connection;
    }
}

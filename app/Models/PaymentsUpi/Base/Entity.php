<?php

namespace RZP\Models\PaymentsUpi\Base;

use RZP\Models\Base;
use Illuminate\Database\MySqlConnection;

class Entity extends Base\PublicEntity
{
    const PAYMENTS_UPI_LIVE = 'payments_upi_live';
    const PAYMENTS_UPI_TEST = 'payments_upi_test';

    public function getConnectionName()
    {
        $name = parent::getConnectionName();

        switch ($name)
        {
            case 'live':
                return self::PAYMENTS_UPI_LIVE;
            case 'test':
                return self::PAYMENTS_UPI_TEST;
        }

        return $name;
    }

    public function getConnection()
    {
        $connection =  parent::getConnection();

        if ($connection instanceof MySqlConnection)
        {
            switch ($connection->getName())
            {
                case 'live':
                    return static::resolveConnection(self::PAYMENTS_UPI_LIVE);

                case 'test':
                    return static::resolveConnection(self::PAYMENTS_UPI_TEST);
            }
        }

        return $connection;
    }
}

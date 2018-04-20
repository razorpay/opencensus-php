<?php

namespace RZP\Tests\Unit\Database\Helpers;

use RZP\Base\Database\MySqlConnection as BaseMySqlConnection;

class MySqlConnection extends BaseMySqlConnection
{
    public function __set(string $name, $value)
    {
        $this->$name = $value;
    }
}

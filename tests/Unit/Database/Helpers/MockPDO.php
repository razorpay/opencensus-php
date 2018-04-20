<?php

namespace RZP\Tests\Unit\Database\Helpers;

use PDO;

/**
 * Mocks the pdo object so that an actual connection
 * is not established to the database.
 */
class MockPDO extends PDO
{
    public function __construct()
    {

    }
}

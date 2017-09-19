<?php

namespace RZP\Tests\Unit\Models\Base;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;

class UniqueIdEntityTest extends TestCase
{
    public function testVerifyUniqueIdWithInvalidId()
    {
        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $id = 'random';

        UniqueIdEntity::verifyUniqueId($id);
    }

    public function testVerifyUniqueIdWithValidId()
    {
        $id = 'validId1234567';

        UniqueIdEntity::verifyUniqueId($id);
    }
}

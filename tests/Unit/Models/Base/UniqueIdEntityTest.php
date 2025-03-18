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

    public function testBase62Manual()
    {
        $inputA = '9999';
        $expectedOutputA = '2bH';
        $this->assertEquals($expectedOutputA, UniqueIdEntity::base62Manual($inputA));

        $inputB = '999999999999';
        $expectedOutputB = 'HbXm5a3';
        $this->assertEquals($expectedOutputB, UniqueIdEntity::base62Manual($inputB));

        $inputC = '61';
        $expectedOutputC = 'z';
        $this->assertEquals($expectedOutputC, UniqueIdEntity::base62Manual($inputC));
    }

    public function testHexToDecimal()
    {
        $inputA = '1a3';
        $expectedOutputA = '419';
        $this->assertEquals($expectedOutputA, UniqueIdEntity::hexToDecimal($inputA));

        $inputB = '8ed38ecc0a134a0ea8e51856cc4dc26f';
        $expectedOutputB = '189848846306435943443416045234282152559 ';
        $this->assertEquals($expectedOutputB, UniqueIdEntity::hexToDecimal($inputB));

        $inputC = 'FFFF';
        $expectedOutputC = '65535';
        $this->assertEquals($expectedOutputC, UniqueIdEntity::hexToDecimal($inputC));
    }
}

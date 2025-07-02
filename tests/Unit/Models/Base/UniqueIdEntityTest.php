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
        $expectedOutputB = '189848846306435943443416045234282152559';
        $this->assertEquals($expectedOutputB, UniqueIdEntity::hexToDecimal($inputB));

        $inputC = 'FFFF';
        $expectedOutputC = '65535';
        $this->assertEquals($expectedOutputC, UniqueIdEntity::hexToDecimal($inputC));
    }

    public function testEncodeData()
    {
        // UTF-8 to ISO-8859-1 conversion
        $utf8String = 'Hello World!';
        $isoString = UniqueIdEntity::encodeData($utf8String, 'ISO-8859-1', 'UTF-8');
        $convertedBack = UniqueIdEntity::encodeData($isoString, 'UTF-8', 'ISO-8859-1');
        $this->assertSame($utf8String, $convertedBack);


        // with empty string
        $emptyString = '';
        $encodedEmpty = UniqueIdEntity::encodeData($emptyString, 'ISO-8859-1', 'UTF-8');
        $this->assertSame('', $encodedEmpty);


        // with special characters
        $specialChars = '!@#$%^&*()_+{}|:"<>?';
        $encodedSpecial = UniqueIdEntity::encodeData($specialChars, 'ISO-8859-1', 'UTF-8');
        $decodedSpecial = UniqueIdEntity::encodeData($encodedSpecial, 'UTF-8', 'ISO-8859-1');
        $this->assertSame($specialChars, $decodedSpecial);


        // with numbers
        $numbers = '1234567890';
        $encodedNumbers = UniqueIdEntity::encodeData($numbers, 'ISO-8859-1', 'UTF-8');
        $decodedNumbers = UniqueIdEntity::encodeData($encodedNumbers, 'UTF-8', 'ISO-8859-1');
        $this->assertSame($numbers, $decodedNumbers);
    }
}

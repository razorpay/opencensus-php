<?php

namespace RZP\Tests\Unit\Models\Merchant\Detail;

use Closure;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Merchant\Balance\BalanceConfig;
use RZP\Exception\BadRequestValidationFailureException;

class BalanceConfigValidationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BalanceConfigValidationTestData.php';

        parent::setUp();
    }

    public function testCreateBalanceConfigEntityInvalidBalanceType()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityBalanceIdMissing()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntitySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->successTest(
            $input, $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityInvalidTransactionFlowForPrimary()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityInvalidTransactionFlowForBanking()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityBankingSuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->successTest(
            $input, $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityInvalidTransactionFlow()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    public function testCreateBalanceConfigEntityTransactionFlowNotArray()
    {
        $data = $this->testData[__FUNCTION__];

        $input = $data['input'];

        $this->exceptionTest(
            $input, BadRequestValidationFailureException::class,
            $data['expected'], function () use ($input) {
                (new BalanceConfig\Validator)->validateCreateBalanceConfig($input);
            }
        );
    }

    protected function successTest($input, $expected, Closure $function = null)
    {
        $actual = $function($input);

        $this->assertArraySelectiveEquals($expected, [$actual]);
    }

    protected function exceptionTest($input, $exClass, $exMessage, Closure $function = null)
    {
        $this->expectException($exClass);

        $this->expectExceptionMessage($exMessage);

        $function($input);
    }
}

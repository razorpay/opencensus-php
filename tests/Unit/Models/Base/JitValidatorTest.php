<?php

namespace Unit\Models\Base;

use RZP\Base\JitValidator;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\Validator as PayoutValidator;
use RZP\Exception\BadRequestValidationFailureException;

class JitValidatorTest extends TestCase
{
    public function testValidatorsFunctionalityForJitValidator()
    {
        try
        {
            (new JitValidator())->rules(['amount' => 'required|int|min:100'])
                                ->caller(new PayoutValidator())
                                ->setStrictFalse()
                                ->input(['amount' => 1000000000000])
                                ->validators(['amount'])
                                ->validate();
        }
        catch (\Throwable $exception)
        {
            $this->assertExceptionClass($exception, BadRequestValidationFailureException::class);

            $this->assertEquals('The amount may not be greater than 10000000000.', $exception->getMessage());
        }
    }

    public function testJitValidatorWithoutValidators()
    {
        $caughtException = false;

        try
        {
            (new JitValidator())->rules(['amount' => 'required|int|min:100'])
                                ->caller(new PayoutValidator())
                                ->setStrictFalse()
                                ->input(['amount' => 1000000000000])
                                ->validate();
        }
        catch (\Throwable $exception)
        {
            $caughtException =  true;
        }

        $this->assertFalse($caughtException);
    }
}

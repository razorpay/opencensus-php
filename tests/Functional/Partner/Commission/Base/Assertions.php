<?php

namespace RZP\Tests\Functional\Partner\Commission\Base;

use RZP\Tests\Functional\TestCase;

class Assertions extends TestCase
{
    public function runExceptionAssertions($testContext)
    {
        if (empty($testContext['action']['exception']) === true)
        {
            if (empty($testContext['post_action']['exception']) === false)
            {
                $this->assertNull($testContext['post_action']['exception']);
            }
        }
        else
        {
            $expectedExceptionData = $testContext['action']['exception'];

            $exception = $testContext['post_action']['exception'];

            $this->assertTrue(is_array($exception));

            if (empty($expectedExceptionData['class']) === false)
            {
                $this->assertEquals($expectedExceptionData['class'], $exception['class']);
            }

            if (empty($expectedExceptionData['message']) === false)
            {
                $this->assertEquals($expectedExceptionData['message'], $exception['message']);
            }
        }
    }

}

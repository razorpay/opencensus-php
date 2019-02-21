<?php

namespace Functional\Partner\Commission\Base;

use RZP\Tests\Functional\TestCase;

class Assertions extends TestCase
{
    public function runExceptionAssertions($testContext)
    {
        if (empty($testContext['action']['exception']) === true)
        {
            $this->assertNull($testContext['post_action']['exception']);
        }
        else
        {
            $expectedExceptionData = $testContext['action']['exception'];

            $exception = $testContext['post_action']['exception'];

            $this->assertTrue(is_object($exception));

            if (empty($expectedExceptionData['class']) === false)
            {
                $this->assertEquals($expectedExceptionData['class'], get_class($exception));
            }

            if (empty($expectedExceptionData['message']) === false)
            {
                $this->assertEquals($expectedExceptionData['message'], $exception->getMessage());
            }
        }
    }

}

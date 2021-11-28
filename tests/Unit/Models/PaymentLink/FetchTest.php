<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\PaymentLink;

class FetchTest extends BaseTest
{
    protected $datahelperPath   = '/Helpers/FetchTestData.php';

    /**
     * @dataProvider getData
     * @param      $method
     * @param      $args
     * @param null $exceptionClass
     * @param null $exceptionMessage
     */
    public function testFetch($method, $args, $exceptionClass=null, $exceptionMessage=null)
    {
        if (empty($exceptionClass) === false)
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertNull($this->callMethod($method, $args));
    }

    private function callMethod($method, array $args)
    {
        $fetch  = new PaymentLink\Fetch();
        $class  = new \ReflectionClass(get_class($fetch));
        $method = $class->getMethod($method);

        $method->setAccessible(true);

        return $method->invokeArgs($fetch, $args);
    }
}

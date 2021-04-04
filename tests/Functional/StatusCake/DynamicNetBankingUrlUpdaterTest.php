<?php

namespace RZP\tests\Functional\StatusCake;

use RZP\Tests\Functional\StatusCake\StatusCakeUpdaterTest;
use RZP\Tests\Functional\TestCase;

class DynamicNetBankingUrlUpdaterTest extends TestCase
{
    public function testCron()
    {
        $statusCakeUpdateObj = new StatusCakeUpdaterTest;

        $statusCakeUpdateObj->handle();
    }

    public function testErrorFlowCron()
    {
        $statusCakeUpdateObj = new StatusCakeUpdaterTest;

        try
        {
            $statusCakeUpdateObj->errorHandle();
        }
        catch (\Exception $exc)
        {
            self::assertEquals('Unknown Error Occured', $exc->getMessage());
        }
    }
}

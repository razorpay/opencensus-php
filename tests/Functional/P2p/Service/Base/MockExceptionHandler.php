<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Exception\Handler;

class MockExceptionHandler extends Handler
{
    public function setThrowExceptionInTesting(bool $set)
    {
        $this->throwExceptionInTesting = $set;
    }

    protected function isDebug()
    {
        return false;
    }
}

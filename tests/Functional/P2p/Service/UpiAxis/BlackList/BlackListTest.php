<?php

namespace Functional\P2p\Service\UpiAxis\BlackList;

use RZP\Exception\RuntimeException;

class BlackListTest extends \RZP\Tests\P2p\Service\UpiAxis\TestCase
{
    public function testCreate()
    {
        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage("Not implemented, processor Implementation is on the way");

        $response = $helper->create();
    }

    public function testRemove()
    {
        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $this->expectExceptionMessage(RuntimeException::class);

        $this->expectExceptionMessage("Not implemented, processor Implementation is on the way");

        $response  = $helper->remove();
    }

    public function testFetch()
    {
        $helper = $this->getBlackListHelper()->setMerchantOnAuth(true);

        $this->expectExceptionMessage(RuntimeException::class);

        $this->expectExceptionMessage("Not implemented, service Implementation is on the way");

        $response = $helper->fetchAll();
    }
}

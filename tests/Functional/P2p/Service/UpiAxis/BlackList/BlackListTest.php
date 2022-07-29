<?php

namespace Functional\P2p\Service\UpiAxis\BlackList;

use RZP\Exception\RuntimeException;
use RZP\Models\P2p\BlackList\Entity;

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

    public function testEntity()
    {
        $entity         = new Entity();
        $entityClass    = $entity->getEntity();
        $entityName     = $entityClass->getP2pEntityName();
        $this->assertEquals('blacklist', $entityName);
    }
}

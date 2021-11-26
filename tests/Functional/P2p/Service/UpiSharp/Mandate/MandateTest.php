<?php

namespace RZP\Tests\P2p\Service\UpiSharp\Mandates;

use RZP\Exception\RuntimeException;
use RZP\Tests\P2p\Service\UpiSharp\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\TransactionTrait;

class MandatesTest extends TestCase
{
    use TransactionTrait;

    public function testFetchAll()
    {
        $helper = $this->getMandatesHelper();

        $this->expectException(RuntimeException::class);

        $helper->fetchAll();

    }

}

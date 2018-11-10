<?php

namespace RZP\Tests\P2p\Service\BankAccount;

use RZP\Tests\P2p\Service\TestCase;

class BankAccountTest extends TestCase
{
    public function testFetchBanks()
    {
        $helper = $this->getBankAccountHelper();

        $helper->withSchemaValidated();

        $helper->fetchBanks();
    }
}

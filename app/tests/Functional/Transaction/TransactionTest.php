<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;

class TransactionTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testDummy()
    {
        // apparently you need to have a test per test file!
    }
}
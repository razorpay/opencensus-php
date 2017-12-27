<?php

namespace RZP\Tests\Functional\Batch;

class LinkedAccountTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/LinkedAccountTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    /**
     * Tests linked account batch creation.
     *
     * Asserts following cases:
     * - If no account_id columns in row, then it creates new entity
     * - If invalid account_id column in row, then it errors for that row
     * - If valid account_id column in row, then it patches only bank account
     *   details using the rows value
     *
     * @return void
     */
    public function testCreateBatch()
    {
        // TODO
    }
}

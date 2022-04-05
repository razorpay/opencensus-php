<?php

namespace RZP\Tests\Functional\Batch;

use Illuminate\Support\Facades\Queue;

use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Batch;
use RZP\Jobs\Batch as BatchJob;

class VirtualBankAccountTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VirtualBankAccountTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $terminalAttributes = [
            'gateway'               => Gateway::BT_DASHBOARD,
            'merchant_id'           => '10000000000000',
            'gateway_merchant_id'   => 'ROHI',
            'gateway_merchant_id2'  => 'TKES',
            'type'                  => [
                Type::NON_RECURRING       => '1',
                Type::NUMERIC_ACCOUNT     => '1',
            ]
        ];
        $this->fixtures->on('test')->create('terminal:bank_account_terminal', $terminalAttributes);
    }

    public function testCreateBatchOfVirtualBankAccountTypeQueued()
    {
        Queue::fake();

        $entries = $this->getDefaultVirtualAccountFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testCreateBatchOfVirtualBankAccountTypeStatus()
    {
        $entries = $this->getDefaultVirtualAccountFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        // Gets last entity (Post queue processing) and asserts attributes
        $entities = $this->getLastEntity('batch', true);
        $this->assertEquals(4, $entities['success_count']);
        $this->assertEquals(2, $entities['failure_count']);

        // Processing should have happened immediately in tests as
        // queue are sync basically.

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultVirtualAccountFileEntries()
    {
        return [
            [
                Batch\Header::VA_CUSTOMER_ID      => '',
                Batch\Header::VA_CUSTOMER_NAME    => 'test',
                Batch\Header::VA_CUSTOMER_CONTACT => '99999966',
                Batch\Header::VA_CUSTOMER_EMAIL   => 'test@test.test',
                Batch\Header::VA_DESCRIPTOR       => '99999666',
                Batch\Header::VA_DESCRIPTION      => 'random description',
                Batch\Header::VA_NOTES            => '{"a": "b"}',
            ],
            [
                Batch\Header::VA_CUSTOMER_ID      => '',
                Batch\Header::VA_CUSTOMER_NAME    => 'test 3',
                Batch\Header::VA_CUSTOMER_CONTACT => '9999997777',
                Batch\Header::VA_CUSTOMER_EMAIL   => 'test3@test.test',
                Batch\Header::VA_DESCRIPTOR       => '99997777',
                Batch\Header::VA_DESCRIPTION      => 'random description',
                Batch\Header::VA_NOTES            => '',
            ],
            [
                Batch\Header::VA_CUSTOMER_ID      => 'cust_100000customer',
                Batch\Header::VA_CUSTOMER_NAME    => '',
                Batch\Header::VA_CUSTOMER_CONTACT => '',
                Batch\Header::VA_CUSTOMER_EMAIL   => '',
                Batch\Header::VA_DESCRIPTOR       => '99999888',
                Batch\Header::VA_DESCRIPTION      => null,
                Batch\Header::VA_NOTES            => null,
            ],
            [
                Batch\Header::VA_CUSTOMER_ID      => 'cust_100000customer',
                Batch\Header::VA_CUSTOMER_NAME    => '',
                Batch\Header::VA_CUSTOMER_CONTACT => '',
                Batch\Header::VA_CUSTOMER_EMAIL   => '',
                Batch\Header::VA_DESCRIPTOR       => '99999999',
                Batch\Header::VA_DESCRIPTION      => 'random description',
                Batch\Header::VA_NOTES            => '{"a": "b", "c": "d"}',
            ],
            // Following should fail
            [
                Batch\Header::VA_CUSTOMER_ID      => 'cust_100DoesntExist',
                Batch\Header::VA_CUSTOMER_NAME    => '',
                Batch\Header::VA_CUSTOMER_CONTACT => '',
                Batch\Header::VA_CUSTOMER_EMAIL   => '',
                Batch\Header::VA_DESCRIPTOR       => '999999999',
                Batch\Header::VA_DESCRIPTION      => 'random description',
                Batch\Header::VA_NOTES            => '',
            ],
            [
                Batch\Header::VA_CUSTOMER_ID      => '',
                Batch\Header::VA_CUSTOMER_NAME    => 'test 2',
                Batch\Header::VA_CUSTOMER_CONTACT => '+919999997777',
                Batch\Header::VA_CUSTOMER_EMAIL   => 'test2@test.test',
                Batch\Header::VA_DESCRIPTOR       => '+919999997777',
                Batch\Header::VA_DESCRIPTION      => 'random description',
                Batch\Header::VA_NOTES            => '',
            ],
        ];
    }
}

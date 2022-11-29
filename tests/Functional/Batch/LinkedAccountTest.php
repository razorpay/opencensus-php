<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch\Header;
use RZP\Models\Merchant;
use RZP\Tests\Functional\TestCase;

class LinkedAccountTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/LinkedAccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

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
        // - ^
        // - Also move testCreateLinkedAccountBatch to this file
    }

    public function testBatchValidationLinkedAccountForMutualFundDistributorMerchant()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'category' => Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->createAndPutExcelFileInRequest([], __FUNCTION__);

        $this->startTest();
    }

    public function testCreateLinkedAccountCreateBatchForMutualFundDistributorMerchant()
    {
        $entries = $this->getFileEntriesForLinkedAccountCreateBatch();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->fixtures->merchant->edit('10000000000000', [
            'category' => Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category'][0],
            'category2' =>  Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED['category2'][0]]);

        $this->startTest();
    }

    protected function getFileEntriesForLinkedAccountCreateBatch()
    {
        return [
            [
                Header::ACCOUNT_NAME        => 'LA_1',
                Header::ACCOUNT_EMAIL       => 'la.1@rzp.com',
                Header::DASHBOARD_ACCESS    => 0,
                Header::CUSTOMER_REFUNDS    => 0,
                Header::BUSINESS_NAME       => 'Business',
                Header::BUSINESS_TYPE       => 'ngo',
                Header::IFSC_CODE           => 'SBIN0000002',
                Header::ACCOUNT_NUMBER      => '999888777666',
                Header::BENEFICIARY_NAME    => 'Beneficiary',
            ],
            [
                Header::ACCOUNT_NAME        => 'LA_2',
                Header::ACCOUNT_EMAIL       => 'la.2@rzp.com',
                Header::DASHBOARD_ACCESS    => 1,
                Header::CUSTOMER_REFUNDS    => 1,
                Header::BUSINESS_NAME       => 'Another business',
                Header::BUSINESS_TYPE       => 'individual',
                Header::IFSC_CODE           => 'CNRB0000002',
                Header::ACCOUNT_NUMBER      => '9876543210',
                Header::BENEFICIARY_NAME    => 'Another beneficiary',
            ],
        ];
    }
}

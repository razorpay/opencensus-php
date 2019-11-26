<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class TerminalCreationBulkTest extends TestCase
{
    use BatchTestTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TerminalCreationBulkTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testBulkTerminalCreationValidateFile()
    {
        $this->ba->proxyAuth();

        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $this->startTest();
    }

    public function testBulkTerminalCreation()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['processed_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $count = $batch['success_count'];

        $terminals = $this->getEntities('terminal', ['count' => $count], true);

        $this->assertEquals(
            $terminals['items'][0][Terminal\Entity::MERCHANT_ID],
            $entries[0][Batch\Header::TERMINAL_CREATION_MERCHANT_ID]
        );
        $this->assertEquals(
            $terminals['items'][0][Terminal\Entity::CATEGORY],
            $entries[0][Batch\Header::TERMINAL_CREATION_CATEGORY]
        );
        $this->assertEquals(
            $terminals['items'][0][Terminal\Entity::GATEWAY_MERCHANT_ID],
            $entries[0][Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID]
        );
        $this->assertEquals(
            $terminals['items'][0][Terminal\Entity::TPV],
            $entries[0][Batch\Header::TERMINAL_CREATION_TPV]
        );

        $this->assertInputFileExistsForBatch($response[Batch\Entity::ID]);
        $this->assertOutputFileExistsForBatch($response[Batch\Entity::ID]);
    }

    protected function getDefaultFileEntries()
    {
        return [
            [
                Batch\Header::TERMINAL_CREATION_MERCHANT_ID          => '10NodalAccount',
                Batch\Header::TERMINAL_CREATION_GATEWAY              => 'BILLDESK',
                Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID  => '1253',
                Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2 => null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_ID  => null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_ACCESS_CODE  => null,
                Batch\Header::TERMINAL_CREATION_MC_MPAN              => null,
                Batch\Header::TERMINAL_CREATION_VISA_MPAN            => null,
                Batch\Header::TERMINAL_CREATION_RUPAY_MPAN           => null,
                Batch\Header::TERMINAL_CREATION_VPA                  => null,
                Batch\Header::TERMINAL_CREATION_CATEGORY             => '8211',
                Batch\Header::TERMINAL_CREATION_CARD                 => null,
                Batch\Header::TERMINAL_CREATION_NETBANKING           => null,
                Batch\Header::TERMINAL_CREATION_EMANDATE             => null,
                Batch\Header::TERMINAL_CREATION_EMI                  => null,
                Batch\Header::TERMINAL_CREATION_UPI                  => null,
                Batch\Header::TERMINAL_CREATION_BANK_TRANSFER        => null,
                Batch\Header::TERMINAL_CREATION_AEPS                 => null,
                Batch\Header::TERMINAL_CREATION_EMI_DURATION         => null,
                'type[non_recurring]'                                => '1',
                'type[pay]'                                          => '1',
                Batch\Header::TERMINAL_CREATION_MODE                 => null,
                Batch\Header::TERMINAL_CREATION_TPV                  => null,
                Batch\Header::TERMINAL_CREATION_INTERNATIONAL        => null,
                Batch\Header::TERMINAL_CREATION_CORPORATE            => null,
                Batch\Header::TERMINAL_CREATION_EXPECTED             => null,
                Batch\Header::TERMINAL_CREATION_EMI_SUBVENTION       => null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_ACQUIRER     => null,
                Batch\Header::TERMINAL_CREATION_NETWORK_CATEGORY     => null,
                Batch\Header::TERMINAL_CREATION_CURRENCY             => null,
                Batch\Header::TERMINAL_CREATION_ACCOUNT_NUMBER       => null,
                Batch\Header::TERMINAL_CREATION_IFSC_CODE            => null,
                Batch\Header::TERMINAL_CREATION_CARDLESS_EMI         => null,
                Batch\Header::TERMINAL_CREATION_PAYLATER             => null,
                Batch\Header::TERMINAL_CREATION_ENABLED              => null,
                Batch\Header::TERMINAL_CREATION_CAPABILITY           => null,
            ],
        ];
    }
}
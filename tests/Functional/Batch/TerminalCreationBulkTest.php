<?php

namespace RZP\Tests\Functional\Batch;

use Mockery;
use RZP\Models\Batch;
use RZP\Models\Terminal;
use RZP\Tests\Functional\TestCase;

class TerminalCreationBulkTest extends TestCase
{
    use BatchTestTrait;

    protected function setUp(): void
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

    public function testBulkTerminalCreationValidateInvalidFile()
    {
        $this->ba->adminAuth();

        $entries = $this->getDefaultFileEntries();

        $entries[1] = $entries[0];

        $entries[0][Batch\Header::TERMINAL_CREATION_PLAN_NAME] = 'plan1';
        $entries[1][Batch\Header::TERMINAL_CREATION_PLAN_NAME] = 'plan2';

        $this->createAndPutExcelFileInRequest($entries, __FUNCTION__);

        $response = $this->startTest();

        $errorGatewayMid = json_decode($response['error']['description'])[0];

        $this->assertEquals("1253", $errorGatewayMid);
    }

    public function testBulkTerminalCreation()
    {
        $this->markTestSkipped();

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
                Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD   =>  null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2  =>  null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_SECURE_SECRET       =>  'randomsecret123',
                Batch\Header::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2      =>  null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD      =>  null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE  =>  null,
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
                Batch\Header::TERMINAL_CREATION_OMNICHANNEL          => null,
                Batch\Header::TERMINAL_CREATION_BANK_TRANSFER        => null,
                Batch\Header::TERMINAL_CREATION_AEPS                 => null,
                Batch\Header::TERMINAL_CREATION_EMI_DURATION         => null,
                Batch\Header::TERMINAL_CREATION_TYPE                 => "non_recurring, pay",
                Batch\Header::TERMINAL_CREATION_MODE                 => null,
                Batch\Header::TERMINAL_CREATION_TPV                  => null,
                Batch\Header::TERMINAL_CREATION_INTERNATIONAL        => null,
                Batch\Header::TERMINAL_CREATION_CORPORATE            => null,
                Batch\Header::TERMINAL_CREATION_EXPECTED             => null,
                Batch\Header::TERMINAL_CREATION_EMI_SUBVENTION       => null,
                Batch\Header::TERMINAL_CREATION_GATEWAY_ACQUIRER     => null,
                Batch\Header::TERMINAL_CREATION_NETWORK_CATEGORY     => null,
                Batch\Header::TERMINAL_CREATION_CURRENCY             => null,
                Batch\Header::TERMINAL_CREATION_ACCOUNT_NUMBER       => '33153078043',
                Batch\Header::TERMINAL_CREATION_IFSC_CODE            => null,
                Batch\Header::TERMINAL_CREATION_CARDLESS_EMI         => null,
                Batch\Header::TERMINAL_CREATION_PAYLATER             => null,
                Batch\Header::TERMINAL_CREATION_ENABLED              => null,
                Batch\Header::TERMINAL_CREATION_STATUS               => null,
                Batch\Header::TERMINAL_CREATION_CAPABILITY           => null,
                Batch\Header::TERMINAL_CREATION_PLAN_NAME            => null,
            ],
        ];
    }

    public function testBulkTerminalCreationCompletelyMigratedBatchUpload()
    {
        $entries = $this->getDefaultFileEntries();

        $this->createAndPutCsvFileInRequest($entries, __FUNCTION__);

        $batch = Mockery::mock('RZP\Services\Mock\BatchMicroService')->makePartial();

        $this->app->instance('batchService', $batch);

        // data which would be accessible to batch service, sensitive fields should get encrypted
        $expectedOutputData = $this->testData[__FUNCTION__]['encrypted_file_data'];

        $batch->shouldReceive('isCompletelyMigratedBatchType')
            ->andReturnUsing(function (string $type)
            {
                return true;
            });

        $batch->shouldReceive('forwardToBatchServiceRequest')
            ->andReturnUsing(function (array $input, $merchant, $ufhFile) use ($expectedOutputData)
            {
                $rows = $this->parseCsvFile($ufhFile->getFullFilePath());
                // assert that sensitive headers(gateway secure secret,  account number) are actually encrypted
                $this->assertArraySelectiveEquals($rows, $expectedOutputData);

                return [
                    'id'               => 'Be6Ob5J8kaMV6o',
                    'created_at'       => 1590521524,
                    'updated_at'       => 1590521524,
                    'entity_id'        => '100000Razorpay',
                    'name'             =>  null,
                    'batch_type_id'    => 'terminal_creation',
                    'type'             => 'terminal_creation',
                    'is_scheduled'     => false,
                    'upload_count'     => 0,
                    'total_count'      => 1,
                    'failure_count'    => 0,
                    'success_count'    => 0,
                    'amount'           => 0,
                    'attempts'         => 0,
                    'status'           => 'created',
                    'processed_amount' => 0
                ];
            });

        $this->startTest();
    }

    public function testBulkTerminalCreationForBatchService()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);
    }

    public function testBulkTerminalCreationUpiAxis()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);
    }

    public function testBulkTerminalCreationUpiIcici()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'], 5), $response['items'][0]['terminal_id']);
    }

    public function testBulkTerminalCreationUpiIciciNegative()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    // to test gateway access code field
    public function testBulkTerminalNetbankingCub()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);
    }

    // to test paytm bulk creation
    public function testBulkTerminalPaytm()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);

        $this->assertEquals(0, $response['items'][0]['Card']);
        $this->assertEquals(1, $response['items'][0]['Netbanking']);
    }

    // to test terminal_password, terminal_password2, gateway_secure_secret, gateway_secure_secret2 fields
    public function testBulkTerminalAtom()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);
    }

    // to test mc_mpan, visa_mpan, rupay_mpan and type[bhsrat_qr] fields
    public function testBulkTerminalWorldline()
    {
        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $this->assertEquals(1, count($response['items']));

        $terminal = $this->getLastEntity('terminal', true);

        $this->assertEquals(base64_encode('4343123412341234'), $terminal['mc_mpan']); // mpan stored would be tokenized

        $this->assertEquals(substr($terminal['id'],5), $response['items'][0]['terminal_id']);
    }

    public function testBulkTerminalCreationInvalidInput()
    {
        $this->ba->batchAppAuth();

        $terminal = $this->fixtures->create('terminal:shared_upi_axis_terminal');

        $this->startTest();
    }
}

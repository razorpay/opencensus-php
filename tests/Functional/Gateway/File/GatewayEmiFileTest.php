<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Queue;
use Mockery;
use ZipArchive;
use Carbon\Carbon;
use RZP\Encryption;
use RZP\Jobs\BeamJob;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Emi as EmiMail;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Http\Controllers as FileStore;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GatewayEmiFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/GatewayEmiFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();

        $this->fixtures->merchant->enableEmi();
    }

    public function testGenerateEmiFile()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'axis_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileWithNoEmiPayments()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(EmiMail\Password::class);
        Mail::assertNotSent(EmiMail\File::class);
    }

    public function testGenerateEmiFileWithFileGenerationError()
    {
        Mail::fake();

        Excel::shouldReceive('create')->andThrow(new \Exception('file_generation_exception'));

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $this->ba->adminAuth();

        $content = $this->startTest();
    }

    public function testGenerateEmiFileWithMailSendError()
    {
        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateEmiFileForIndusInd()
    {
        Mail::fake();

        Queue::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4147720000000009', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'indusind_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testGenerateEmiFileForIndusIndForCardMasking()
    {
        Mail::fake();

        Queue::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4147720000000009', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'indusind_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $filestorecontrol = new FileStore\FileStoreController();

        $fileentity = $filestorecontrol->getFile($file['id']);

        $content = $fileentity->getOriginalContent();

        $data = $content["url"];

        $emiFileContents = (new ExcelImport)->toArray($data);

        // Check if the fields are set correctly
        $this->assertEquals('************0009', $emiFileContents[0][0]['card_pan']);
        $this->assertEquals('INDUSIND', $emiFileContents[0][0]['issuer']);
        $this->assertEquals('14%', $emiFileContents[0][0]['interest_rate']);
    }

    public function testGenerateEmiFileForKotak()
    {
        $this->markTestSkipped('Skipping this right now as it fails intermittently and affects other deverlopers. Will have to fix this soon.');

        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4280951000002433', 9, 1, 'capp_1000000custapp');

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'kotak_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForRbl()
    {
        Mail::fake();
        Queue::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5243730000000008', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'rbl_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testGenerateEmiFileForIcici()
    {
        Mail::fake();
        Queue::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4076510229001234', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertEquals(File\Status::FILE_SENT, $content[File\Entity::STATUS]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'icici_emi_file_sftp',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);

        Queue::assertPushed(BeamJob::class, 1);
    }

    public function testGenerateEmiFileForYesB()
    {
        Mail::fake();
        Queue::fake();

        $this->fixtures->create('emi_plan',
            [
                'id'                => '90101010101011',
                'duration'          => '9',
                'rate'              => '1400',
                'methods'           => 'creditcard',
                'bank'              => 'YESB',
                'min_amount'        => '300000',
                'merchant_id'       => '100000Razorpay',
            ]);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5318490005001234', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertEquals(File\Status::FILE_SENT, $content[File\Entity::STATUS]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'yes_emi_file_sftp',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Queue::assertPushed(BeamJob::class, 1);
    }

    public function testGenerateEmiFileForHsbc()
    {
        $this->prerequisitesForHsbcEmi();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hsbc_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $this->fixtures->merchant->disableEmi();

        Mail::assertQueued(EmiMail\Password::class);

        $filestorecontrol =  new FileStore\FileStoreController();
        $fileentity = $filestorecontrol->getFile($file['id']);
        $data = $fileentity->getOriginalContent();

        $monthYear = Carbon::now(Timezone::IST)->format('mY');

        $zip = new ZipArchive();
        $zip->open($data["url"]);
        $zip->setPassword('razorpay' . $monthYear);
        $pathinfo = pathinfo($data["url"]);
        $zip->extractTo($pathinfo['dirname']);
        $filename = $zip->getNameIndex(0);
        $zip->close();

        $emiFileContents = (new ExcelImport)->toArray($pathinfo['dirname'] . '/' . $filename);

        $this->assertEquals("9413", $emiFileContents[0][0]["last_4_digits_of_card_number"]);
        $this->assertEquals("5000", $emiFileContents[0][0]["amount"]);
        $this->assertEquals("123412341234", $emiFileContents[0][0]["rrn_number"]);

        //Mail::assertQueued(EmiMail\Password::class);
    }

    public function testGenerateEmiFileForHsbcNoTransaction()
    {
        $this->prerequisitesForHsbcEmi();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $this->assertNull($file);

        $this->fixtures->merchant->disableEmi();

        Mail::assertQueued(EmiMail\NoTransaction::class);
    }

    public function testGenerateEmiFileForSbi()
    {
        $input = [
            [
                'emi_duration' => 9,
            ],
            [
                'emi_duration' => 12,
            ]
        ];

        $this->prerequisitesForSbiEmi($input);

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $amountData = [58846,44894];

        $merchantNames = ['A WEIRD MERCH NT NAME W TH S PECIAL CHAR'];

        $cardNumbers = ['0000000000000006709'];

        $payment = $this->getLastPayment(true);

        $transactionDate = Carbon::createFromTimestamp($payment['authorized_at'], Timezone::IST)->format('dmY');

        $this->assertSbiEmiFileData($content, 3, $amountData, $merchantNames, $cardNumbers, [$transactionDate, $transactionDate],
            0, 1, 325, 166, 57, 102, 450, 'sbi_emi_file');

        Mail::assertQueued(EmiMail\File::class, function ($mail)
        {
            $this->assertEmpty($mail->attachments);

            return $mail->hasTo('emi.ops@sbicard.com');
        });
    }

    public function testGenerateEmiFileForSbiNce()
    {
        $input = [
            [
                'emi_duration'      => 3,
                'merchant_payback'  => 518,
                'order_amount'      => 500000,
                'discounted_amount' => 474100,
            ],
            [
                'emi_duration'      => 6,
                'merchant_payback'  => 600,
                'order_amount'      => 500000,
                'discounted_amount' => 470000,
            ]
        ];

        $this->fixtures->create('emi_plan',
            [
                'id'                => '30101010101013',
                'duration'          => $input[0]['emi_duration'],
                'rate'              => '1400',
                'methods'           => 'creditcard',
                'bank'              => 'SBIN',
                'min_amount'        => '300000',
                'merchant_id'       => '100000Razorpay',
                'merchant_payback'  => $input[0]['merchant_payback'],
            ]);

        $this->fixtures->create('emi_plan',
            [
                'id'                => '30101010101012',
                'duration'          => $input[1]['emi_duration'],
                'rate'              => '1400',
                'methods'           => 'creditcard',
                'bank'              => 'SBIN',
                'min_amount'        => '300000',
                'merchant_id'       => '100000Razorpay',
                'merchant_payback'  => $input[1]['merchant_payback'],
            ]);

        $this->prerequisitesForSbiNce($input);

        // trigger emi file before nce file
        $this->runRequestResponseFlow($this->testData['testGenerateEmiFileForSbi']);

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $amountData = array_column($input, 'discounted_amount');

        $merchantNames = ['A WEIRD MERCH NT NAME W TH S PECIAL CHAR'];

        $cardNumbers = ['0000000000000006709'];

        $payment = $this->getLastPayment(true);

        $transactionDate = Carbon::createFromTimestamp($payment['authorized_at'], Timezone::IST)->format('dmY');

        $this->assertSbiEmiFileData($content, 3, $amountData, $merchantNames, $cardNumbers, [$transactionDate, $transactionDate], 2,
            3, 36, 67, 17, 59, 200,  'sbi_nc_emi_file');

        Mail::assertQueued(EmiMail\File::class, function ($mail)
        {
            $this->assertEmpty($mail->attachments);

            return $mail->hasTo('emi.ops@sbicard.com');
        });
    }

    public function testGenerateEmiFileForSbiSecondFile()
    {
        $input = [
            [
                'emi_duration' => 9,
            ],
            [
                'emi_duration' => 12,
            ]
        ];

        $this->prerequisitesForSbiEmi($input);

        $testData = $this->testData['testGenerateEmiFileForSbi'];

        $this->startTest($testData);

        $fileStoreEntity = $this->getDbLastEntity('file_store')->toArray();

        $startString = 'GGCMS1';

        $length = strlen($startString);

        $this->assertEquals(substr($fileStoreEntity['name'], 0, $length), $startString);

        $this->startTest($testData);

        $fileStoreEntity = $this->getDbLastEntity('file_store')->toArray();

        $startString = 'GGCMS1';

        $length = strlen($startString);

        $this->assertEquals(substr($fileStoreEntity['name'], 0, $length), $startString);
    }

    public function testGenerateEmiFileForSbiWithBeamFailure()
    {
        $input = [
            [
                'emi_duration' => 9,
            ],
            [
                'emi_duration' => 12,
            ]
        ];

        $this->prerequisitesForSbiEmi($input);

        $this->mockBeamContentFunction(
            function (&$content, $action = '')
            {
                $content = [
                    'failed'   => [],
                    'job_name' => 'sbi_emi',
                    'success'  => null,
                ];
            }
        );

        $this->startTest();

        Mail::assertNotQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForSbiWithNoSbiEmiTerminal()
    {
        Mail::fake();

        Queue::fake();

        $merchantId = $this->fixtures->create('merchant_detail:valid_fields')['merchant_id'];

        $this->fixtures->edit('merchant_detail', $merchantId,[
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('iin',
            [
                'iin'           => '400666',
                'category'      => 'STANDARD',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'STATE BANK OF INDI',
                'issuer'        => 'SBIN',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ]);

        // creating a random terminal on same merchant to validate
        // that correct `sbi_emi` terminal is used to get MID
        $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
        ]);

        $this->fixtures->terminal->create([
            'merchant_id' => '10000000000000',
            'gateway'     => 'emi_sbi',
        ]);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4006660000086709', 9);

        $this->makeEmiPaymentOnCard('4006660000086709', 12);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $this->assertSbiEmiFileData($content, 1, [], [], [], [], 0, 1, null, null, null, null, null, "sbi_emi_file");
    }

    // One file would be encrypted and the other not encrypted
    protected function assertSbiEmiFileData($content, $rowCount, $amountData = [], $merchantNames = [], $cardNumbers = [], $transactionDates = [], $emiFileIndex = 0, $outputFileIndex = 1, $amountOffset = null, $nameOffset = null, $cardOffset = null, $dateOffset = null, $rowLength = null, $emiFileName = null)
    {
        $files = $this->getDbEntities('file_store')->toArray();

        $file = $files[$emiFileIndex];

        $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

        $encryptor = new Encryption\Handler(
            Encryption\Type::AES_GCM_ENCRYPTION,
            [
                'secret' => File\Processor\Emi\Sbi::TEST_ENCRYPTION_KEY,
                'iv'     => File\Processor\Emi\Sbi::TEST_ENCRYPTION_IV,
            ]
        );

        $fileContent = $encryptor->decrypt($fileContent);

        $this->checkSbiEmiFileContents($file, $fileContent, $content, $rowCount, $amountData, $merchantNames, $cardNumbers, $transactionDates, false, $amountOffset, $nameOffset, $cardOffset, $dateOffset, $rowLength, $emiFileName);

        $outputFile = $files[$outputFileIndex];

        $fileContent = file_get_contents('storage/files/filestore/' . $outputFile['location']);

        // For the output files, the card numbers would be replaced with 0s
        if (empty($cardNumbers) === false)
        {
            $cardNumbers = ['0000000000000006709'];
        }

        $this->checkSbiEmiFileContents($outputFile, $fileContent, $content, $rowCount, $amountData, $merchantNames, $cardNumbers, $transactionDates, true, $amountOffset, $nameOffset, $cardOffset, $dateOffset, $rowLength, 'sbi_emi_output_file');
    }

    protected function checkSbiEmiFileContents($file, $fileContent, $content, $rowCount, $amountData = [], $merchantNames = [], $cardNumbers = [], $transactionDates = [], $outputFile = false, $amountOffset, $nameOffset, $cardOffset, $dateOffset, $rowLength, $emiFileName)
    {
        $fileRows = explode("\r\n", $fileContent);

        $this->assertEquals($rowCount, count($fileRows));

        $amounts = [];
        $names = [];
        $cards = [];
        $dates = [];

        // Remove header
        unset($fileRows[0]);
        $fileRows = array_values($fileRows);


        foreach ($fileRows as $key => $row)
        {
            $amount = (int)substr($row, $amountOffset, 17);

            $amounts[] = $amount;
            $names[] = substr($row, $nameOffset, 40);
            $cards[] = substr($row, $cardOffset, 19);
            $dates[] = substr($row, $dateOffset, 8);

            $this->assertEquals($rowLength, strlen($row));
        }

        // Assert that the amounts in each rows are correct
        if (empty($amountData) !== true)
        {
            $this->assertArraySelectiveEquals(
                $amountData,
                $amounts
            );
        }

        // Assert that the merchant name in each row does not contain invalid characters
        if (empty($merchantNames) !== true)
        {
            $this->assertArraySelectiveEquals(
                $merchantNames,
                $names
            );
        }

        if (empty($cardNumbers) !== true)
        {
            $this->assertArraySelectiveEquals(
                $cardNumbers,
                $cards
            );
        }

        if(empty($transactionDates) !== true) {
            $this->assertArraySelectiveEquals(
                $transactionDates,
                $dates
            );
        }

        $expectedFileContent = [
            'type'        => $emiFileName,
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testGenerateEmiFileForScbl()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4028740000000001', 9);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'scbl_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForCiti()
    {
        Mail::fake();

        $this->fixtures->create('iin',
            [
                'iin'           => '554637',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'Citi Bank',
                'issuer'        => 'CITI',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ]);

        $this->makeEmiPaymentOnCard('5546370000099413', 12);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5546370000099413', 12);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'citi_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
//            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForBob()
    {
        Mail::fake();

        $this->createDependentEntities(null);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'bob_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForOneCard()
    {
        Mail::fake();

        $this->createDependentEntities('onecard');

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateEmiFileForFederal()
    {
        Mail::fake();

        Queue::fake();

        $this->createDependentEntitiesForFederal();

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'federal_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    protected function createDependentEntities($cobrandingPartner)
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
            'gateway'               => Payment\Gateway::EMI_SBI,
            'gateway_merchant_id'   => '250000002',
            'gateway_terminal_id'   => '38R00001',
            'enabled'               => 0,
            'emi'                   => 0,
        ]);

        $this->fixtures->create('emi_plan',
            [
                'id'                 => '90101010101011',
                'duration'           => '9',
                'rate'               => '1400',
                'methods'            => 'creditcard',
                'bank'               => 'BARB',
                'min_amount'         => '300000',
                'issuer_plan_id'     => '85009',
                'merchant_id'        => '100000Razorpay',
                'cobranding_partner' => $cobrandingPartner
            ]);

        // Enable EMI on iin
        $this->fixtures->create('iin',
            [
                'iin'                => '999999',
                'category'           => 'STANDARD',
                'network'            => 'Visa',
                'type'               => 'credit',
                'country'            => 'IN',
                'issuer_name'        => 'SBM Bank',
                'issuer'             => 'STCB',
                'cobranding_partner' => $cobrandingPartner,
                'emi'                => 1,
                'trivia'             => 'random trivia'
            ]);

        $card = $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'name'              =>  'test',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'STCB',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'credit',
                'vault'             =>  'visa',
                'iin'               =>  '0',
                'vault_token'       =>  'pay_sampletoken',
            ]
        );

        $this->fixtures->payment->create(
            [
                'merchant_id'      => '10000000000000',
                'amount'           => 1000,
                'currency'         => 'INR',
                'method'           => 'emi',
                'status'           => 'captured',
                'bank'             => 'BARB',
                'gateway'          => 'hdfc_debit_emi',
                'terminal_id'      => $terminal['id'],
                'card_id'          => $card['id'],
                'emi_plan_id'      => '90101010101011',
                'captured_at'      => Carbon::now(Timezone::IST)->getTimestamp(),
                'reference2'       => '1038203',
            ]
        );
    }

    protected function createDependentEntitiesForFederal()
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
            'gateway'               => Payment\Gateway::HITACHI,
            'gateway_merchant_id'   => '250000002',
            'gateway_terminal_id'   => '38R00001',
            'enabled'               => 0,
            'emi'                   => 0,
        ]);

        $this->fixtures->create('emi_plan',
            [
                'id'                 => '90101010101011',
                'duration'           => '9',
                'rate'               => '1400',
                'methods'            => 'creditcard',
                'bank'               => 'FDRL',
                'min_amount'         => '300000',
                'issuer_plan_id'     => '85009',
                'merchant_id'        => '100000Razorpay',
                'cobranding_partner' => null,
            ]);

        // Enable EMI on iin
        $this->fixtures->create('iin',
            [
                'iin'                => '999999',
                'category'           => 'STANDARD',
                'network'            => 'Visa',
                'type'               => 'credit',
                'country'            => 'IN',
                'issuer_name'        => 'FEDERAL BANK',
                'issuer'             => 'FDRL',
                'cobranding_partner' => null,
                'emi'                => 1,
                'trivia'             => 'random trivia'
            ]);

        $card = $this->fixtures->card->create(
            [
                'id'                =>  '100000003lcard',
                'name'              =>  'test',
                'expiry_month'      =>  '12',
                'expiry_year'       =>  '2100',
                'issuer'            =>  'FDRL',
                'network'           =>  'Visa',
                'last4'             =>  '1111',
                'type'              =>  'credit',
                'vault'             =>  'visa',
                'iin'               =>  '0',
                'vault_token'       =>  'pay_sampletoken',
            ]
        );

        $this->fixtures->payment->create(
            [
                'merchant_id'      => '10000000000000',
                'amount'           => 1000,
                'currency'         => 'INR',
                'method'           => 'emi',
                'status'           => 'captured',
                'bank'             => 'FDRL',
                'gateway'          => 'hitachi',
                'terminal_id'      => $terminal['id'],
                'card_id'          => $card['id'],
                'emi_plan_id'      => '90101010101011',
                'captured_at'      => Carbon::now(Timezone::IST)->getTimestamp(),
                'reference2'       => '1038203',
            ]
        );

        $this->fixtures->payment->create(
            [
                'merchant_id'      => '10000000000000',
                'amount'           => 1000,
                'currency'         => 'INR',
                'method'           => 'emi',
                'status'           => 'captured',
                'bank'             => 'FDRL',
                'gateway'          => 'hitachi',
                'terminal_id'      => $terminal['id'],
                'card_id'          => $card['id'],
                'emi_plan_id'      => '90101010101011',
                'captured_at'      => Carbon::now(Timezone::IST)->getTimestamp(),
                'reference2'       => '1038203',
            ]
        );
    }


    protected function makeEmiPaymentOnCard($card, $emiDuration,
        $save = 0, $appToken = null, $customerId = null, $merchantSubvention = false, $orderId = null, $discountedPrice = 0)
    {
        $this->mockSession($appToken);

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'emi';
        $payment['emi_duration'] = $emiDuration;
        $payment['card']['number'] = $card;
        $payment['save'] = $save;
        $payment['customer_id'] = $customerId;

        if(isset($orderId))
        {
            $payment['order_id'] = $orderId;
        }

        if ($merchantSubvention === true)
        {
            $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);
        }

        if($discountedPrice != 0)
        {
            $this->doAuthAndCapturePayment($payment, $payment['amount'], 'INR', $discountedPrice);
        }
        else
        {
            $this->doAuthAndCapturePayment($payment);
        }

    }

    protected function mockSession($appToken = null)
    {
        $data = array(
            'test_app_token' => $appToken,
        );

        $this->session($data);
    }

    protected function prerequisitesForHsbcEmi()
    {
        Mail::fake();

        Queue::fake();

        $this->fixtures->create('iin',
            [
                'iin'           => '554637',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'HSBC',
                'issuer'        => 'HSBC',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ]);

        $this->fixtures->create('emi_plan',
            [
                'id'                => '90101010101011',
                'duration'          => '9',
                'rate'              => '1400',
                'methods'           => 'creditcard',
                'bank'              => 'HSBC',
                'min_amount'        => '300000',
                'merchant_id'       => '100000Razorpay',
            ]);


        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5546370000099413', 9);

        $this->ba->adminAuth();
    }
    protected function prerequisitesForSbiNce($input)
    {
        $offer = $this->fixtures->create('offer:emi_subvention', [
            'issuer'          => 'SBIN',
            'payment_network' => null,
            'type'            => 'instant',
            'emi_durations'   => array_column($input, 'emi_duration'),
        ]);

        for($idx = 0; $idx < 2; $idx++ )
        {
            $order = $this->fixtures->order->createWithOffers($offer, [
                'amount'      => $input[0]['order_amount'],
                'force_offer' => true,
            ]);

            $input[$idx]['order_id'] = $order->getPublicId();
        }

        $this->prerequisitesForSbiEmi($input);
    }

    protected function prerequisitesForSbiEmi($input)
    {
        Mail::fake();

        Queue::fake();

        $merchantId = $this->fixtures->create(
            'merchant_detail:valid_fields',
            [
                'business_name' => 'A weird merch@nt name\'w!th s®pecial chars and > 40 chars'
            ]
        )['merchant_id'];

        $this->fixtures->create('terminal:shared_hitachi_terminal');

        $this->fixtures->create('gateway_rule', [
            'method'        => 'emi',
            'merchant_id'   => '100000Razorpay',
            'gateway'       => 'hitachi',
            'issuer'        => 'SBIN',
            'type'          => 'filter',
            'filter_type'   => 'select',
            'min_amount'    => 0,
            'group'         => 'routing_filter',
            'emi_subvention'=> 'customer',
            'step'          => 'authorization',
        ]);

        $this->fixtures->edit('merchant_detail', $merchantId,[
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('iin',
            [
                'iin'           => '400666',
                'category'      => 'STANDARD',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'STATE BANK OF INDI',
                'issuer'        => 'SBIN',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ]);

        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
            'gateway'               => Payment\Gateway::EMI_SBI,
            'gateway_merchant_id'   => '250000002',
            'gateway_terminal_id'   => '38R00001',
            'enabled'               => 0,
        ]);

        $this->fixtures->edit('terminal', $terminal->getId(),[
            'enabled'   => 1,
        ]);

        $this->ba->publicAuth();

        $order_id = isset($input[0]['order_id']) ? $input[0]['order_id'] : null;

        $discounted_amount = isset($input[0]['discounted_amount']) ? $input[0]['discounted_amount'] : 0;

        // Generated using luhn generator
        $this->makeEmiPaymentOnCard('4006660000086709', $input[0]['emi_duration'], 0 , null, null, false, $order_id, $discounted_amount);

        $payment = $this->getLastPayment(true);

        $this->assertEquals('hitachi', $payment['gateway']);

        $order_id = isset($input[1]['order_id']) ? $input[1]['order_id'] : null;

        $discounted_amount = isset($input[1]['discounted_amount']) ? $input[1]['discounted_amount'] : 0;

        $this->makeEmiPaymentOnCard('4006660000086709', $input[1]['emi_duration'], 0 , null, null, false, $order_id, $discounted_amount);

        $this->ba->adminAuth();
    }

    protected function mockBeamContentFunction($closure)
    {
        $beamServiceMock = Mockery::mock(\RZP\Services\Mock\BeamService::class, [$this->app])->makePartial();

        $service = $beamServiceMock
            ->shouldReceive('content')
            ->andReturnUsing($closure)
            ->mock();

        $this->app['beam']->setMockService($service);
    }

    protected function setCardPaymentMockResponse($mockedResponse)
    {
        $mock = Mockery::mock(CardPaymentService::class)->makePartial();

        $mock->shouldReceive([
            'fetchAuthorizationData' => $mockedResponse
        ]);

        $this->app->instance('card.payments', $mock);
    }
}

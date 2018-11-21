<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Queue;

use Carbon\Carbon;
use RZP\Jobs\BeamJob;
use RZP\Models\Payment;
use RZP\Models\Gateway\File;
use RZP\Mail\Emi as EmiMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class GatewayEmiFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/GatewayEmiFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockTokenex();

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
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForKotak()
    {
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

        Mail::assertQueued(EmiMail\Password::class);
        Mail::assertQueued(EmiMail\File::class);
    }

    public function testGenerateEmiFileForSbi()
    {
        Mail::fake();

        Queue::fake();

        $merchantId = $this->fixtures->create('merchant_detail:valid_fields')['merchant_id'];
        $this->fixtures->create('terminal:shared_hitachi_emi_terminal');
        $this->fixtures->create('terminal:shared_blade_terminal');

        $this->fixtures->edit('merchant_detail', $merchantId,[
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('iin',
            [
                'iin'           => '472642',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'STATE BANK OF INDI',
                'issuer'        => 'SBIN',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ]);

        $this->fixtures->create('iin',
            [
                'iin'           => '556763',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
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

        // creating a random terminal on same merchant to validate
        // that correct `sbi_emi` terminal is used to get MID
        $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
        ]);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5567630000002004', 9);

        $payment = $this->getLastPayment(true);

        $this->assertEquals('hitachi', $payment['gateway']);

        $this->makeEmiPaymentOnCard('4726426854804947', 12);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

        $fileRows = explode("\r\n", $fileContent);

        $this->assertEquals(3, count($fileRows));

        foreach ($fileRows as $row)
        {
            $this->assertEquals(450, strlen($row));
        }

        $expectedFileContent = [
            'type'        => 'sbi_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('general_test', BeamJob::class);
    }

    public function testGenerateEmiFileForSbiWithDuplicateSbiEmiTerminal()
    {
        Mail::fake();

        Queue::fake();

        $merchantId = $this->fixtures->create('merchant_detail:valid_fields')['merchant_id'];

        $this->fixtures->edit('merchant_detail', $merchantId,[
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('iin',
            [
                'iin'           => '472642',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
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

        // duplicate `sbi_emi` terminal
        $terminal = $this->fixtures->create('terminal', [
            'merchant_id'           => '10000000000000',
            'gateway'               => Payment\Gateway::EMI_SBI,
            'gateway_merchant_id'   => '250000003',
            'gateway_terminal_id'   => '38R00001',
            'enabled'               => 0,
        ]);

        $this->fixtures->edit('terminal', $terminal->getId(),[
            'enabled'   => 1,
        ]);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4726426854804947', 9);

        $this->makeEmiPaymentOnCard('4726426854804947', 12);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

        $fileRows = explode("\n", $fileContent);

        $this->assertEquals(1, count($fileRows));

        foreach ($fileRows as $row)
        {
            $this->assertEquals(450, strlen($row));
        }

        $expectedFileContent = [
            'type'        => 'sbi_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('general_test', BeamJob::class);
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
                'iin'           => '472642',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
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

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4726426854804947', 9);

        $this->makeEmiPaymentOnCard('4726426854804947', 12);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $fileContent = file_get_contents('storage/files/filestore/' . $file['location']);

        $fileRows = explode("\n", $fileContent);

        $this->assertEquals(1, count($fileRows));

        foreach ($fileRows as $row)
        {
            $this->assertEquals(450, strlen($row));
        }

        $expectedFileContent = [
            'type'        => 'sbi_emi_file',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('general_test', BeamJob::class);
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

    protected function makeEmiPaymentOnCard($card, $emiDuration,
        $save = 0, $appToken = null, $customerId = null, $merchantSubvention = false)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'emi';
        $payment['emi_duration'] = $emiDuration;
        $payment['card']['number'] = $card;
        $payment['save'] = $save;
        $payment['app_token'] = $appToken;
        $payment['customer_id'] = $customerId;

        if ($merchantSubvention === true)
        {
            $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);
        }

        $this->doAuthAndCapturePayment($payment);
    }
}

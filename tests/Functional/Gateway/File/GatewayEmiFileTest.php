<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;
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

        $this->ba->appAuth();

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

        Mail::assertSent(EmiMail\Password::class);
        Mail::assertSent(EmiMail\File::class);
    }

    public function testGenerateEmiFileWithNoEmiPayments()
    {
        Mail::fake();

        $this->ba->appAuth();

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

        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testGenerateEmiFileWithMailSendError()
    {
        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4111460212312338', 3);

        $this->ba->appAuth();

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

        $this->ba->appAuth();

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

        Mail::assertSent(EmiMail\Password::class);
        Mail::assertSent(EmiMail\File::class);
    }

    public function testGenerateEmiFileForKotak()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4280951000002433', 9, 1, 'capp_1000000custapp');

        $this->ba->appAuth();

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

        Mail::assertSent(EmiMail\Password::class);
        Mail::assertSent(EmiMail\File::class);
    }

    public function testGenerateEmiFileForRbl()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5243730000000008', 9);

        $this->ba->appAuth();

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

        Mail::assertSent(EmiMail\Password::class);
        Mail::assertSent(EmiMail\File::class);
    }

    public function testGenerateEmiFileForScbl()
    {
        Mail::fake();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4028740000000001', 9);

        $this->ba->appAuth();

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

        Mail::assertSent(EmiMail\Password::class);
        Mail::assertSent(EmiMail\File::class);
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

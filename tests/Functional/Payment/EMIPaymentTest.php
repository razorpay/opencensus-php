<?php

namespace RZP\Tests\Functional\Payment;

use Str;
use File;
use Mail;
use Excel;
use Queue;
use Mockery;
use ZipArchive;
use Carbon\Carbon;
use RZP\Jobs\BeamJob;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Entity as Payment;

class EMIPaymentTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $emiPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();
    }

    protected function setCardPaymentMockResponse($mockedResponse)
    {
        $mock = Mockery::mock(CardPaymentService::class)->makePartial();

        $mock->shouldReceive([
            'fetchAuthorizationData' => $mockedResponse
        ]);

        $this->app->instance('card.payments', $mock);
    }

    public function testEmiPaymentCreate()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $feeBreakup = $this->getEntities('fee_breakup',[], true);

        $this->assertEquals($feeBreakup['items'][1]['pricing_rule_id'], '1zE31zbybabab2');

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();
    }

    public function testS2SEmiPaymentCreate()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->merchant->enableEmi();
        $this->ba->privateAuth();
        $this->fixtures->merchant->addFeatures(['s2s']);
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';
        $this->payment['card'][ 'cryptogram_value'] = 'test';
        $this->payment['card'][ 'tokenised'] = true;
        $this->payment['card'][ 'last4'] = '1234';
        $this->payment['card'][ 'token_provider'] =  'PayU';

        $response = $this->doS2SPrivateAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $feeBreakup = $this->getEntities('fee_breakup',[], true);

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'authorized');

        $this->fixtures->merchant->disableEmi();
    }

    public function testS2SEmiPaymentLast4ValidationFailure()
    {
        $this->fixtures->merchant->enableEmi();
        $this->ba->privateAuth();
        $this->fixtures->merchant->addFeatures(['s2s']);

        $paymentArray = $this->getDefaultTokenPanPaymentArray();
        $paymentArray['amount'] = 500000;
        $paymentArray['method'] = 'emi';
        $paymentArray['emi_duration'] = 9;
        $paymentArray['card']['number'] = '41476700000006';

        $this->makeRequestAndCatchException(
            function() use ($paymentArray)
            {
                $this->doS2SPrivateAuthPayment($paymentArray);
            },
            \RZP\Exception\BadRequestValidationFailureException::class , 'The last4 field is required when method is emi and tokenised is true');
    }

    public function testEmiPaymentWithNewCardAndUserConsentTokenisation()
    {
        $this->mockSession();

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';
        $this->payment['save'] = 1;
        $this->payment['_']['library'] = 'checkoutjs';

        $content = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getLastEntity('token', true);
        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testEmiPaymentWithSavedCardAndUserConsentTokenisation()
    {
        $this->mockSession();

        $token = $this->getEntityById('token', '10000custgcard', true);

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['method'] = 'emi';
        $this->payment['amount'] = 500000;
        $this->payment['emi_duration'] = 9;
        $this->payment[Payment::CARD] = array('cvv'  => 111);
        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];
        $this->payment['user_consent_for_tokenisation'] = 1;
        $this->payment['_']['library'] = 'checkoutjs';

        $content = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getEntityById('token', '10000custgcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testEmiPaymentWithLocalSavedCardAndUserConsentTokenisation()
    {
        $token = $this->getEntityById('token', '100000custcard', true);

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $this->payment['method'] = 'emi';
        $this->payment['amount'] = 500000;
        $this->payment['emi_duration'] = 9;
        $this->payment[Payment::CARD] = array('cvv'  => 111);
        $this->payment[Payment::TOKEN] = $token[Payment::TOKEN];
        $this->payment['user_consent_for_tokenisation'] = 1;
        $this->payment['_']['library'] = 'checkoutjs';

        $content = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getEntityById('token', '100000custcard', true);

        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testEmiPaymentWithLocalNewCardAndUserConsentTokenisation()
    {
        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->payment[Payment::CUSTOMER_ID] = 'cust_100000customer';
        $this->payment['method'] = 'emi';
        $this->payment['amount'] = 500000;
        $this->payment['emi_duration'] = 9;
        $this->payment['save'] = 1;
        $this->payment['_']['library'] = 'checkoutjs';

        $content = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $token = $this->getLastEntity('token', true);
        $this->assertNotNull($token['acknowledged_at']);
    }

    public function testEmiPaymentCreatWithMerchantSpecificEmiPlan()
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 6;
        $this->payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $feeBreakup = $this->getEntities('fee_breakup',[], true);

        $this->assertEquals($feeBreakup['items'][1]['pricing_rule_id'], '1zE31zbybabab2');

        $this->assertEquals($payment['emi_plan_id'], '11101010101010');
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();
    }

    public function testMultipleEmiPayments()
    {
        $this->testEmiPaymentCreate();

        $this->testEmiPaymentCreate();
    }

    public function testSbiEmiPaymentWithEmiSbiTerminal()
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

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

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->terminal->create([
            'merchant_id' => '10000000000000',
            'gateway'     => 'emi_sbi',
        ]);

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4006660000086709', 9);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status' => 'captured',
                'method' => 'emi',
            ],
            $payment
        );

        $emiPlan = $this->getDbEntityById('emi_plan', $payment['emi_plan_id'])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'bank' => 'SBIN',
                'type' => 'credit',
            ],
            $emiPlan
        );
    }

    public function testSbiEmiPaymentWithoutEmiSbiTerminal()
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

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

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeRequestAndCatchException(
            function()
            {
                $this->makeEmiPaymentOnCard('4006660000086709', 9);
            },
            \RZP\Exception\BadRequestException::class);
    }

    public function testEmiFileGenerateForYesB()
    {
        Mail::fake();

        //Making transactions happen yesterday
        $yesterdayAtTen = Carbon::yesterday(Timezone::IST)->addHours(10)->timestamp;

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen);

        $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);

        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen, 0, null, null);

        $request = array(
            'method'  => 'POST',
            'url'     => '/emi/generate/excel',
            'content' => []);

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(count($content), 1);

        $this->assertEquals(true, File::exists($content['YESB']));

        $this->fixtures->merchant->disableEmi();

        $this->deleteAlltheGenerateFiles($content);

        unlink($content['YESB']);
    }

    public function testEmiFileGenerate()
    {
        $this->markTestSkipped();

        Mail::fake();

        $emiPlan = $this->emiPlan;

        //Making transactions happen yesterday
        $yesterdayAtTen = Carbon::yesterday(Timezone::IST)->addHours(10)->timestamp;

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        //ICICI Card
        $iciciPayment1 = $this->makeEmiPaymentOnCard('4076510000000033', 9, $yesterdayAtTen);

        //Yes Bank
        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen);

        //merchant subvention payments

        $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);

        //ICICI Merchant subvention Card
        $iciciPayment2 = $this->makeEmiPaymentOnCard('4076510000000033', 9, $yesterdayAtTen, 0, null, null);

        ////Yes Bank Merchant subvention Card
        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen, 0, null, null);

        $this->setCardPaymentMockResponse(
            [
                $iciciPayment1['id'] => [
                    'rrn'                    => '654321',
                    'gateway_merchant_id'    => 'ABC1234',
                    'gateway_transaction_id' => 'TRAN1234',
                ],
                $iciciPayment2['id'] => [
                    'rrn'                    => '654321',
                    'gateway_merchant_id'    => 'ABC1234',
                    'gateway_transaction_id' => 'TRAN1234',
                ],
            ]
        );

        $request = array(
            'method'  => 'POST',
            'url'     => '/emi/generate/excel',
            'content' => []);

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(count($content), 1);

        $this->assertEquals(true, File::exists($content['ICIC']));
        $this->assertEquals(true, File::exists($content['YESB']));

        // Assert ICICI file contents
        $monthYear = Carbon::now(Timezone::IST)->format('mY');

        $zip = new ZipArchive();
        $zip->open($content['ICIC']);
        $zip->setPassword('razorpay' . $monthYear);
        $pathinfo = pathinfo($content['ICIC']);
        $zip->extractTo($pathinfo['dirname']);
        $filename = $zip->getNameIndex(0);
        $zip->close();

        $emiFileContents = (new ExcelImport)->toArray($pathinfo['dirname'] . '/' . $filename);

        // Check if the fields are set correctly
        $arrayContent = [
            'emi_id'           => $iciciPayment1['id'],
            'mid'              => null,
            'tid'              => null,
            'rrn'              => null,
            'product_category' => null,
        ];

        $this->assertArraySelectiveEquals(
            $arrayContent,
            $emiFileContents[0][0]);

        $arrayContent['emi_id'] = $iciciPayment2['id'];

        $this->assertArraySelectiveEquals(
            $arrayContent,
            $emiFileContents[0][1]);

        // Assert ICICI file contents done

        $this->fixtures->merchant->disableEmi();

        $this->deleteAlltheGenerateFiles($content);

        unlink($content['YESB']);
    }

    public function testBeamPushForEmiFile()
    {
        Mail::fake();

        Queue::fake();

        $emiPlan = $this->emiPlan;

        //Making transactions hapen yesterday
        $yesterdayAtTen = Carbon::yesterday(Timezone::IST)->addHours(10)->timestamp;

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        //ICICI Card
        $iciciPayment1 = $this->makeEmiPaymentOnCard('4076510000000033', 9, $yesterdayAtTen);

        //Yes Bank
        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen);

        $this->setCardPaymentMockResponse(
            [
                $iciciPayment1['id'] => [
                    'rrn'                    => '654321',
                    'gateway_merchant_id'    => 'ABC1234',
                    'gateway_transaction_id' => 'TRAN1234',
                ],
            ]
        );

        $request = array(
            'method' => 'POST',
            'url' => '/emi/generate/excel',
            'content' => []);

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(count($content), 1);

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);
    }

    public function testOneCardEmiPayment()
    {
        $this->fixtures->emiPlan->createMerchantSpecificEmiPlans();

        // Enable EMI on iin
        $this->fixtures->create('iin',
            [
                'iin'                => '402275',
                'category'           => 'STANDARD',
                'network'            => 'MasterCard',
                'type'               => 'credit',
                'country'            => 'IN',
                'issuer_name'        => 'SBM Bank',
                'issuer'             => 'STCB',
                'cobranding_partner' => 'onecard',
                'emi'                => 1,
                'trivia'             => 'random trivia'
            ]);

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        $this->makeEmiPaymentOnCard('4022750600094037', 3);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertArraySelectiveEquals(
            [
                'status' => 'captured',
                'method' => 'emi',
            ],
            $payment
        );
    }

    private function zipFileName($filePath)
    {
        $pathinfo = pathinfo($filePath);

        return $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.zip';
    }

    private function deleteAlltheGenerateFiles($content)
    {
        foreach ($content as $file)
        {
            $zipFile = $this->zipFileName($file);

            if (file_exists($zipFile) === true)
            {
                unlink($zipFile);
            }
        }
    }

    private function checkPasswordProtectedZip($filePath)
    {
        $zip = new ZipArchive();
        $zip->open($filePath);

        $pathinfo = pathinfo($filePath);

        // Extraction fails, unset password
        $this->assertEquals(false, $zip->extractTo($pathinfo['dirname']));
        $this->deleteExtractedFile($pathinfo);

        $zip->setPassword(Str::random(10));
        // Extraction fails, incorrect password

        // This doesn't always work. extractTo() sometimes returns true
        // even with a made-up password. Commenting this out till someone can
        // figure it out.
        // $this->assertEquals(false, $zip->extractTo($pathinfo['dirname']));

        $this->deleteExtractedFile($pathinfo);

        $zip->close();
    }

    protected function deleteExtractedFile($pathinfo)
    {
        $excelFileName = $pathinfo['dirname'].'/'.$pathinfo['filename'].'.xlsx';
        $txtFileName = $pathinfo['dirname'].'/'.$pathinfo['filename'].'.txt';

        if (file_exists($excelFileName) === true)
        {
            unlink($excelFileName);
        }
        else if (file_exists($txtFileName) === true)
        {
            unlink($txtFileName);
        }
    }

    protected function makeEmiPaymentOnCard($card, $emiDuration,
        $paymentTime = null, $save = 0, $appToken = null, $customerId = null)
    {
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = $emiDuration;
        $this->payment['card']['number'] = $card;
        $this->payment['save'] = $save;
        $this->payment['app_token'] = $appToken;
        $this->payment['customer_id'] = $customerId;

        $this->doAuthAndCapturePayment($this->payment);

        // Set Payment Time
        $payment = $this->getDbLastEntityToArray('payment');

        if ($paymentTime !== null)
        {
            $this->fixtures->edit('payment', $payment['id'], [
                'created_at'    => $paymentTime - 2,
                'authorized_at' => $paymentTime,
                'captured_at'   => $paymentTime + 2,
                'updated_at'    => $paymentTime + 2,
            ]);
        }

        return $this->getDbLastEntityToArray('payment');
    }

    public function testEmiPaymentEmiNotSupported()
    {
        $emiPlan = $this->emiPlan;

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '4000400000000004';

        $this->changeEnvToNonTest();
        $content = $this->doAuthPayment($this->payment);

        $this->assertEquals($content['error']['http_status_code'], 400);
        $this->assertEquals($content['error']['internal_error_code'], 'BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD');
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }
}

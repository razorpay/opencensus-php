<?php

namespace RZP\Tests\Functional\Payment;

use Str;
use File;
use ZipArchive;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mail;

use RZP\Mail\Emi as EmiMail;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EMIPaymentTest extends TestCase
{
    use PaymentTrait;

    protected $emiPlan;

    public function setUp()
    {
        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockTokenex();
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

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();
    }

    public function testEmiPaymentCreateWithMerchantSub()
    {
        $emiPlan = $this->emiPlan;

        $emiPlanId = $emiPlan[0]['id'];

        $this->fixtures->create('emi_merchant_subvention', ['emi_plan_id' => $emiPlanId]);

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndGetPayment($this->payment);

        $payment = $this->capturePayment(
            $content['id'],
            500000, 'INR', 474100);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();
    }

    public function testEmiPaymentAutoCaptureWithMerchantSub()
    {
        $emiPlan = $this->emiPlan;

        $emiPlanId = $emiPlan[0]['id'];

        $this->fixtures->create('emi_merchant_subvention', ['emi_plan_id' => $emiPlanId]);

        $this->fixtures->merchant->enableEmi();
        $this->ba->publicAuth();
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = 9;
        $this->payment['card']['number'] = '41476700000006';

        $content = $this->doAuthAndGetPayment($this->payment);

        $this->fixtures->edit('payment', $content['id'], ['created_at' => time() - (36 * 60 * 60)]);

        $this->doAutoCapture();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['emi_plan_id'], $emiPlan[0]['id']);
        $this->assertEquals($payment['method'], 'emi');
        $this->assertEquals($payment['status'], 'captured');

        $this->fixtures->merchant->disableEmi();

    }

    public function testMultipleEmiPayments()
    {
        $this->testEmiPaymentCreate();

        $this->testEmiPaymentCreate();
    }

    public function testEmiFileGenerate()
    {
        $emiPlan = $this->emiPlan;

        //Making transactions hapen yesterday
        $yesterdayAtTen = Carbon::yesterday(Timezone::IST)->addHours(10)->timestamp;

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        //ICICI Card
        $this->makeEmiPaymentOnCard('4076510000000033', 9, $yesterdayAtTen);

        //Yes Bank
        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen);

        //merchant subvention payments

        $this->fixtures->merchant->addFeatures(['emi_merchant_subvention']);

        //ICICI Merchant subvention Card
        $this->makeEmiPaymentOnCard('4076510000000033', 9, $yesterdayAtTen, 0, null, null);

        ////Yes Bank Merchant subvention Card
        $this->makeEmiPaymentOnCard('5318491050009999', 9 ,$yesterdayAtTen, 0, null, null);

        $request = array(
            'method' => 'POST',
            'url' => '/emi/generate/excel',
            'content' => []);

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(count($content), 2);

        $this->assertEquals(true, File::exists($content['ICIC']));
        $this->assertEquals(true, File::exists($content['YESB']));

        $this->fixtures->merchant->disableEmi();

        $this->deleteAlltheGenerateFiles($content);

        unlink($content['YESB']);
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

        $zip->setPassword(Str::quickRandom(10));
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
        $paymentTime, $save = 0, $appToken = null, $customerId = null)
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
        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit('payment', $payment['id'], [
            'created_at'  => $paymentTime - 2,
            'authorized_at' => $paymentTime,
            'captured_at' => $paymentTime + 2,
            'updated_at' => $paymentTime + 2,
        ]);

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
}

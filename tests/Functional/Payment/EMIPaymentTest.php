<?php

namespace RZP\Tests\Functional\Payment;

use File;
use Carbon\Carbon;
use ZipArchive;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EmiPaymentTest extends TestCase
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

    public function testMultipleEmiPayments()
    {
        $this->testEmiPaymentCreate();

        $this->testEmiPaymentCreate();
    }

    public function testEmiFileGenerate()
    {
        $emiPlan = $this->emiPlan;

        //Making transactions hapen yesterday
        $yesterdayAtTen = Carbon::yesterday('Asia/Kolkata')->addHours(10)->timestamp;

        $this->fixtures->merchant->enableEmi();

        $this->ba->publicAuth();

        //Kotak Card
        $this->makeEmiPaymentOnCard('4280951000002433', 9, 1, 'capp_1000000custapp');
        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit('payment', $payment['id'], [
            'created_at'  => $yesterdayAtTen - 2,
            'authorized_at' => $yesterdayAtTen,
            'captured_at' => $yesterdayAtTen + 2,
            'updated_at' => $yesterdayAtTen + 2,
        ]);

        //Axis Card
        $this->makeEmiPaymentOnCard('4111460212312338', 3);
        $payment = $this->getLastEntity('payment', true);
        $this->fixtures->edit('payment', $payment['id'], [
            'created_at'  => $yesterdayAtTen - 2,
            'authorized_at' => $yesterdayAtTen,
            'captured_at' => $yesterdayAtTen + 2,
            'updated_at' => $yesterdayAtTen + 2,
        ]);

        $request = array(
            'method' => 'POST',
            'url' => '/emi/generate/excel',
            'content' => array());

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(count($content), 3);
        $this->assertEquals(File::exists($this->zipFileName($content['KKBK'])), true);
        $this->assertEquals(File::exists($this->zipFileName($content['UTIB'])), true);

        $this->checkPasswordProtectedZip($this->zipFileName($content['KKBK']));
        $this->checkPasswordProtectedZip($this->zipFileName($content['UTIB']));

        $this->fixtures->merchant->disableEmi();
    }

    private function zipFileName($filePath)
    {
        $pathinfo = pathinfo($filePath);
        return $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.zip';
    }

    private function checkPasswordProtectedZip($filePath)
    {
        $zip = new ZipArchive();
        $zip->open($filePath);

        $pathinfo = pathinfo($filePath);

        // Extraction fails, unset password
        $this->assertEquals($zip->extractTo($pathinfo['dirname']), false);
        $this->deleteExtractedFile($pathinfo);

        $zip->setPassword('incorrect_password');
        // Extraction fails, incorrect password
        $this->assertEquals($zip->extractTo($pathinfo['dirname']), false);
        $this->deleteExtractedFile($pathinfo);
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

    protected function makeEmiPaymentOnCard($card, $emiDuration, $save = 0, $appToken = null, $customerId =  null)
    {
        $this->payment['amount'] = 500000;
        $this->payment['method'] = 'emi';
        $this->payment['emi_duration'] = $emiDuration;
        $this->payment['card']['number'] = $card;
        $this->payment['save'] = $save;
        $this->payment['app_token'] = $appToken;
        $this->payment['customer_id'] = $customerId;

        $this->doAuthAndCapturePayment($this->payment);
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

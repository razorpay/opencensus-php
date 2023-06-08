<?php

namespace RZP\Tests\Functional\Merchant;

use Queue;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\MerchantFirsDocumentsZip;
use RZP\Services\UfhService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantDocumentFIRSTest Extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $ufh;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantDocumentTestData.php';

        parent::setUp();

        $this->ufh = (new UfhService($this->app));
    }

    public function testFetchFIRSDocuments()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);
        $this->assertEquals('firs_file',$content[1]['document_type']);

    }

    public function testFetchFIRSDocumentsUploadedOnFirstDayOfMonth()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);


        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime('02/01/2022'),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime('02/03/2022'),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $requestForFeb = $this->testData[__FUNCTION__]['request'];

        $requestForFeb['url'] = sprintf($requestForFeb['url'], '02','2022');
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($requestForFeb);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);

        $requestForJan = $this->testData[__FUNCTION__]['request'];

        $requestForJan['url'] = sprintf($requestForJan['url'], '01','2022');
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($requestForJan);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(0,$content);
        $this->assertEquals(0,count($content));

    }

    public function testDownloadFIRSDocuments()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $merchantDocument = $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);


        $request['url'] = sprintf($request['url'], date('m'),date('Y'),$merchantDocument['id']);
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service',$ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
            'signed_url' => 'firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.pdf"
        ]);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals('firs_file',$content['document_type']);
        $this->assertArrayKeysExist($content,['signed_url','file_store_id','id', 'document_type', 'merchant_id', 'created_at']);

    }

    /*
     * Disabling Test Case Because removing zipping functionality on merchant dashboard
     * because of already generated ICICI Zipped FIRS Documents
     *
    public function testDownloadFIRSDocumentsZIP()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $merchantDocument = $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_zip',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service',$ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
            'signed_url' => 'firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.zip"
        ]);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals('firs_zip',$content['document_type']);
        $this->assertArrayKeysExist($content,['signed_url','file_store_id','id', 'document_type', 'merchant_id', 'created_at']);

    }

    */

    /*
     * Disabling Test Case Because removing zipping functionality on merchant dashboard
     * because of already generated ICICI Zipped FIRS Documents
     *
    public function testDownloadFIRSDocumentsZIPFileNotPresent()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service',$ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
            'signed_url' => 'firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.zip"
        ]);

        $ufhService->shouldReceive('downloadFiles')->andReturn('DO6dXJfU4WzeAK');


        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals('firs_zip',$content['document_type']);
        $this->assertEquals('firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.zip",$content['signed_url']);
        $this->assertArrayKeysExist($content,['signed_url','file_store_id','id', 'document_type', 'merchant_id', 'created_at']);

    }

    */

    public function testFetchFIRSDocumentsWithICICIZippedDocument()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_zip',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WmePS',
        ]);


        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service',$ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
            'status' => 'uploaded',
            'signed_url' => 'firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.zip"
        ]);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);
        $this->assertEquals('firs_icici_zip',$content[1]['document_type']);

    }

    public function testFetchFIRSDocumentsWithICICIZippedDocumentInCreatedState()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_zip',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WmePS',
        ]);


        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $ufhService = \Mockery::mock('RZP\Services\UfhService')->makePartial();

        $this->app->instance('ufh.service',$ufhService);

        $ufhService->shouldReceive('getSignedUrl')->andReturn([
            'status' => 'created',
            'signed_url' => 'firs/'.$merchantDetail['merchant_id'].'/'.date('Y').'/'.date('m').'/'."random_name.zip"
        ]);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(1,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);

    }

    public function testICICIZipFIRSDocumentsIfNoZipExists()
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::now(Timezone::IST));

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $previousMonth->year;
        $month = $previousMonth->month;

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAJ',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true,$content['success']);

        Queue::assertPushed(MerchantFirsDocumentsZip::class);

    }

    public function testICICIZipFIRSDocumentsIfZipAlreadyExists()
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::now(Timezone::IST));

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $previousMonth->year;
        $month = $previousMonth->month;

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAJ',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_zip',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/10/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true,$content['success']);

        Queue::assertPushed(MerchantFirsDocumentsZip::class, 0);

    }

    public function testICICIZipFIRSDocumentsIfZipAlreadyExistsForPreviousToPreviousMonth()
    {
        Queue::fake();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        Carbon::setTestNow(Carbon::now(Timezone::IST));

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $previousMonth->year;
        $month = $previousMonth->month;

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAJ',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAJ',
        ]);


        $previousToPreviousMonth = Carbon::now(Timezone::IST)->subMonth()->subMonth();

        $year  = $previousToPreviousMonth->year;
        $month = $previousToPreviousMonth->month;

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' => strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' => strtotime($month.'/01/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAJ',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_icici_zip',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' => strtotime($month.'/10/'.$year),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertEquals(true,$content['success']);

        Queue::assertPushed(MerchantFirsDocumentsZip::class);

    }

    public function testFetchRBLAndFirstdataFIRSDocuments()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_firstdata_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);
        $this->assertEquals('firs_firstdata_file',$content[1]['document_type']);

    }

    public function testFetchRBLAndFirstdataFIRSDocumentsWithSummaryFile()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DM6dXJfU4WzeAF',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_firstdata_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $this->fixtures->create('merchant_document', [
            'document_type' => 'firs_firstdata_sum_file',
            'merchant_id'   => $merchantDetail['merchant_id'],
            'document_date' =>  time(),
            'file_store_id' => 'DO6dXJfU4WzeAK',
        ]);

        $request = $this->testData[__FUNCTION__]['request'];

        $request['url'] = sprintf($request['url'], date('m'),date('Y'));
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->sendRequest($request);

        $content = $this->getJsonContentFromResponse($response);

        $this->assertCount(2,$content);
        $this->assertEquals('firs_file',$content[0]['document_type']);
        $this->assertEquals('firs_firstdata_file',$content[1]['document_type']);

    }

}

<?php

namespace RZP\Tests\Functional\Gateway\Enach\Physical;

use Mail;
use Excel;

use ZipArchive;
use DOMDocument;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaperMandate;
use RZP\Constants\Entity as E;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Partner\PartnerTrait;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;

class IciciGatewayTest extends TestCase
{
    use FileHandlerTrait;
    use DbEntityFetchTrait;
    use AttemptTrait;
    use AttemptReconcileTrait;
    use PartnerTrait;
    use FileHandlerTrait;

    // 09-02-2020 Sunday
    const FIXED_NON_WORKING_DAY_TIME = 1581223905;
    // 10-02-2020 Monday
    const FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME = 1581313905;
    // 10-02-2020 Tuesday
    const FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME = 1581385905;

    protected function setUp(): void
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->testDataFilePath = __DIR__ . '/IciciGatewayTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        $this->fixtures->create('terminal:nach', [
                                                            'id'                  => '1icicnachDTmnl',
                                                            'gateway'             => 'nach_icici',
                                                            'gateway_access_code' => 'ICIC0TREA00',
                                                            'gateway_acquirer'    => 'icic',
                                                          ]);
        $this->fixtures->create(
            E::CUSTOMER,
            ['id' => '1000000000cust']
        );

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGatewayFileRegister()
    {
        Mail::fake();

        $payment1 = $this->createDummyRegisterToken();
        $this->fixtures->stripSign($payment1['id']);

        $this->createDummyRegisterToken();

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile = $files['items'][0];

        $expectedFileContentZip = [
            'type'        => 'icici_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $zipArchive = new ZipArchive();

        $zipFilePath = storage_path('files/filestore') . '/' . $zipFile['location'];

        $zipArchive->open($zipFilePath);

        $zipArchive->extractTo(dirname($zipFilePath) . '/extracted');

        $zipArchive->close();

        $fileName = 'MMS-CREATE-ICIC-ICIC865719-{$date}-{$count}';

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName1 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000001']);
        $fileName2 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000002']);

        $extractedFileList = scandir(dirname($zipFilePath) . '/extracted');

        $expectedExtractedFiles = [
            $fileName1 . '-INP.xml',
            $fileName1 . '_detailfront.jpg',
            $fileName1 . '_front.tiff',
            $fileName2 . '-INP.xml',
            $fileName2 . '_detailfront.jpg',
            $fileName2 . '_front.tiff',
        ];

        $this->assertEquals(['.', '..'], array_diff($extractedFileList, $expectedExtractedFiles));

        $this->assertArraySelectiveEquals($expectedFileContentZip, $zipFile);

        $this->validateRegisterXml(dirname($zipFilePath) . '/extracted/' . $fileName1 . '-INP.xml', $payment1['id']);

        Mail::assertQueued(NachMail::class, function ($mail) use ($fileName, $date)
        {
            $fileName = strtr($fileName, ['{$date}' => $date, '{$count}' => '000001']);

            $fileName = $fileName . '-INP.zip';

            $this->assertEquals($fileName, (array_keys($mail->viewData['mailData']))[0]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(2, $mailData[$fileName]['count']);

            return true;
        });
    }

    public function testGatewayFileRegisterSequenceIssue()
    {
        Mail::fake();

        $payment1 = $this->createDummyRegisterToken();
        $this->fixtures->stripSign($payment1['id']);

        $this->createDummyRegisterToken();

        $this->createDummyRegisterToken();

        $payment4 = $this->createDummyRegisterToken();
        $this->fixtures->stripSign($payment4['id']);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile1 = $files['items'][1];

        $zipFile2 = $files['items'][0];

        $expectedFileContentZip = [
            'type'        => 'icici_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $zipArchive = new ZipArchive();

        //zip 1
        $zipFilePath1 = storage_path('files/filestore') . '/' . $zipFile1['location'];

        $zipArchive->open($zipFilePath1);

        $zipArchive->extractTo(dirname($zipFilePath1) . '/extracted');

        //zip 2
        $zipFilePath2 = storage_path('files/filestore') . '/' . $zipFile2['location'];

        $zipArchive->open($zipFilePath2);

        $zipArchive->extractTo(dirname($zipFilePath2) . '/extracted2');

        $zipArchive->close();

        $fileName = 'MMS-CREATE-ICIC-ICIC865719-{$date}-{$count}';

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName1 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000001']);
        $fileName2 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000002']);
        $fileName3 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000003']);
        $fileName4 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000004']);

        $extractedFileList1 = scandir(dirname($zipFilePath1) . '/extracted');
        $extractedFileList2 = scandir(dirname($zipFilePath2) . '/extracted2');

        $expectedExtractedFiles1 = [
            $fileName1 . '-INP.xml',
            $fileName1 . '_detailfront.jpg',
            $fileName1 . '_front.tiff',
            $fileName2 . '-INP.xml',
            $fileName2 . '_detailfront.jpg',
            $fileName2 . '_front.tiff',
            $fileName3 . '-INP.xml',
            $fileName3 . '_detailfront.jpg',
            $fileName3 . '_front.tiff',
        ];

        $expectedExtractedFiles2 = [
            $fileName4 . '-INP.xml',
            $fileName4 . '_detailfront.jpg',
            $fileName4 . '_front.tiff',
        ];

        $this->assertEquals(['.', '..'], array_diff($extractedFileList1, $expectedExtractedFiles1));
        $this->assertEquals(['.', '..'], array_diff($extractedFileList2, $expectedExtractedFiles2));

        $this->assertArraySelectiveEquals($expectedFileContentZip, $zipFile1);

        $this->validateRegisterXml(dirname($zipFilePath1) . '/extracted/' . $fileName1 . '-INP.xml', $payment1['id']);
        $this->validateRegisterXml(dirname($zipFilePath2) . '/extracted2/' . $fileName4 . '-INP.xml', $payment4['id']);

        Mail::assertQueued(NachMail::class, function ($mail) use ($fileName, $date)
        {
            //file 1
            $fileName1 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000001']);

            $fileName1 = $fileName1 . '-INP.zip';

            $this->assertEquals($fileName1, (array_keys($mail->viewData['mailData']))[0]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(3, $mailData[$fileName1]['count']);

            //file 2
            $fileName2 = strtr($fileName, ['{$date}' => $date, '{$count}' => '000002']);

            $fileName2 = $fileName2 . '-INP.zip';

            $this->assertEquals($fileName2, (array_keys($mail->viewData['mailData']))[1]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(1, $mailData[$fileName2]['count']);

            return true;
        });
    }

    public function testGatewayFileRegisterOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createDummyRegisterToken();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function  testGatewayFileRegisterWhenMccNotRecognized()
    {
        Mail::fake();

        $this->fixtures->merchant->setCategory('9999');

        $payment = $this->createDummyRegisterToken();
        $this->fixtures->stripSign($payment['id']);

        $this->ba->cronAuth();

        $data = $this->testData['testGatewayFileRegisterWhenMccNotRecognized'];

        $content = $this->startTest();
        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $zipFile = $files['items'][0];

        $expectedFileContentZip = [
            'type'        => 'icici_nach_register',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $zipArchive = new ZipArchive();

        $zipFilePath = storage_path('files/filestore') . '/' . $zipFile['location'];

        $zipArchive->open($zipFilePath);
        $zipArchive->extractTo(dirname($zipFilePath) . '/extracted');
        $zipArchive->close();

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = 'MMS-CREATE-ICIC-ICIC865719-{$date}-{$count}';
        $fileName = strtr($fileName, ['{$date}' => $date, '{$count}' => '000001']);

        $this->validateRegisterXml(dirname($zipFilePath) . '/extracted/' . $fileName . '-INP.xml', $payment['id']);
    }

    public function testGatewaySuccessRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $completedBatch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $completedBatch['status']);
        $this->assertEquals(1, $completedBatch['success_count']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('captured', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNotNull($token['gateway_token']);
        $this->assertEquals('confirmed', $token['recurring_status']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");
    }

    public function testGatewayFailureRegistrationResponseFile()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment, 'false', 'M080');

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $completedBatch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $completedBatch['status']);
        $this->assertEquals(1, $completedBatch['success_count']);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        $this->assertEquals('failed', $payment['status']);
        $this->assertStringStartsWith("BAD_REQUEST_", $payment['internal_error_code']);
        $this->assertNotEmpty($payment['error_description'], "Payment should have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals('BAD_REQUEST_MANDATE_DATA_MISMATCH', $token['recurring_details']['failure_reason']);
    }

    public function testGatewayFileRegisterForPaymentCreatedOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createDummyRegisterToken();

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebit()
    {
        Mail::fake();

        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['type' => 'icici_nach_combined_debit'], true);

        $debit = $files['items'][0];

        $expectedFileContentDebit = [
            'type'        => 'icici_nach_combined_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY                                                                                  0001000000000000000030000011022020                       NACH00000000013149000000000000000000ICIC0TREA00000205025290                       000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debit = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'RZPTestMerchant',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'ICIC0TREA00',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'ICIC0000000011506909'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debit);

        Mail::assertQueued(NachMail::class, function ($mail)
        {
            $fileName = 'ACH-DR-ICIC-ICIC865719-{$date}-RZ0001-INP.txt';

            $date = Carbon::now(Timezone::IST)->format('dmY');

            $fileName = strtr($fileName, ['{$date}' => $date]);

            $this->assertEquals($fileName, (array_keys($mail->viewData['mailData']))[0]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(1, $mailData[$fileName]['sr_no']);

            $this->assertEquals('3,000.00', $mailData[$fileName]['amount']);

            $this->assertEquals('1', $mailData[$fileName]['count']);

            $this->assertEquals('emails.admin.icici_enach_npci', $mail->view);

            return true;
        });
    }

    public function testGatewayFileDebitOnNonWorkingDay()
    {
        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->createRecurringNachPayment();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileDebitBankResponseSuccess()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);
        $this->assertEquals(300000, $batch['amount']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('authorized', $payment['status']);
    }

    public function testGatewayFileDebitBankResponseFailure()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment, "0", "04");

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $payment = $this->getEntityById('payment', $payment['razorpay_payment_id'], true);

        $this->assertEquals('BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE', $payment['internal_error_code']);
        $this->assertEquals('failed', $payment['status']);
    }

    public function testGatewaySuccessRegistrationAckFile()
    {
        $payment = $this->createDummyRegisterToken();

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);

        $batchFile = $this->getBatchFileToUploadForBankRegisterAcknowledgementResponse($payment, true);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $completedBatch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $completedBatch['status']);
        $this->assertEquals(1, $completedBatch['success_count']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('created', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('IBKL0000000000000001', $token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
        $this->assertEquals(false, $token['recurring']);
        $this->assertNotEmpty($token['acknowledged_at']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");
    }

    public function testGatewayFailureRegistrationAckFile()
    {
        $payment = $this->createDummyRegisterToken();

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);

        $batchFile = $this->getBatchFileToUploadForBankRegisterAcknowledgementResponse($payment, false);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $completedBatch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $completedBatch['status']);
        $this->assertEquals(1, $completedBatch['success_count']);
        $this->assertEquals(0, $completedBatch['failure_count']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('failed', $payment['status']);

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('rejected', $token['recurring_status']);
        $this->assertEquals(false, $token['recurring']);
        $this->assertNull($token['acknowledged_at']);
        $this->assertNotNull($token['recurring_failure_reason']);
    }

    public function testGatewaySuccessRegistrationAckFileRetry()
    {
        $payment = $this->createDummyRegisterToken();

        $token = $this->getDbLastEntityToArray('token');

        $this->assertNull($token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);

        $batchFile = $this->getBatchFileToUploadForBankRegisterAcknowledgementResponse($payment, true);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $batch = $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        $completedBatch = $this->getLastEntity('batch', true);
        $this->assertEquals('processed', $completedBatch['status']);
        $this->assertEquals(1, $completedBatch['success_count']);

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('created', $payment['status']);
        $this->assertEmpty($payment['internal_error_code'], "Payment should not have some Error Code");
        $this->assertEmpty($payment['error_description'], "Payment should not have some Error Reason");

        $token = $this->getDbLastEntityToArray('token');

        $this->assertEquals('IBKL0000000000000001', $token['gateway_token']);
        $this->assertEquals('initiated', $token['recurring_status']);
        $this->assertEquals(false, $token['recurring']);
        $this->assertNotEmpty($token['acknowledged_at']);
        $this->assertEmpty($token['recurring_failure_reason'], "Token should not have some Error Reason");

        $batchFile = $this->getBatchFileToUploadForBankRegisterAcknowledgementResponse($payment, true);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'acknowledge');
    }

    public function testCancelNachToken()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertTrue($paymentEntity->isAuthorized());

        $this->assertTrue($paymentEntity->transaction->isReconciled());

        $this->assertEquals('confirmed', $paymentEntity->localToken->getRecurringStatus());

        $this->assertNotEmpty($paymentEntity->localToken->getGatewayToken());

        $response = $this->deleteCustomerToken('token_' . $paymentEntity['token_id'], 'cust_' . $paymentEntity['customer_id']);

        $this->assertTrue($response['deleted']);

        $this->assertNotNull($paymentEntity->localToken->getDeletedAtColumn());

        $this->ba->adminAuth();

        $this->startTest();

        $file = $this->getLastEntity('file_store', true);

        $actualZipFile = zip_open(storage_path('files/filestore') . '/' . $file['location']);

        $actualFile = zip_read($actualZipFile);

        $expectedFile = file_get_contents(__DIR__ . '/MMS-CANCEL-ICIC-ICIC865719-11022020-API000001-INP.xml');

        $expectedFileData = strtr($expectedFile, ['$tokenId' => $paymentEntity->getTokenId()]);

        $this->assertEquals(zip_entry_read($actualFile), $expectedFileData);

        $this->assertEquals('MMS-CANCEL-ICIC-ICIC865719-11022020-API000001-INP.xml', zip_entry_name($actualFile));

        zip_close($actualZipFile);
    }

    public function testNachCancellationSuccessResponseFile()
    {
        $payment = $this->createRecurringNachPayment();

        $batchFile = $this->getBatchFileToUploadForBankDebitResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile, 'debit');

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals('nach', $batch['type']);
        $this->assertEquals('processed', $batch['status']);

        $paymentEntity = $this->getDbLastPayment();

        $this->assertTrue($paymentEntity->isAuthorized());

        $this->assertTrue($paymentEntity->transaction->isReconciled());

        $this->assertEquals('confirmed', $paymentEntity->localToken->getRecurringStatus());

        $this->assertNotEmpty($paymentEntity->localToken->getGatewayToken());

        $response = $this->deleteCustomerToken('token_' . $paymentEntity['token_id'], 'cust_' . $paymentEntity['customer_id']);

        $this->assertTrue($response['deleted']);

        $batchFile = $this->getBatchFileToUploadForMandateCancelRes($paymentEntity);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile,'cancel');

        $token = $this->getTrashedDbEntityById('token', $paymentEntity->getTokenId());

        $this->assertEquals('cancelled', $token['recurring_status']);
    }

    protected function getBatchFileToUploadForMandateCancelRes(Payment $payment): TestingFile
    {
        $tokenId = $payment->getTokenId();

        $xmlData = file_get_contents(__DIR__ . '/MMS-ACCEPT-BARB-SYSTEM-29012022-000199-INP.xml');

        $responseXml = strtr($xmlData, ['$tokenId' => $tokenId]);

        $zip = new ZipArchive();

        $zip->open(__DIR__ . '/MMS-CANCEL-ICIC-ICIC403690-29012022-000026-RES.zip', ZipArchive::CREATE);

        $zip->addFromString( 'MMS-ACCEPT-BARB-SYSTEM-29012022-000199-INP.xml', $responseXml);

        $zip->close();

        $handle = fopen(__DIR__ . '/MMS-CANCEL-ICIC-ICIC403690-29012022-000026-RES.zip', 'r');

        return (new TestingFile('MMS-CANCEL-ICIC-ICIC403690-29012022-000026-RES.zip', $handle));
    }

    protected function createRecurringNachPayment()
    {
        $initialPayment = $this->createAcceptedToken();

        $tokenId = $initialPayment[Payment::TOKEN_ID];

        $order = $this->fixtures->create('order', [
            'amount' => 300000,
            'method' => 'nach',
        ]);

        $payment = [
            'contact'     => '9876543210',
            'email'       => 'r@g.c',
            'customer_id' => 'cust_1000000000cust',
            'currency'    => 'INR',
            'method'      => 'nach',
            'amount'      => 300000,
            'recurring'   => true,
            'token'       => $tokenId,
            'order_id'    => $order->getPublicId(),
        ];

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create',
            'content' => $payment
        ];

        $this->ba->privateAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function createAcceptedToken()
    {
        $payment = $this->createDummyRegisterToken();

        $batchFile = $this->getBatchFileToUploadForBankRegisterResponse($payment);

        $url = '/admin/batches';

        $this->ba->adminAuth();

        $this->makeRequestWithGivenUrlAndFile($url, $batchFile);

        $payment = $this->getEntityById('payment', $payment['id'], true);

        return $payment;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file, $type = 'register')
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => $type,
                'gateway'  => 'nach_icici',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getBatchFileToUploadForBankDebitResponse($payment, $status = '1', $errorCode = '00')
    {
        $paymentId = $payment['razorpay_payment_id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status . $errorCode. 'HDFC00024971111111111111                      CITI000PIGWNACH00000000013149' . $paymentId . '                10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

    protected function getBatchFileToUploadForBankRegisterResponse($payment, $status = 'true', $failureReason = 'ac01')
    {
        $paymentId = $payment['id'];

        $this->fixtures->stripSign($paymentId);

        $dom = new DOMDocument();

        $dom->load(__DIR__ . '/RegisterResponse.xml');

        $responseXml = strtr($dom->saveXML(), ['$paymentId' => $paymentId, '$status' => $status, '$failureReason' => $failureReason]);

        $zip = new ZipArchive();

        $zip->open(__DIR__ . '/test.zip', ZipArchive::CREATE);

        $zip->addFromString( 'RegisterResponse.xml', $responseXml);

        $zip->close();

        $handle = fopen(__DIR__ . '/test.zip', 'r');

        $file = (new TestingFile('test.zip', $handle));

        return $file;
    }

    protected function getBatchFileToUploadForBankRegisterAcknowledgementResponse($payment, $status)
    {
        $paymentId = $payment['id'];

        $this->fixtures->stripSign($paymentId);

        $dom = new DOMDocument();

        if ($status === true)
        {
            $dom->load(__DIR__ . '/AckSuccessResponse.xml');
        }
        else
        {
            $dom->load(__DIR__ . '/AckFailedResponse.xml');
        }

        $responseXml = strtr($dom->saveXML(), ['$paymentId' => $paymentId, '$status' => $status]);

        $zip = new ZipArchive();

        $date = now(Timezone::IST)->format('dmY');

        $zip->open(__DIR__ . '/MMS-CREATE-ICIC-ICIC865719-' . $date . '-100004-INP-ACK.zip', ZipArchive::CREATE);

        $zip->addFromString( 'MMS-CREATE-ICIC-ICIC865719-'. $date . '-200001-INP-ACK.xml', $responseXml);

        $zip->close();

        $handle = fopen(__DIR__ . '/MMS-CREATE-ICIC-ICIC865719-' . $date . '-100004-INP-ACK.zip', 'r');

        $file = (new TestingFile('MMS-CREATE-ICIC-ICIC865719-' . $date . '-100004-INP-ACK.zip', $handle));

        return $file;
    }

    protected function createDummyRegisterToken()
    {
        $orderId = UniqueIdEntity::generateUniqueId();

        $this->createOrder([
            'id'     => $orderId,
            'amount' => 0,
            'method' => 'nach',
            E::INVOICE => [
                'order_id' => $orderId,
                'amount'   => 0,
                E::SUBSCRIPTION_REGISTRATION => [
                    'token_id'   => '100000000token',
                    'max_amount' => 1000000,
                    'auth_type'  => 'physical',
                    E::PAPER_MANDATE => [
                        'amount' => 1000000,
                        'status' => PaperMandate\Status::AUTHENTICATED,
                        'uploaded_file_id' => '1000000000file'
                    ],
                ],
            ],
        ]);

        $response = $this->doAuthPayment([
            "amount"      => 0,
            "currency"    => "INR",
            "method"      => "nach",
            "order_id"    => 'order_' . $orderId,
            "customer_id" => "cust_1000000000cust",
            "recurring"   => true,
            "contact"     => "9483159238",
            "email"       => "r@g.c",
            "auth_type"   => "physical",
        ]);

        return $this->getEntityById('payment', $response['razorpay_payment_id'], true);
    }

    protected function createToken(array $overrideWith = [])
    {
        $payment = array_pull($overrideWith, E::PAYMENT, []);

        $token = $this->fixtures
            ->create(
                E::TOKEN,
                array_merge(
                    [
                        'id'              => '100000000token',
                    ],
                    $overrideWith
                )
            );

        $this->createPayment($payment);

        return $token;
    }

    protected function createPayment(array $overrideWith = [])
    {
        $order = array_pull($overrideWith, E::ORDER, []);

        $this->createOrder($order);

        $payment = $this->fixtures
            ->create(
                E::PAYMENT,
                array_merge(
                    [
                        'id'              => '1000000payment',
                    ],
                    $overrideWith
                )
            );

        return $payment;
    }

    protected function createOrder(array $overrideWith = [])
    {
        $invoice = array_pull($overrideWith, E::INVOICE, []);

        $order = $this->fixtures->create(
                E::ORDER,
                array_merge(
                    [
                        'amount'          => 100000,
                    ],
                    $overrideWith
                )
            );

        $this->createInvoiceForOrder($invoice);

        return $order;
    }

    protected function createInvoiceForOrder(array $overrideWith = [])
    {
        $subscriptionRegistration = array_pull($overrideWith, E::SUBSCRIPTION_REGISTRATION, []);

        $invoiceId                  = UniqueIdEntity::generateUniqueId();
        $subscriptionRegistrationId = UniqueIdEntity::generateUniqueId();

        $order = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => $invoiceId,
                        'entity_type'     => 'subscription_registration',
                        'entity_id'       => $subscriptionRegistrationId,
                    ],
                    $overrideWith
                )
            );

        $subscriptionRegistration['id'] = $subscriptionRegistrationId;

        $this->createSubscriptionRegistration($subscriptionRegistration);

        return $order;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
        $paperMandate = array_pull($overrideWith, E::PAPER_MANDATE, []);

        $paperMandateId = UniqueIdEntity::generateUniqueId();

        $subscriptionRegistration = $this->fixtures
            ->create(
                'subscription_registration',
                array_merge(
                    [
                        'method'          => 'nach',
                        'notes'           => [],
                        'entity_type'     => 'paper_mandate',
                        'entity_id'       => $paperMandateId,
                    ],
                    $overrideWith
                )
            );

        $paperMandate['id'] = $paperMandateId;

        $this->createPaperMandate($paperMandate);

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountId = UniqueIdEntity::generateUniqueId();

        $bankAccount = array_pull($overrideWith, E::BANK_ACCOUNT, []);

        $paperMandate = $this->fixtures
            ->create(
                'paper_mandate',
                array_merge(
                    [
                        'bank_account_id'   => $bankAccountId,
                        'amount'            => 1000,
                        'status'            => PaperMandate\Status::CREATED,
                        'debit_type'        => PaperMandate\DebitType::MAXIMUM_AMOUNT,
                        'type'              => PaperMandate\Type::CREATE,
                        'frequency'         => PaperMandate\Frequency::YEARLY,
                        'start_at'          => (new Carbon('+5 day'))->timestamp,
                        'utility_code'      => 'NACH00000000013149',
                        'sponsor_bank_code' => 'RATN0TREASU',
                        'terminal_id'       => '1icicnachDTmnl',
                    ],
                    $overrideWith
                )
            );

        $bankAccount['id'] = $bankAccountId;

        $this->createBankAccount($bankAccount);

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'beneficiary_name' => 'dead pool',
                        'ifsc_code'        => 'HDFC0001233',
                        'account_number'   => '1111111111111',
                        'account_type'     => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }

    protected function parseTextRow(string $row, int $ix, string $delimiter, array $headings = null)
    {
        $values = [
            Headings::ACH_TRANSACTION_CODE             =>  substr($row, 0, 2),
            Headings::CONTROL_9S                       =>  substr($row, 2, 9),
            Headings::DESTINATION_ACCOUNT_TYPE         =>  substr($row, 11, 2),
            Headings::LEDGER_FOLIO_NUMBER              =>  substr($row, 13, 3),
            Headings::CONTROL_15S                      =>  substr($row, 16, 15),
            Headings::BENEFICIARY_ACCOUNT_HOLDER_NAME  =>  substr($row, 31, 40),
            Headings::CONTROL_9SS                      =>  substr($row, 71, 9),
            Headings::CONTROL_7S                       =>  substr($row, 80, 7),
            Headings::USER_NAME                        =>  substr($row, 87, 20),
            Headings::CONTROL_13S                      =>  substr($row, 107, 13),
            Headings::AMOUNT                           =>  substr($row, 120, 13),
            Headings::ACH_ITEM_SEQ_NO                  =>  substr($row, 133, 10),
            Headings::CHECKSUM                         =>  substr($row, 143, 10),
            Headings::FLAG                             =>  substr($row, 153, 1),
            Headings::REASON_CODE                      =>  substr($row, 154, 2),
            Headings::DESTINATION_BANK_IFSC            =>  substr($row, 156, 11),
            Headings::BENEFICIARY_BANK_ACCOUNT_NUMBER  =>  substr($row, 167, 35),
            Headings::SPONSOR_BANK_IFSC                =>  substr($row, 202, 11),
            Headings::USER_NUMBER                      =>  substr($row, 213, 18),
            Headings::TRANSACTION_REFERENCE            =>  substr($row, 231, 30),
            Headings::PRODUCT_TYPE                     =>  substr($row, 261, 3),
            Headings::BENEFICIARY_AADHAR_NUMBER        =>  substr($row, 264, 15),
            Headings::UMRN                             =>  substr($row, 279, 20),
            Headings::FILLER                           =>  substr($row, 299, 7),
        ];

        return $values;
    }

    protected function validateRegisterXml($generatedXmlPath, $paymentId)
    {
        $actual = file_get_contents($generatedXmlPath);

        $expected = file_get_contents(__DIR__ . '/RegisterRequest.xml');

        $expected = strtr($expected, ['$paymentId' => $paymentId]);

        $this->assertEquals($expected, $actual);
    }
}

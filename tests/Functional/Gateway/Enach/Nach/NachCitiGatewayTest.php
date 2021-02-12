<?php

namespace RZP\Tests\Functional\Gateway\Enach\Nach;

use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;

class NachCitiGatewayTest extends NachGatewayTest
{
    public function testGatewayFileWithEarlyPresentmentFeatureFor11AMPayment()
    {
        $this->fixtures->merchant->addFeatures(['early_mandate_presentment']);

        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->fixtures->edit('payment', $response['razorpay_payment_id'], [
            'created_at' => Carbon::yesterday(Timezone::IST)->addHours(11)->timestamp
        ]);

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testGatewayFileWithEarlyPresentmentFeatureFor5PMPayment()
    {
        $this->fixtures->merchant->addFeatures(['early_mandate_presentment']);

        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->fixtures->edit('payment', $response['razorpay_payment_id'], [
            'created_at' => Carbon::yesterday(Timezone::IST)->addHours(17)->timestamp
        ]);

        $this->ba->cronAuth();

        $data = $this->testData['testGatewayFileDebit'];

        $content = $this->startTest($data);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_NACH00000000013149_11022020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_NACH00000000013149_11022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0000050000000000000030000011022020                       NACH00000000013149000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testGatewayFileWithEarlyPresentmentFeatureForSundayPayment()
    {
        $this->fixtures->merchant->addFeatures(['early_mandate_presentment']);

        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $response = $this->createRecurringNachPayment();

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->cronAuth();

        $data = $this->testData['testGatewayFileDebit'];

        $content = $this->startTest($data);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_NACH00000000013149_10022020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/RAZORP_COLLECT_NACH00000000013149_10022020_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0000050000000000000030000010022020                       NACH00000000013149000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'CTRAZORPAY',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testGatewayFileWithEarlyPresentmentFeatureForWorkingSaturday()
    {
        $this->fixtures->merchant->addFeatures(['early_mandate_presentment']);

        $fixedTime = (new Carbon())->timestamp(self::FIXED_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $response = $this->createRecurringNachPayment();

        $this->fixtures->edit('payment', $response['razorpay_payment_id'], [
            'created_at' => Carbon::createFromTimestamp($fixedTime->subDays(2)->timestamp, Timezone::IST)->timestamp
        ]);

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_NON_WORKING_DAY_TIME);

        Carbon::setTestNow($fixedTime);

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->ba->cronAuth();

        $data = $this->testData['testGatewayFileWithEarlyPresentmentFeatureFor11AMPayment'];

        $this->startTest($data);
    }

    public function testGatewayFileEarlyDebit()
    {
        $this->fixtures->merchant->addFeatures(['early_mandate_presentment']);

        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $fixedTime = (new Carbon())->timestamp(self::FIXED_WORKING_DAY_AFTER_WORKING_DAY_TIME)->timestamp;

        $this->fixtures->edit('payment', $response['razorpay_payment_id'], [
            'created_at' => Carbon::createFromTimestamp($fixedTime, Timezone::IST)->addHours(4)->timestamp
        ]);

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $summary = $files['items'][0];
        $debit = $files['items'][1];

        $expectedFileContentSummary = [
            'type'        => 'citi_nach_early_debit_summary',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xls',
            'name'        => 'citi/nach/RAZORP_SUMMARY_NACH00000000013149_12022020_test'
        ];

        $expectedFileContentDebit = [
            'type'        => 'citi_nach_early_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
            'name'        => 'citi/nach/ACH-DR-CITI-CITI999999-12022020-MUT000100-INP_test',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentSummary, $summary);
        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY SOFTWARE PVT LTD                                                                 0000050000000000000030000012022020                       NACH00000000013149000000000000000000CITI000PIGW000018003                          000000001                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debitRow = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'dead pool',
            'User Name' => 'CTRAZORMFS',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'HDFC0001233',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'CITI000PIGW',
            'User Number' => 'NACH00000000013149',
            'Transaction Reference' => 'TESTMERCHA' . $response['razorpay_payment_id'],
            'Product Type' => 'MUT',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow, $debitRow);
    }

    public function testGatewayFileEarlyDebitWithoutFeatureEnabled()
    {
        $response = $this->createRecurringNachPayment();

        $this->fixtures->stripSign($response['razorpay_payment_id']);

        $this->fixtures->edit('payment', $response['razorpay_payment_id'], [
            'created_at' => Carbon::today(Timezone::IST)->addHours(11)->timestamp
        ]);

        $this->ba->cronAuth();

        $this->startTest();
    }
}

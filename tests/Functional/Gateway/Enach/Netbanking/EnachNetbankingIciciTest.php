<?php

namespace RZP\Tests\Functional\Gateway\Enach\Netbanking;

use Mail;
use Excel;
use Queue;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Constants\Timezone;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use Illuminate\Http\Testing\File as TestingFile;

class EnachNetbankingNpciIciciTest extends EnachNetbankingNpciGatewayTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->sharedCitiTerminal = $this->sharedTerminal;

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $this->sharedTerminal = $this->fixtures->create(
            'terminal:shared_enach_npci_netbanking_terminal',
            [
                Terminal\Entity::ID                  => Terminal\Shared::ENACH_NPCI_NETBANKING_ICIC_TERMINAL,
                Terminal\Entity::GATEWAY_ACQUIRER    => Payment\Gateway::ACQUIRER_ICIC,
                Terminal\Entity::GATEWAY_ACCESS_CODE => 'ICIC0TREA00'
            ]
        );
    }

    public function testDebitFileGeneration()
    {
        $payment1 = $this->makeDebitPayment();

        $this->fixtures->stripSign($payment1['razorpay_payment_id']);

        $payment2 = $this->makeDebitPayment(40000);

        $this->fixtures->stripSign($payment2['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationIcici']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(1, $files['items']);

        $debit = $files['items'][0];

        $expectedFileContentDebit = [
            'type'        => 'icici_nach_combined_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $enach = $this->getLastEntity('enach', true);

        $this->assertArraySelectiveEquals(
            [
                'payment_id' => $payment2['razorpay_payment_id'],
                'action'     => 'authorize',
                'bank'       => 'UTIB',
                'status'     => null,
            ],
            $enach
        );

        $fileContent = explode("\n", file_get_contents('storage/files/filestore/' . $debit['location']));

        // since date and amount is fixed for this test header is a constant
        $expectedHeader = '56       RAZORPAY                                                                                  0000050000000000000034000007032020                       shared_utility_cod000000000000000000ICIC0TREA00000205025290                       000000002                                                           ';

        $this->assertEquals($expectedHeader, $fileContent[0]);

        $debit1 = array_map('trim', $this->parseTextRow($fileContent[1], 0, ''));

        $expectedDebitRow1 = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'RZPTestMerchant',
            'Amount' => '0000000300000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'ICIC0TREA00',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => $payment1['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $debit2 = array_map('trim', $this->parseTextRow($fileContent[2], 0, ''));

        $expectedDebitRow2 = [
            'ACH Transaction Code' => '67',
            'Destination Account Type' => '10',
            'Beneficiary Account Holder\'s Name' => 'Test account',
            'User Name' => 'RZPTestMerchant',
            'Amount' => '0000000040000',
            'Destination Bank IFSC / MICR / IIN' => 'UTIB0000123',
            'Beneficiary\'s Bank Account number' => '1111111111111',
            'Sponsor Bank IFSC / MICR / IIN' => 'ICIC0TREA00',
            'User Number' => 'shared_utility_cod',
            'Transaction Reference' => $payment2['razorpay_payment_id'],
            'Product Type' => '10',
            'UMRN' => 'UTIB6000000005844847'
        ];

        $this->assertArraySelectiveEquals($expectedDebitRow1, $debit1);
        $this->assertArraySelectiveEquals($expectedDebitRow2, $debit2);

        Mail::assertQueued(NachMail::class, function ($mail)
        {
            $fileName = 'ACH-DR-ICIC-ICIC401790-{$date}-RZ0001-INP.txt';

            $date = Carbon::now(Timezone::IST)->format('dmY');

            $fileName = strtr($fileName, ['{$date}' => $date]);

            $this->assertEquals($fileName, (array_keys($mail->viewData['mailData']))[0]);

            $mailData = $mail->viewData['mailData'];

            $this->assertEquals(1, $mailData[$fileName]['sr_no']);

            $this->assertEquals('3,400.00', $mailData[$fileName]['amount']);

            $this->assertEquals('2', $mailData[$fileName]['count']);

            $this->assertEquals('emails.admin.icici_enach_npci', $mail->view);

            return true;
        });
    }

    public function testDebitFileGenerationMultipleSponsorBanks()
    {
        $iciciTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($iciciTerminalPaymentResponse['razorpay_payment_id']);

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $this->fixtures->terminal->enableTerminal($this->sharedCitiTerminal['id']);

        $citiTerminalPaymentResponse = $this->makeDebitPayment();

        $this->fixtures->stripSign($citiTerminalPaymentResponse['razorpay_payment_id']);

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $content = $this->startTest($this->testData['testDebitFileGenerationIcici']);

        $content = $content['items'][0];

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(1, $files['items']);

        $debit = $files['items'][0];

        $expectedFileContentDebit = [
            'type'        => 'icici_nach_combined_debit',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContentDebit, $debit);

        $fileContent = file_get_contents('storage/files/filestore/' . $debit['location']);

        $this->assertNotFalse(strpos($fileContent, $iciciTerminalPaymentResponse['razorpay_payment_id']));

        $this->assertFalse(strpos($fileContent, $citiTerminalPaymentResponse['razorpay_payment_id']));

        $this->fixtures->terminal->disableTerminal($this->sharedCitiTerminal['id']);

        $this->fixtures->terminal->enableTerminal($this->sharedTerminal['id']);
    }

    public function testDebitFileGenerationMultipleUtilityCode()
    {
        $this->makeDebitPayment();

        $this->fixtures->create(
            'terminal:direct_enach_npci_netbanking_terminal',
            [Terminal\Entity::GATEWAY_ACQUIRER => Payment\Gateway::ACQUIRER_ICIC]
        );

        $this->makeDebitPayment();

        $this->ba->adminAuth();

        Queue::fake();
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testDebitFileGenerationIcici'];

        $this->startTest();

        $files = $this->getEntities('file_store', [], true);

        $this->assertCount(2, $files['items']);

        $fileName1 = $files['items'][0]['name'];
        $fileName2 = $files['items'][1]['name'];

        // The file naming format - 'icici/nach/debit/ACH-DR-ICIC-ICIC401790-{$date}-{$batchCode}-INP'
        // Batch code is a sequentially increasing number for every file generated
        // Different files are generated for different utility codes
        $seqNo1 = (int) substr($fileName1, -5, 1);
        $seqNo2 = (int) substr($fileName2, -5, 1);

        if ((($seqNo1 - $seqNo2) === 1) or
            (($seqNo2 - $seqNo1) === 1))
        {
            $fileNamesSequential = true;
        }
        else
        {
            $fileNamesSequential = false;
        }

        $this->assertTrue($fileNamesSequential);

        Mail::assertQueued(NachMail::class, function ($mail)
        {
            $this->assertEquals(2, count($mail->viewData['mailData']));

            return true;
        });
    }

    protected function getBatchDebitFile($payment, $status)
    {
        $paymentId = $payment['id'];
        $this->fixtures->stripSign($paymentId);

        $data = '56       RAZORPAY SOFTWARE PVT LTD                             000000000                           000005000000000000000020001701202047642224498136619848   NACH00000000013149000000000000000000CITI000PIGW000018003                          00000000227
67         10                  ABIJITO GUHA                            17012020        RAZORPAY SOFTWARE PV             000000030000047642224504081750481'. $status['status'] . $status['error_code']. 'HDFC00024971111111111111                      CITI000PIGWNACH00000000013149' . $paymentId . '                10 000000000000000HDFC0000000010936518
';

        $name = 'temp.txt';

        $handle = tmpfile();
        fwrite($handle, $data);
        fseek($handle, 0);

        $file = (new TestingFile($name, $handle));

        return $file;
    }

    protected function makeRequestWithGivenUrlAndFile($url, $file)
    {
        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [
                'type'     => 'nach',
                'sub_type' => 'debit',
                'gateway'  => 'nach_icici',
            ],
            'files'   => [
                'file' => $file,
            ],
        ];

        return $this->makeRequestAndGetContent($request);
    }
}

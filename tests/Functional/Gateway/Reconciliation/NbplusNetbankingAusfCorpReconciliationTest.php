<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Constants\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Feature\Constants;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;


class NbplusNetbankingAusfCorpReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';
        parent::setUp();

        $this->bank = 'AUBL_C';

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ausf_terminal', $terminalAttrs );

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testAusfCorpSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);
        $this->assertEquals(Payment::CAPTURED, $payment[Payment::STATUS]);

        $data = $this->testData[__FUNCTION__];

        $data['payment_id'] = $payment['id'];

        $data['amount'] = $payment['amount'] / 100;

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'Razorpay Tagit txn '.Carbon::today()->format("dmY").'.csv', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_AUSF);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(1234, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $this->assertEquals(1234, $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals('processed', $batch['status']);
    }

    public function testAusfSuccessReconWithForceAuthorize()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === NbPlusPaymentService\Action::AUTHORIZE)
            {
                $content = [
                    NbPlusPaymentService\Response::RESPONSE  => null,
                    NbPlusPaymentService\Response::ERROR => [
                        NbPlusPaymentService\Error::CODE  => 'GATEWAY',
                        NbPlusPaymentService\Error::CAUSE => [
                            NbPlusPaymentService\Error::MOZART_ERROR_CODE => 'BAD_REQUEST_PAYMENT_FAILED'
                        ]

                    ],
                ];
            }
        });

        $this->makeRequestAndCatchException(
            function()
            {
                $this->doAuthPayment($this->payment);
            },
            GatewayErrorException::class);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment::NB_PLUS_SERVICE, $payment[Payment::CPS_ROUTE]);
        $this->assertEquals(Status::FAILED, $payment[Payment::STATUS]);

        $data = $this->testData['testAusfCorpSuccessRecon'];

        $data['payment_id'] = $payment['id'];

        $data['amount'] = $payment['amount'] / 100;

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'Razorpay Tagit txn '.Carbon::today()->format("dmY").'.csv', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_AUSF);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(1234, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $this->assertEquals(1234, $paymentEntity['reference1']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        $formattedData = 'TRANSACTION TYPE,CHANNEL_REF_NO,PAYMENT_ID_EXT,MERCHANT_ID,USERREFERENCENO,HOST_REF_NO,EXTERNALREFERENCEID_EXT,PAYMENT_DATE,PAYMENT_AMT,REFUND_AMOUNT,DEBIT_ACCOUNT_NO,STATUS,MERCHANT_ACCT_NO,MERCHANT_URL
PAYMENT,PG-20210205195519793000000-610,,RAZORPAY,\''.$data['payment_id'].','.$data['HOST_REF_NO'].',,05-FEB-21 07.54.46.387000000 PM,'.$data['amount'].',,\'1712220914442391,S,\'2121201131751367,https://www.razorpay.com';

        return $this->createFile($formattedData);
    }

    public function createUploadedFile(string $url, $fileName = 'file.txt', $mime = "text/plain"): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
}

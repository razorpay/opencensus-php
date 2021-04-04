<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Models\FileStore;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Gateway;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Reconciliator\NetbankingDcb\Reconciliate;
use RZP\Tests\Functional\Helpers\FileUploadTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingDcbReconciliationTest extends NbPlusPaymentServiceNetbankingTest
{
    use ReconTrait;
    use FileUploadTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_dcb_terminal');

        $this->bank = 'DCBL';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testDcbSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payments[] = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payments[0][Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payments[0][Payment::STATUS], Payment::CAPTURED);
        $this->assertEquals($payments[0][Payment::GATEWAY], Gateway::NETBANKING_DCB);
        $this->assertEquals($payments[0]['acquirer_data']['bank_transaction_id'], 1234);
        $this->assertEquals($payments[0]['reference1'], 1234);

        $transactionEntity1 = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->doAuthAndCapturePayment($this->payment);

        $payments[] = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payments[1][Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payments[1][Payment::STATUS], Payment::CAPTURED);
        $this->assertEquals($payments[1][Payment::GATEWAY], Gateway::NETBANKING_DCB);
        $this->assertEquals($payments[1]['acquirer_data']['bank_transaction_id'], 1234);
        $this->assertEquals($payments[1]['reference1'], 1234);

        $transactionEntity2 = $this->getDbLastEntity(Entity::TRANSACTION);

        $reconFile = $this->getReconFile($payments);

        $uploadedFile = $this->createUploadedFile($reconFile);

        $this->reconcile($uploadedFile, Base::NETBANKING_DCB);

        $txn1 = $this->getDbEntityById('transaction', $transactionEntity1['id']);
        $txn2 = $this->getDbEntityById('transaction', $transactionEntity2['id']);

        $this->assertNotNull($txn1[Txn::RECONCILED_AT]);
        $this->assertNotNull($txn2[Txn::RECONCILED_AT]);

        $this->assertEquals($txn1[Txn::GATEWAY_AMOUNT], $payments[0][Payment::AMOUNT]);
        $this->assertEquals($txn2[Txn::GATEWAY_AMOUNT], $payments[1][Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function testDcbSuccessReconWithForceAuthorize()
    {
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if (($action === NbPlusPaymentService\Action::AUTHORIZE) or ($action === NbPlusPaymentService\Action::VERIFY))
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

        $payments[] = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payments[0][Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payments[0][Payment::STATUS], Status::FAILED);

        $reconFile = $this->getReconFile($payments);

        $uploadedFile = $this->createUploadedFile($reconFile);

        $this->reconcile($uploadedFile, Base::NETBANKING_DCB, [$payments[0]['public_id']]);

        $paymentEntity = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 1234);

        $this->assertEquals($paymentEntity['reference1'], 1234);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::GATEWAY_AMOUNT], $payments[0][Payment::AMOUNT]);

        $this->assertEquals($paymentEntity['status'], Status::AUTHORIZED);
        $this->assertEquals($paymentEntity['late_authorized'], true);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function getReconFile($payments)
    {
        $formattedData = '';

        foreach ($payments as $index => $payment)
        {
            $amount  = number_format($payment['amount'] / 100, '2', '.', '');

            $formattedData = $formattedData . $payment['id'] . '^' . '1234' . '^' . $amount . '^' . '000' . '^' .
                Carbon::createFromTimestamp($payment['created_at'])->format("d-m-y") . "\n";
        }

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($formattedData)
                        ->name('DcbReconTest')
                        ->type(FileStore\Type::MOCK_RECONCILIATION_FILE)
                        ->headers(false)
                        ->save()
                        ->get();

        return $file['local_file_path'];
    }
}

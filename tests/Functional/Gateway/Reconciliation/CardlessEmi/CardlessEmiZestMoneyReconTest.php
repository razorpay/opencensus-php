<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\CardlessEmi;

use RZP\Constants\Entity;
use RZP\Models\Payment\Gateway;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Models\Payment\Entity as Payment;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

Class CardlessEmiZestMoneyReconTest extends TestCase
{
    use ReconTrait;

    use FileHandlerTrait;

    use PaymentTrait;

    use DbEntityFetchTrait;

    private $payment;

    private $sharedTerminal;

    protected $provider;

    private $method = 'cardless_emi';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CardlessEmiZestMoneyReconTestData.php';

        parent::setUp();

        $this->provider = 'zestmoney';

        $this->sharedTerminal = $this->fixtures->create('terminal:cardlessEmiZestMoneyTerminal');

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $this->gateway = Gateway::CARDLESS_EMI;

        $this->fixtures->merchant->enableMethod('10000000000000', 'cardless_emi');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testZestMoneyCombinedReconSuccess()
    {
        //since zestmoney has moved to new flow
        $this->markTestSkipped();

        $this->doAuthPayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $capturedPayment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($capturedPayment['id']);

        $gatewayRefund = $this->getLastEntity('cardless_emi', true);

        $refund = $this->getLastEntity('refund', true);

        $this->assertEquals($payment['id'], 'pay_' . $gatewayRefund['payment_id']);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::API);
        $this->assertEquals($capturedPayment[Payment::STATUS], 'captured');
        $this->assertEquals($refund[Payment::STATUS],'processed');

        $data[0] = $this->testData['testZestMoneySuccessRecon'];
        $data[1] = $this->testData['testZestMoneySuccessRecon'];

        $data[0]['partner_order_id'] = substr($payment['id'], 4);
        $data[1]['refund_id'] = substr($refund['id'], 5);
        $data[1]['partner_order_id'] = substr($refund['payment_id'], 4);
        $data[1]['transaction_type'] = 'REVERSE';
        $data[1]['refund_amount'] = '500.00';

        $file = $this->writeToExcelFile($data, 'zestmoney_recon_file', 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'zestmoney_recon_file.xlsx');

        $this->reconcile($uploadedFile, Base::CARDLESS_EMI_ZESTMONEY);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(2, $batch['total_count']);
        $this->assertEquals(2, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
}


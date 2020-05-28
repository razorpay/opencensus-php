<?php

namespace RZP\Tests\Functional\Gateway\File;

use Illuminate\Http\UploadedFile;
use Mail;

use phpseclib\Crypt\AES;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Gateway\Base\AESCrypto;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Reconciliator\NetbankingSvc\Reconciliate;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceTest;

class NbplusNetbankingSvcReconciliationTest extends NbPlusPaymentServiceTest
{
    use ReconTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/NbplusNetbankingReconciliationTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_svc_terminal');

        $this->bank = 'SVCB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testSvcSuccessRecon()
    {
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
        $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

        $data = $this->testData[__FUNCTION__];

        $data[Reconciliate::PAYMENT_ID] = $payment['id'];

        $reconFile = $this->generateReconFile($data);

        $uploadedFile = $this->createUploadedFile($reconFile['local_file_path'],'SvcReconTest.txt', "text/plain");

        $this->reconcile($uploadedFile, Base::NETBANKING_SVC);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);
        $this->assertEquals($transactionEntity[Txn::GATEWAY_AMOUNT], $payment[Payment::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    protected function generateReconFile($data)
    {
        $fileData = implode('^', $data);

        $config = $this->config['gateway.netbanking_svc'];

        $key = $config['encryption_key'];

        $iv = $config['encryption_iv'];

        $masterKey = hex2bin(md5($key));

        $aes = new AESCrypto(AES::MODE_CBC, $masterKey, base64_decode($iv));

        $encryptedString = bin2hex($aes->encryptString($fileData));

        return $this->createFile($encryptedString);
    }
    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            filesize($url),
            null,
            true);
    }
}

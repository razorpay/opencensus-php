<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Mockery;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Transaction\Entity as Txn;
use RZP\Reconciliator\RequestProcessor\Base;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentNbplusTrait;

class NbplusWalletPhonepeReconciliationTest extends TestCase
{
    use ReconTrait;
    use PaymentNbplusTrait;
    use FileHandlerTrait;

    private $sharedTerminal;

    protected $wallet = Wallet::PHONEPE;

    protected $method = Payment\Method::WALLET;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/NbplusWalletReconciliationTestData.php';

        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    return 'nbplusps';
                })
            );

        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Wallet', [$this->app])->makePartial();

        $this->app->instance('nbplus.payments', $this->nbPlusService);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_phonepe_terminal');

        $this->gateway = Payment\Gateway::WALLET_PHONEPE;

        $this->fixtures->merchant->enableWallet(Merchant\Account::TEST_ACCOUNT, $this->wallet);

        $this->payment = $this->getDefaultWalletPaymentArray($this->wallet);
    }

    public function testPhonepeSuccessRecon()
    {
        $this->doAuthPayment($this->payment);

        $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

        $this->assertEquals(Payment\Entity::NB_PLUS_SERVICE, $payment[Payment\Entity::CPS_ROUTE]);

        $data[] = $this->testData[__FUNCTION__];

        $data[0]['MerchantReferenceId'] = $payment['id'];
        $data[0]['MerchantOrderId'] = $payment['id'];

        $file = $this->writeToCsvFile($data, 'testRecon', null, 'files/filestore');

        $uploadedFile = $this->createUploadedFile($file, 'wallet_phonepe_recon_file.csv', 'text/csv');

        $this->reconcile($uploadedFile, Base::PHONEPE);

        $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

        $this->assertNotNull($transactionEntity[Txn::RECONCILED_AT]);

        $this->assertEquals($transactionEntity[Txn::AMOUNT], $payment[Payment\Entity::AMOUNT]);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertEquals($batch['status'], 'processed');
    }

    public function createUploadedFile(string $url, $fileName, $mime): UploadedFile
    {
        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true);
    }
}

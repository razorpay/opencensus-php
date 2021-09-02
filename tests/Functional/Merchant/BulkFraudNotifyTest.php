<?php


namespace Functional\Merchant;

use RZP\Models;
use RZP\Gateway;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Services\FreshdeskTicketClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\Fraud\BulkNotification\File;
use RZP\Models\Admin\Permission\Name as PermissionName;

class BulkFraudNotifyTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BulkFraudNotifyTestData.php';

        parent::setUp();
    }

    public function testNotifyWithChargebackPocEmail()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        (new Models\Merchant\Email\Service())->createEmails($payment->getMerchantId(), ['type' => 'chargeback', 'email' => 'a@rzp.com,b@rzp.com']);

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithPaymentId(bool $addPermission = true)
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, $addPermission);
    }

    public function testNotifyWithHitachiPrrn()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Hitachi\Entity $hitachiEntity */
        $hitachiEntity = $this->fixtures->create('hitachi', ['payment_id' => $payment->getId(), 'pRRN' => 123]);

        $arn = $hitachiEntity->getRrn();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'card',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithPaysecureRrn()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Paysecure\Entity $paysecureEntity */
        $paysecureEntity = $this->fixtures->create('paysecure', ['rrn' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $paysecureEntity->getRrn();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'card',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithNpciReferenceId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Upi\Base\Entity $upiEntity */
        $upiEntity = $this->fixtures->create('upi', ['npci_reference_id' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $upiEntity->getNpciReferenceId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'upi',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithGatewayPaymentId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Upi\Base\Entity $upiEntity */
        $upiEntity = $this->fixtures->create('upi', ['gateway_payment_id' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $upiEntity->getGatewayPaymentId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'upi',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyWithBankUtr()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Models\BankTransfer\Entity $bankTransferEntity */
        $bankTransferEntity = $this->fixtures->create('bank_transfer', ['utr' => 1111, 'payment_id' => $payment->getId()]);

        $arn = $bankTransferEntity->getUtr();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'bank_transfer',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

     public function testNotifyWithNetbankingGatewayPaymentId()
    {
        /** @var Models\Payment\Entity $payment */
        $payment = $this->fixtures->create('payment');

        /** @var Gateway\Netbanking\Base\Entity $netbankingEntity */
        $netbankingEntity = $this->fixtures->create('netbanking', [
            'bank' => 'hdfc',
            'bank_payment_id' => 1111,
            'caps_payment_id' => '123',
            'payment_id' => $payment->getId()
        ]);

        $arn = $netbankingEntity->getBankPaymentId();

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'netbanking',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, $payment->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

     public function testNotifyNotAbleToResolvePaymentIdCase()
    {
        $arn = 'random_arn';

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => 'netbanking',
                'reported_by' => 'Visa',
                'payment_id' => '',
                'type' => '',
                'arn' => $arn,
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [$arn, null, null, null, 'Could not resolve payment_id']
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0, true);
    }

    public function testNotifyIgnoreOnSecondCall()
    {
        $this->testNotifyWithPaymentId();
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);
        $this->testNotifyWithPaymentId(false);

        $payment = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $message = "Merchant was already notified 8 times. Can not notify more than 8 times in 24 hours. Please try again later.";

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment->getPublicId(), $payment->getMerchantId(), null, $message]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 0, false);
    }

    public function testNotifySingleForOneMerchant()
    {
        /** @var Models\Payment\Entity $payment */
        $payment1 = $this->fixtures->create('payment');

        /** @var Models\Payment\Entity $payment */
        $payment2 = $this->fixtures->create('payment');

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment1->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment2->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment1->getPublicId(), $payment1->getMerchantId(), 123, null],
            [null, $payment2->getPublicId(), $payment2->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 1, true);
    }

    public function testNotifyForMultipleMerchant()
    {
        /** @var Models\Payment\Entity $payment */
        $payment1 = $this->fixtures->create('payment');

        $merchant = $this->fixtures->create('merchant');

        /** @var Models\Payment\Entity $payment */
        $payment2 = $this->fixtures->create('payment', ['merchant_id' => $merchant->getId()]);

        $fileData = [
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment1->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
            [
                'reported_to_razorpay_at' => '11/08/2021',
                'payment_method' => '',
                'reported_by' => 'Visa',
                'payment_id' => $payment2->getPublicId(),
                'type' => '',
                'arn' => ''
            ],
        ];

        $expectedOutputFileRows = [
            ["arn", "payment_id", "merchant_id", "fd_ticket_id", "error"],
            [null, $payment1->getPublicId(), $payment1->getMerchantId(), 123, null],
            [null, $payment2->getPublicId(), $payment2->getMerchantId(), 123, null]
        ];

        $this->prepareAndDoTest($fileData, $expectedOutputFileRows, 2, true);
    }

    private function prepareAndDoTest(array $fileData, array $expectedOutputFileRows, int $expectFdCallCount, bool $addPermission)
    {
        $testData = &$this->testData['commonTestData'];

        $testData['request']['files']['file'] = $this->getBulkFraudNotifyUploadedXLSXFileFromFileData($fileData);

        $this->mockFreshdesk($expectFdCallCount);

        $this->ba->adminAuth();

        if ($addPermission === true)
        {
            $this->addAdminPermission();
        }

        $response = $this->startTest($testData);

        $entityId = $response['entity_id'];

        $fileStoreEntities = (new Models\FileStore\Repository())->fetch([
            'type'      => 'bulk_fraud_notification',
            'entity_id' => $entityId
        ]);

        $this->assertCount(2, $fileStoreEntities);

        /** @var Models\FileStore\Entity $outputFile */
        $outputFile = $fileStoreEntities->firstWhere('name', '=', $entityId . '_output');

        $uploadFile = new UploadedFile($outputFile->getFullFilePath(), $outputFile->getName() . '.' . $outputFile->getExtension());

        $fileData = (new File())->getFileData($uploadFile);

        $this->assertArraySelectiveEquals($expectedOutputFileRows, $fileData);
    }

    private function addAdminPermission()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::BULK_FRAUD_NOTIFY]);

        $role->permissions()->attach($perm->getId());
    }

    private function getBulkFraudNotifyUploadedXLSXFileFromFileData($fileData): UploadedFile
    {
        $inputExcelFile = (new File())->createExcelFile(
            $fileData,
            'bulk_dispute_test_input',
            'files/bulk_fraud_notify/test'
        );

        return $this->createUploadedFile($inputExcelFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function createUploadedFile(string $filePath, string $mimeType = null, int $fileSize = -1): UploadedFile
    {
        $this->assertFileExists($filePath);

        $mimeType = $mimeType ?: 'image/png';

        $fileSize = ($fileSize === -1) ? filesize($filePath) : $fileSize;

        return new UploadedFile($filePath, $filePath, $mimeType, $fileSize, null, true);
    }

    private function mockFreshdesk(int $expectFdCallCount): void
    {
        $freshdeskClientMock = $this->getMockBuilder(FreshdeskTicketClient::class)
                                    ->setConstructorArgs([$this->app])
                                    ->onlyMethods(['sendOutboundEmail'])
                                    ->getMock();

        $freshdeskClientMock
            ->expects($this->exactly($expectFdCallCount))
            ->method('sendOutboundEmail')
            ->willReturn(['id' => 123]);

        $this->app->instance('freshdesk_client', $freshdeskClientMock);
    }
}

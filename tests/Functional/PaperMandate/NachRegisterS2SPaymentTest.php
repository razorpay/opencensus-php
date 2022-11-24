<?php

namespace RZP\Tests\Functional\PaperMandate;

use Mockery;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Models\Order;
use RZP\Error\ErrorCode;
use RZP\Models\PaperMandate;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\PaperMandate\PaperMandateUpload;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class NachRegisterS2SPaymentTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const ORDER_ID = '100000000order';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/NachRegisterS2SPaymentTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->fixtures->create('terminal:nach');

        $this->ba->privateAuth();
    }

    public function testCreatePayment()
    {
        $this->mockExtractNACH();

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('accepted', $paperMandateUpload->getStatus());

        $notMatching = $paperMandateUpload->getNotMatching();

        $this->assertEmpty($notMatching);

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNotNull($paperMandate->getUploadedFileId());
        $this->assertEquals('authenticated', $paperMandate->getStatus());

        $bankAccount = $paperMandate->bankAccount;

        $tokenRegistration = $this->getDbLastEntity('subscription_registration');

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('created', $payment->getStatus());
        $this->assertEquals('physical', $payment->getAuthType());

        $token = $this->getDbLastEntity('token');

        $this->assertEquals('initiated', $token->getRecurringStatus());
        $this->assertEquals($paperMandate->getAmount(), $token->getMaxAmount());
        $this->assertEquals(false, $token->isRecurring());
        $this->assertEquals($payment->getAuthType(), $token->getAuthType());
        $this->assertEquals($bankAccount->getBankCode(), $token->getBank());
        $this->assertEquals($bankAccount->getBeneficiaryName(), $token->getBeneficiaryName());
        $this->assertEquals($bankAccount->getAccountNumber(), $token->getAccountNumber());
        $this->assertEquals($bankAccount->getAccountType(), $token->getAccountType());
        $this->assertEquals($paperMandate->getTerminalId(), $token->getTerminalId());
        $this->assertEquals($paperMandate->getStartAt(), $token->getStartTime());
        $this->assertEquals($paperMandate->getEndAt(), $token->getExpiredAt());
        $this->assertEquals($payment->getTokenId(), $token->getId());
        $this->assertEquals($tokenRegistration->token->getId(), $token->getId());
    }

    public function testCreatePaymentForAlreadyOneMorePaymentIsActive()
    {
        $this->testCreatePayment();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();
    }

    public function testCreatePaymentWithFormDataNotMatching()
    {
        $this->markTestSkipped("until form field validations are added back");

        $wrongData = [
            PaperMandateUpload\Entity::AMOUNT_IN_NUMBER => '123XXXX',
            PaperMandateUpload\Entity::UTILITY_CODE     => '123XXXX',
            PaperMandateUpload\Entity::ACCOUNT_NUMBER   => '123XXXX',
            PaperMandateUpload\Entity::IFSC_CODE        => '123XXXX',
            PaperMandateUpload\Entity::ACCOUNT_TYPE     => 'current',
        ];

        $this->mockExtractNACH($wrongData);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('rejected', $paperMandateUpload->getStatus());

        $notMatching = $paperMandateUpload->getNotMatching();

        $this->assertArrayKeysExist($wrongData, $notMatching);

        $this->assertEquals(count($wrongData), count($notMatching));

        $this->assertEquals('some fields are not matching', $paperMandateUpload->status_reason);

        $compareArray = $this->testData['hyperVergeExtractNACHOutput'];

        unset($compareArray['enhanced_image']);

        $this->assertArraySelectiveEquals(
            $compareArray,
            $paperMandateUpload->toArrayPublic());

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNull($paperMandate->getUploadedFileId());
    }

    public function testCreatePaymentWithoutSignature()
    {
        $wrongData = ['signature_present_primary' => 'no'];

        $this->mockExtractNACH($wrongData);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('rejected', $paperMandateUpload->getStatus());

        $notMatching = $paperMandateUpload->getNotMatching();

        $this->assertArrayKeysExist($wrongData, $notMatching);

        $this->assertEquals(count($wrongData), count($notMatching));

        $this->assertEquals('some fields are not matching', $paperMandateUpload->status_reason);

        $compareArray = $this->testData['hyperVergeExtractNACHOutput'];

        unset($compareArray['enhanced_image']);

        $this->assertArraySelectiveEquals(
            $compareArray,
            $paperMandateUpload->toArrayPublic());

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNull($paperMandate->getUploadedFileId());
    }

    public function testCreatePaymentWithDifferentForm()
    {
        $wrongData = [PaperMandate\Entity::FORM_CHECKSUM => 'YYYYYY'];

        $this->mockExtractNACH($wrongData);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('rejected', $paperMandateUpload->getStatus());

        $notMatching = $paperMandateUpload->getNotMatching();

        $this->assertArrayKeysExist($wrongData, $notMatching);

        $this->assertEquals(count($wrongData), count($notMatching));

        $this->assertEquals('some fields are not matching', $paperMandateUpload->status_reason);

        $compareArray = $this->testData['hyperVergeExtractNACHOutput'];

        unset($compareArray['enhanced_image']);

        $this->assertArraySelectiveEquals(
            $compareArray,
            $paperMandateUpload->toArrayPublic());

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNull($paperMandate->getUploadedFileId());
    }

    public function testCreatePaymentForFormExtractionFailed()
    {
        $this->markTestSkipped('until fixed');

        $this->mockHyperVerge(function ()
        {
            throw (new ServerErrorException('',ErrorCode::SERVER_ERROR_NACH_EXTRACTION_FAILED));
        });

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('failed', $paperMandateUpload->getStatus());

        $this->assertEquals('SERVER_ERROR_NACH_EXTRACTION_FAILED', $paperMandateUpload->status_reason);

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNull($paperMandate->getUploadedFileId());
    }

    public function testCreatePaymentWithNotReadableForm()
    {
        $this->mockHyperVerge(function ()
        {
            throw (new BadRequestException(
                ErrorCode::BAD_REQUEST_UNABLE_TO_READ_NACH_FORM,
            null,
            null,
            'unable to read form'));
        });

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandateUpload = $this->getDbLastEntity('paper_mandate_upload');

        $this->assertEquals('rejected', $paperMandateUpload->getStatus());

        $this->assertEquals('unable to read form', $paperMandateUpload->status_reason);

        $paperMandate = $this->getDbLastEntity('paper_mandate');

        $this->assertNull($paperMandate->getUploadedFileId());
    }

    public function testCreatePaymentWithInvalidOrderId()
    {
        $this->fixtures->create('order', ['id' => self::ORDER_ID]);

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();
    }

    public function testCreatePaymentWithNonExistingOrderId()
    {
        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();
    }

    public function testCreatePaymentWithoutOrderId()
    {
        $this->startTest();
    }

    public function testCreatePaymentWithNotSupportedFileType()
    {
        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__, 'sample_form.pdf');

        $this->startTest();
    }

    public function testCreatePaymentWithFileSizeMoreThanLimit()
    {
        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__, 'more_than_5MB.jpg');

        $this->startTest();
    }

    protected function mockExtractNACH(array $input = [])
    {
        $this->testData['hyperVergeExtractNACHOutput'] = array_merge(
            $this->testData['hyperVergeExtractNACHOutput'],
            $input);

        $callable = function ()
        {
            return $this->testData['hyperVergeExtractNACHOutput'];
        };

        return $this->mockHyperVerge($callable);
    }

    protected function mockHyperVerge($callable = null)
    {
        $hyperVerge = Mockery::mock('RZP\Services\HyperVerge', [$this->app]);

        $callable = $callable ?: function ()
        {
            return [];
        };

        $hyperVerge->shouldReceive('extractNACHWithOutputImage')
                   ->andReturnUsing($callable);

        $this->app->instance('hyperVerge', $hyperVerge);
    }

    protected function createOrder(array $overrideWith = []): Order\Entity
    {
        $invoiceArray = array_pull($overrideWith, 'invoice', []);

        $order = $this->fixtures
            ->create(
                'order',
                array_merge(
                    [
                        'id'     => '100000000order',
                        'amount' => 0,
                        'method' => 'nach',
                    ],
                    $overrideWith));

        $this->createInvoice($invoiceArray);

        return $order;
    }

    protected function createInvoice(array $overrideWith = [])
    {
        $subscriptionRegistrationArray = array_pull($overrideWith, 'subscriptionRegistration', []);
        $subscriptionRegistration = $this->createSubscriptionRegistration($subscriptionRegistrationArray);

        $invoice = $this->fixtures
            ->create(
                'invoice',
                array_merge(
                    [
                        'id'              => '1000000invoice',
                        'order_id'        => '100000000order',
                        'entity_type'     => 'subscription_registration',
                        'entity_id'       => $subscriptionRegistration->getId(),
                    ],
                    $overrideWith));

        return $invoice;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
        $paperMandateArray = array_pull($overrideWith, 'paper_mandate', []);
        $paperMandate = $this->createPaperMandate($paperMandateArray);

        $subscriptionRegistration = $this->fixtures
            ->create(
                'subscription_registration',
                array_merge(
                    [
                        'method'          => 'nach',
                        'notes'           => [],
                        'entity_type'     => 'paper_mandate',
                        'entity_id'       => $paperMandate->getId(),
                        'auth_type'       => 'physical',
                        'max_amount'      => 1000,
                    ],
                    $overrideWith));

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountArray = array_pull($overrideWith, 'bank_account', []);
        $bankAccount = $this->createBankAccount($bankAccountArray);

        $paperMandate = $this->fixtures
            ->create(
                'paper_mandate',
                array_merge(
                    [
                        'bank_account_id'   => $bankAccount->getId(),
                        'amount'            => 1000,
                        'status'            => PaperMandate\Status::CREATED,
                        'debit_type'        => PaperMandate\DebitType::MAXIMUM_AMOUNT,
                        'type'              => PaperMandate\Type::CREATE,
                        'frequency'         => PaperMandate\Frequency::YEARLY,
                        'start_at'          => (new Carbon('+5 day'))->timestamp,
                        'utility_code'      => 'NACH00000000013149',
                        'sponsor_bank_code' => 'RATN0TREASU',
                        'terminal_id'       => '1citinachDTmnl',
                        'form_checksum'     => 'XXXXXXX',
                    ],
                    $overrideWith));

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        return $this->fixtures->create(
            'bank_account',
            array_merge(
                [
                    'ifsc_code'          => 'HDFC0000123',
                    'account_number'     => '1111111111111',
                    'account_type'       => 'savings',
                    'beneficiary_email'  => 'gaurav.kumar12@example.com',
                    'beneficiary_mobile' => '9123456780',
                    'beneficiary_name'   => 'TEST',
                ],
                $overrideWith));
    }

    protected function createAndPutImageFileInRequest(string $callee, $file = 'sample_uploaded.jpeg')
    {
        $uploadedFile = $this->createUploadedFile(__DIR__ . '/Helpers/' . $file);

        $this->testData[$callee]['request']['files']['file'] = $uploadedFile;
    }

    protected function createUploadedFile(string $url, $fileName = 'test.jpeg'): UploadedFile
    {
        $mime = 'application/jpeg';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            null,
            true
        );
    }
}

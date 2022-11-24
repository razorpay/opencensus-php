<?php

namespace RZP\Tests\Functional\PaperMandate;

use Mockery;
use Carbon\Carbon;
use RZP\Services\RazorXClient;
use RZP\Constants\Entity;
use RZP\Models\PaperMandate;
use RZP\Tests\Traits\MocksRazorx;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaperMandateTest extends TestCase
{
    use DbEntityFetchTrait;
    use PaymentTrait;
    use MocksRazorx;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaperMandateTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->enableMethod('10000000000000', 'nach');

        $this->fixtures->create('terminal:nach');

        $this->ba->proxyAuth();
    }

    public function testCreateAuthLinkForPaperMandate()
    {
        $this->mockGenerateNACH();

        $this->startTest();
    }

    public function testCreateAuthLinkForPaperMandateSBNRO()
    {
        $this->mockGenerateNACH();

        if ($this->razorxMock === null)
        {
            $this->razorxMock = Mockery::mock(RazorXClient::class)->makePartial();

            $this->app->instance('razorx', $this->razorxMock);
        }

        $this->razorxMock->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                return 'on';
            });

        $this->startTest();
    }

    public function testCreateAuthLinkForPaperMandateWithMergedBank()
    {
        $this->mockGenerateNACH();

        $this->startTest();
    }

    public function testCreateAuthLinkForPaperMandateWithoutAuthType()
    {
        $this->mockGenerateNACH();

        $this->startTest();
    }

    public function testCreateAuthLinkForPaperMandateWithoutBankAccountForMandate()
    {
        $this->mockGenerateNACH();

        $this->startTest();
    }

    public function testAuthenticatePaperMandate()
    {
        $this->ba->publicAuth();

        $this->mockExtractNACH();

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals('1cXSLlUU8V9sXl', $paperMandate->getUploadedFileID());
    }

    public function testUploadValidatePaperMandate()
    {
        $this->mockExtractNACH();

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $response = $this->startTest();

        $this->assertNotNull($response['id']);

        $this->assertEmpty($response['not_matching']);

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertNull($paperMandate->uploaded_file_id);

        return $response;
    }

    public function testReuseUploadValidateInSubmitPaperMandate()
    {
        $validateResponse = $this->testUploadValidatePaperMandate();

        $this->testData[__FUNCTION__]['request']['content']['paper_mandate_upload_id'] = $validateResponse['id'];

        $this->ba->proxyAuth();

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);
        $paperMandateUpload = $this->getDbLastEntity(Entity::PAPER_MANDATE_UPLOAD);

        $this->assertEquals($paperMandateUpload->getEnhancedFileId(), $paperMandate->getUploadedFileID());
    }

    public function testShortUrlGenerationForGeneratedImageUrl()
    {
        $this->markTestSkipped('until s3 signed url timeout bug is fixed');

        $this->ba->publicAuth();

        $this->createOrder();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $formUrl = $paperMandate->getGeneratedFormUrl();

        $this->assertEquals($formUrl, $paperMandate->getGeneratedFormUrl());

        $time = new Carbon('+7 days');
        Carbon::setTestNow($time);

        $this->assertNotEquals($formUrl, $paperMandate->getGeneratedFormUrl());
    }

    public function testAuthenticatePaperMandateWithoutCustomerSign()
    {
        $this->ba->publicAuth();

        $this->mockExtractNACHWithoutCustomerSignature();

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::CREATED, $paperMandate->getStatus());
    }

    public function testAuthenticatePaperMandateWithWrongAccountNumber()
    {
        $this->markTestSkipped("until account number validation enabled back");

        $this->ba->publicAuth();

        $this->mockExtractNACHWithWrongAccountNumber();

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::CREATED, $paperMandate->getStatus());
    }

    public function testCreatePaperMandateSpecialCharsInName()
    {
        $this->mockGenerateNACH();

        $this->startTest();
    }

    protected function mockExtractNACHWithWrongAccountNumber()
    {
        $this->testData['hyperVergeExtractNACHOutput']['account_number'] = '000';

        $this->mockExtractNACH();
    }

    protected function mockExtractNACHWithoutCustomerSignature()
    {
        $this->testData['hyperVergeExtractNACHOutput']['signature_present_primary'] = 'no';

        return $this->mockExtractNACH();
    }

    protected function mockExtractNACH($input = null)
    {
        $callable = function () use ($input)
        {
            if ($input !== null)
            {
                return $input;
            }

            return $this->testData['hyperVergeExtractNACHOutput'];
        };

        return $this->mockHyperVerge($callable);
    }

    protected function mockGenerateNACH()
    {
        $callable = function ()
        {
            return [
                'outputImage' => base64_encode(file_get_contents(__DIR__ . '/Helpers/sample_form.pdf')),
                'uid'         => 'XXXXXXX'
            ];
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

        $hyperVerge->shouldReceive('generateNACH', 'extractNACHWithOutputImage')
                   ->andReturnUsing($callable);

        $this->app->instance('hyperVerge', $hyperVerge);
    }

    protected function createOrder(array $overrideWith = [])
    {
        $order = $this->fixtures
            ->create(
                'order',
                array_merge(
                    [
                        'id'     => '100000000order',
                        'amount' => 0,
                        'method' => 'nach',
                    ],
                    $overrideWith
                )
            );

        $this->createInvoiceForOrder();

        return $order;
    }

    protected function createInvoiceForOrder(array $overrideWith = [])
    {
        $subscriptionRegistrationId = UniqueIdEntity::generateUniqueId();

        $order = $this->fixtures
                      ->create(
                          'invoice',
                          array_merge(
                              [
                                  'id'              => '1000000invoice',
                                  'order_id'        => '100000000order',
                                  'entity_type'     => 'subscription_registration',
                                  'entity_id'       => $subscriptionRegistrationId,
                              ],
                              $overrideWith
                          )
                      );

        $this->createSubscriptionRegistration(['id' => $subscriptionRegistrationId]);

        return $order;
    }

    protected function createSubscriptionRegistration(array $overrideWith = [])
    {
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
                                 'auth_type'       => 'physical',
                             ],
                             $overrideWith
                        )
                     );

        $this->createPaperMandate(['id' => $paperMandateId]);

        return $subscriptionRegistration;
    }

    protected function createPaperMandate(array $overrideWith = [])
    {
        $bankAccountId = UniqueIdEntity::generateUniqueId();

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
                        'terminal_id'       => '1citinachDTmnl',
                        'form_checksum'     => 'XXXXXXX',
                        'generated_file_id' => 'aaaaaaaaaaaaaa',
                    ],
                    $overrideWith
                )
            );

        $this->createBankAccount(['id' => $bankAccountId]);

        return $paperMandate;
    }

    protected function createBankAccount(array $overrideWith = [])
    {
        $bankAccount = $this->fixtures
            ->create(
                'bank_account',
                array_merge(
                    [
                        'ifsc_code'        => 'HDFC0000123',
                        'account_number'   => '1111111111111',
                        'account_type'     => 'savings',
                        'beneficiary_name' => 'TEST',
                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }

    protected function createAndPutImageFileInRequest(string $callee)
    {
        $uploadedFile = $this->createUploadedFile(__DIR__ . '/Helpers/sample_uploaded.jpeg');

        $this->testData[$callee]['request']['files']['form_uploaded'] = $uploadedFile;
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

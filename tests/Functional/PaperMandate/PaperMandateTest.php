<?php

namespace RZP\Tests\Functional\PaperMandate;

use Mockery;
use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Models\PaperMandate;
use Illuminate\Http\UploadedFile;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Terminal;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaperMandateTest extends TestCase
{
    use DbEntityFetchTrait;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/PaperMandateTestData.php';

        parent::setUp();

        (new Terminal)->createNachTerminal();

        $this->ba->proxyAuth();
    }

    public function testCreateAuthLinkForPaperMandate()
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

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->mockExtractNACH($merchant);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::AUTHENTICATED, $paperMandate->getStatus());
    }

    public function testAuthenticatePaperMandateWithoutCustomerSign()
    {
        $this->ba->publicAuth();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->mockExtractNACHWithoutCustomerSignature($merchant);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::CREATED, $paperMandate->getStatus());
    }

    public function testAuthenticatePaperMandateWithWrongAccountNumber()
    {
        $this->ba->publicAuth();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->mockExtractNACHWithWrongAccountNumber($merchant);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::CREATED, $paperMandate->getStatus());
    }

    public function testAuthenticatePaperMandateWithTertiarySignaturePresentWithoutSecondary()
    {
        $this->ba->publicAuth();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->mockExtractNACHWithTertiarySignaturePresentWithoutSecondary($merchant);

        $this->createOrder();

        $this->createAndPutImageFileInRequest(__FUNCTION__);

        $this->startTest();

        $paperMandate = $this->getDbLastEntity(Entity::PAPER_MANDATE);

        $this->assertEquals(PaperMandate\Status::CREATED, $paperMandate->getStatus());
    }

    protected function mockExtractNACHWithTertiarySignaturePresentWithoutSecondary($merchant)
    {
        $this->testData['hyperVergeExtractNACHOutput']['details']['signaturePresentSecondary']['value'] = 'no';
        $this->testData['hyperVergeExtractNACHOutput']['details']['signaturePresentTertiary']['value'] = 'yes';

        $this->mockExtractNACH($merchant);
    }

    protected function mockExtractNACHWithWrongAccountNumber($merchant)
    {
        $this->testData['hyperVergeExtractNACHOutput']['details']['accountNumber']['value'] = '000';

        $this->mockExtractNACH($merchant);
    }

    protected function mockExtractNACHWithLessConfidentAccountNumber($merchant)
    {
        $this->testData['hyperVergeExtractNACHOutput']['details']['accountNumber']['to-be-reviewed'] = 'yes';

        $this->testData['hyperVergeExtractNACHOutput']['details']['accountNumber']['value'] = '000';

        $this->mockExtractNACH($merchant);
    }

    protected function mockExtractNACHWithoutCustomerSignature($merchant)
    {
        $this->testData['hyperVergeExtractNACHOutput']['details']['signaturePresentPrimary']['value'] = 'no';

        return $this->mockExtractNACH($merchant);
    }

    protected function mockExtractNACH($merchant, $input = null)
    {
        $callable = function () use ($input, $merchant)
        {
            if ($input !== null)
            {
                return $input;
            }

            $this->testData['hyperVergeExtractNACHOutput']['details']['companyName']['value'] = strtoupper($merchant->getName());

            return $this->testData['hyperVergeExtractNACHOutput'];
        };

        return $this->mockHyperVerge($callable);
    }

    protected function mockGenerateNACH()
    {
        $callable = function ()
        {
            return ['outputImage' => base64_encode(file_get_contents(__DIR__ . '/Helpers/sample_form.pdf'))];
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
                        'id'              => '100000000order',
                        'amount'          => 100000,
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
                        'account_number' => '1111111111111',
                        'account_type'   => 'savings',

                    ],
                    $overrideWith
                )
            );

        return $bankAccount;
    }

    protected function createMerchant(array $overrideWith = [])
    {
        $merchant = $this->fixtures
            ->create(
                'merchant',
                array_merge(
                    [
                        'id' => '10000000000010',
                        'name' => 'TEST',

                    ],
                    $overrideWith
                )
            );

        return $merchant;
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
            filesize($url),
            null,
            true
        );
    }
}

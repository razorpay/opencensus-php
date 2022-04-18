<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Payment;
use RZP\Models\PaymentLink;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class ServiceTest extends BaseTest
{
    use PaymentTrait;
    use PaymentLinkTestTrait;

    protected $datahelperPath   = '/Helpers/ServiceTestData.php';

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';

    protected $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PaymentLink\Service::class);
    }

    /**
     * @group nocode_pp_service
     */
    public function testAppendAmountIfPossible()
    {
        $data       = $this->createPaymentLinkAndOrderForThat();
        $pl         = $data['payment_link'];
        $order      = $data['payment_link_order']['order'];
        $payment    = $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $order);
        $payload = [];
        $this->service->appendAmountIfPossible($pl->getPublicId(), [
            'razorpay_payment_id'   => $payment[Payment\Entity::ID]
        ], $payload);
        $this->assertEquals(15000, array_get($payload, PaymentLink\Entity::REQUEST_PARAMS.'.'.PaymentLink\Entity::AMOUNT));
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_service
     */
    public function testCustomAmountEncryptionDecryption($amount, $isValid)
    {
        $input = [PaymentLink\Entity::AMOUNT => $amount];

        if($isValid === false)
        {
            $this->expectException(BadRequestValidationFailureException::class);

            $this->expectExceptionMessage('The amount must be valid integer between 0 and 4294967295.');
        }

        $this->merchant = $this->fixtures->create('merchant');

        $encryptedAmount = $this->service->encryptAmountForPaymentHandle($input);

        $encryptedAmount = $encryptedAmount[PaymentLink\Entity::ENCRYPTED_AMOUNT];

        if($isValid === true)
        {
            // url decoding encrypted amount manually, as this happens automatically when query params come to service
            $decryptedAmount = $this->getDecryptedAmount(urldecode($encryptedAmount));

            $this->assertEquals($amount, $decryptedAmount);
        }
    }

    protected function getDecryptedAmount(string $encryptedAmount)
    {
        $input = [PaymentLink\Entity::AMOUNT => $encryptedAmount];

        $this->core = $this->app->make(PaymentLink\Core::class);

        $host = config('app.payment_handle_domain');

        $payload = [];

        $this->core->addCustomAmountForPaymentHandleIfRequired($payload, $host, $input);

        return $payload['data'][PaymentLink\Entity::PAYMENT_HANDLE_AMOUNT];
    }
}

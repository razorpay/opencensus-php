<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use DB;
use Mail;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\PaymentsUpiTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SavedVpaCustomerTokenTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/SavedVpaCustomerTokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures(['cardsaving', 'save_vpa']);

        $this->fixtures->create('customer:upi_payments_local_customer_token');

        $this->fixtures->create('customer:upi_payments_global_customer_token');

        $this->createUpiPaymentsGlobalCustomerVpa();

        $this->createUpiPaymentsLocalCustomerVpa();
    }

    public function testGetCustomerTokensWithSaveVpaFeatureEnabled()
    {
        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetCustomerTokensWithSaveVpaFeatureDisabled()
    {
        $this->fixtures->merchant->removeFeatures(['save_vpa']);

        $this->fixturesToCreateToken('100gcustltoken', '100000003card1', '411140', '10000000000000', '10000gcustomer', ['vault' => 'visa']);

        $this->mockSession();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testGetAllCustomerTokensWithSavedVpaFeature()
    {
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $this->ba->privateAuth();

        $this->startTest();
    }


    public function testAddCustomerTokenUpiBulkCron()
    {
        $customer = $this->fixtures->customer->create([
            'merchant_id' => Account::SHARED_ACCOUNT,
        ]);

        $payments = $this->createPaymentsWithNullGlobalToken($customer, 4);
        $tokens = $this->getDbEntities('token')->toArray();
        $beforeTokenCount = count($tokens);

        $response = $this->createCustomerTokenUpiBulkCron(4);
        $this->createCustomerTokenUpiBulkCron(4);

        $this->assertSame(4, $response['tokens_created']);
        $this->assertSame(0, $response['errors']);

        $tokens = $this->getDbEntities('token')->toArray();

        $afterTokenCount = count($tokens);
        $this->assertSame(4, $afterTokenCount - $beforeTokenCount);

        $tokens = array_slice($tokens, -4, 4);

        foreach ($payments as $payment)
        {
            $payment->refresh();

            $this->assertNotNull($payment->getGlobalTokenId());

            $vpa = $payment->getVpa();
            $vpaParts = explode('@', $vpa);

            $vpaEntity = $this->getDbEntity('payments_upi_vpa', [
                'username' => $vpaParts[0],
                'handle'   => $vpaParts[1],
            ]);

            $this->assertNotNull($vpaEntity);

            $tokenEntity = $this->getDbEntity('token', [
               'vpa_id'    => $vpaEntity->getId(),
            ]);

            $this->assertNotNull($tokenEntity);

            $this->assertArraySubset([
                'customer_id'       => $payment->getGlobalCustomerId(),
                'method'            => $payment->getMethod(),
                'id'                => $payment->getGlobalTokenId(),
                'merchant_id'       => Account::SHARED_ACCOUNT,
                'used_at'           => $payment->getCreatedAt(),
                'start_time'        => null,
                'terminal_id'       => null,
                'card_id'           => null,
                'vpa_id'            => $vpaEntity->getId(),
                'recurring'         => false,
                'confirmed_at'      => null,
                'rejected_at'       => null,
                'initiated_at'      => null,
                'acknowledged_at'   => null,
            ], $tokenEntity->toArray());
        }
    }

    protected function createPaymentsWithNullGlobalToken($customer, $times = 1)
    {
        $vpaPrefix = 'vpa1';
        $vpaSuffix = '@hdfcbank';

        $payments = [];
        for ($i = 0; $i < $times; $i++)
        {
            $payment = $this->fixtures->create('payment:upi_authorized', [
                Payment\Entity::VPA                  => $vpaPrefix.$i.$vpaSuffix,
                Payment\Entity::GLOBAL_CUSTOMER_ID   => $customer->getId(),
                Payment\Entity::AUTHORIZED_AT        => Carbon::now()->getTimestamp(),
            ]);
            $payments[] = $payment;
        }

        return $payments;
    }

    protected function createCustomerTokenUpiBulkCron($limit = 100)
    {
        $this->ba->cronAuth();

        $request = array(
            'url'     => '/tokens/upi/vpa/bulk',
            'method'  => 'post',
        );

        $request['content']['limit'] = $limit;

        return $this->makeRequestAndGetContent($request);
    }

    protected function mockSession()
    {
        $data = array(
            'test_app_token'   => 'capp_1000000custapp',
            'test_checkcookie' => '1'
        );

        $this->session($data);
    }

    protected function fixturesToCreateToken(
        $tokenId,
        $cardId,
        $iin,
        $merchantId = '100000Razorpay',
        $customerId = '10000gcustomer',
        $inputFields = []
    )
    {
        $this->fixtures->card->create(
            [
                'id'            => $cardId,
                'merchant_id'   => $merchantId,
                'name'          => 'test',
                'iin'           => $iin,
                'expiry_month'  => '12',
                'expiry_year'   => '2100',
                'issuer'        => 'HDFC',
                'network'       => $inputFields['network'] ?? 'Visa',
                'last4'         => '1111',
                'type'          => 'debit',
                'vault'         => $inputFields['vault'] ?? 'rzpvault',
                'vault_token'   => 'test_token',
                'international' => $inputFields['international'] ?? null,
            ]
        );

        $this->fixtures->token->create(
            [
                'id'              => $tokenId,
                'customer_id'     => $customerId,
                'token'           => '1000lcardtoken',
                'method'          => 'card',
                'card_id'         => $cardId,
                'used_at'         => 10,
                'merchant_id'     => $merchantId,
                'acknowledged_at' => Carbon::now()->getTimestamp(),
                'expired_at'      => $inputFields['expired_at'] ?? '9999999999',
                'status'          => $inputFields['status'] ?? 'active',
            ]
        );
    }
}

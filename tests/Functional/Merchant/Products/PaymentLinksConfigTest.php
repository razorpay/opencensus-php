<?php

namespace Functional\Merchant\Products;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

use RZP\Tests\Traits\TestsMetrics;

class PaymentLinksConfigTest extends OAuthTestCase
{
    use PartnerTrait;
    use WebhookTrait;
    use TestsMetrics;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentLinksConfigTestData.php';

        parent::setUp();

        $this->fixtures->connection('test')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);
        $this->fixtures->connection('live')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);

        $this->mockStorkService();
    }

    public function testCreateDefaultPaymentLinksConfigForActivatedMerchant()
    {
        Mail::fake();

        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $this->createMerchantFixtures($subMerchant, 'activated');

        $accountId = 'acc_' . $subMerchant->getId();

        $testData = $this->testData['testCreateDefaultPaymentLinksConfig'];

        $testData['response']['content']['activation_status'] = 'activated';

        $testData['response']['content']['account_id'] = $accountId;

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $now = Carbon::now()->getTimestamp();

        $response = $this->runRequestResponseFlow($testData);

        $this->assertGreaterThanOrEqual($now, $response['requested_at']);

    }

    public function testDefaultPaymentLinksConfig()
    {
        Mail::fake();

        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $this->createMerchantFixtures($subMerchant, null);

        $accountId = 'acc_' . $subMerchant->getId();

        $testData = $this->testData['testCreateDefaultPaymentLinksConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchPaymentLinksConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdatePaymentLinksConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];

        $this->runRequestResponseFlow($testData);
    }


    public function testPaymentLinksWebhookEvent()
    {
        Mail::fake();

        list($subMerchant, $partner) = $this->setupPrivateAuthForPartner();

        $this->createMerchantFixtures($subMerchant, 'under_review');

        $input = ['activation_status' => 'needs_clarification'];

        $testData = $this->testData['testCreateDefaultPaymentLinksConfig'];

        $testData['response']['content']['activation_status'] = 'needs_clarification';

        $merchantId = $subMerchant->getId();

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/products';

        unset($testData['response']['content']['activation_status']);

        $merchantProductResponse = $this->runRequestResponseFlow($testData);

        $eventFired = false;

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($merchantProductResponse, $merchantId, & $eventFired) {
                $this->validateStorkWebhookFireEvent($merchantProductResponse, $payload, $merchantId, $eventFired);

                return new \Requests_Response();
            });

        (new Detail\Core)->updateActivationStatus($subMerchant, $input, $subMerchant);

        $this->assertTrue($eventFired);
    }


    protected function validateStorkWebhookFireEvent($testData, $storkPayload, $merchantId, &$eventFired)
    {
        if ($storkPayload['event']['name'] === 'product.payment_links.needs_clarification')
        {
            $this->assertEquals('merchant', $storkPayload['event']['owner_type']);
            $this->assertEquals($merchantId, $storkPayload['event']['owner_id']);
            $merchantProductInPayload = [
                'id'                => $testData['id'],
                'merchant_id'       => 'acc_' . $merchantId,
                'activation_status' => 'needs_clarification'
            ];
            $completePayload          = json_decode($storkPayload['event']['payload'], true);
            $storkActualPayload       = $completePayload['payload'];
            $this->assertArraySelectiveEquals($storkActualPayload['merchant_product']['entity'], $merchantProductInPayload);
            $eventFired = true;
        }
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->createConfigForPartnerApp($app->getId());
        list($subMerchant) = $this->createSubMerchant($partner, $app);

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);

        return [$subMerchant, $partner];
    }

    private function createMerchantFixtures($subMerchant, $status)
    {
        $this->fixtures->on('live')->edit('merchant', $subMerchant->getId(), ['email' => 'testemail@gmail.com']);
        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), ['activation_status' => $status]);
        $this->fixtures->on('live')->create('user', ['name' => 'test', 'email' => 'testemail@gmail.com', 'contact_mobile' => '9999999999']);

        $this->fixtures->on('test')->edit('merchant', $subMerchant->getId(), ['email' => 'testemail@gmail.com']);
        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), ['activation_status' => $status]);
    }
}

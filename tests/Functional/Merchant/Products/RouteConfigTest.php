<?php

namespace Functional\Merchant\Products;

use Config;
use RZP\Constants\Mode;
use RZP\Models\Merchant\Account;
use Functional\Helpers\BvsTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BvsConstants;


class RouteConfigTest extends TestCase
{
    use BvsTrait;
    use WebhookTrait;
    use TerminalTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $terminalsServiceMock;

    const RZP_ORG = '100000razorpay';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/RouteConfigTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();

        $this->fixtures->connection('test')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);
        $this->fixtures->connection('live')->create('tnc_map', ['product_name' => 'all', 'content' => ['terms' => 'https://www.terms.com'], 'business_unit' => 'payments']);

        $this->mockStorkService();

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
    }

    public function testRouteDefaultConfig()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $testData = $this->testData['testCreateLinkedAccountWithMarketplaceFeature'];

        $linkedAccountResponse = $this->runRequestResponseFlow($testData);

        $linkedAccountId    =   $linkedAccountResponse['id'];

        $testData = $this->testData['testRouteDefaultConfig'];

        $testData['request']['url'] = '/v2/accounts/'.$linkedAccountId.'/products';

        $this->runRequestResponseFlow($testData);
    }

//    public function testRequestRouteProductLinkedAccountWithDifferentParentMId()
//    {
//        $this->ba->privateAuth();
//
//        $this->fixtures->merchant->addFeatures(['marketplace']);
//
//        $this->fixtures->merchant->create(['id' => '10000000000002']);
//
//        $linkedAccount = $this->fixtures->create('merchant:marketplace_account', ['parent_id' => '10000000000002']);
//
//        $linkedAccountId = 'acc_'.$linkedAccount->getId();
//
//        $testData = $this->testData['testRequestRouteProductLinkedAccountWithDifferentParentMId'];
//
//        $testData['request']['url'] = '/v2/accounts/'.$linkedAccountId.'/products';
//
//        $this->runRequestResponseFlow($testData);
//    }

    public function testUpdateRouteConfig()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $subMerchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail:associate_merchant',
            [
                'merchant_id' => $subMerchant->getId(),
                'business_type' => 4,
                'business_name' => 'Acme Corp'
            ]);

        $this->createMerchantFixtures($subMerchant, null);

        $accountId = 'acc_' . $subMerchant->getId();

        $testData = $this->testData['testRouteDefaultConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchRouteConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];

        $response = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testUpdateRouteConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];

        $this->runRequestResponseFlow($testData);
    }

//    public function testUpdateRouteConfigWithExtraFields()
//    {
//        $this->ba->privateAuth();
//
//        $this->fixtures->merchant->addFeatures(['marketplace']);
//
//        $subMerchant = $this->fixtures->create('merchant:marketplace_account');
//
//        $this->fixtures->create('merchant_detail:associate_merchant',
//                                    [
//                                        'merchant_id' => $subMerchant->getId(),
//                                        'business_type' => 4,
//                                        'business_name' => 'Acme Corp'
//                                    ]);
//
//        $this->createMerchantFixtures($subMerchant, null);
//
//        $accountId = 'acc_' . $subMerchant->getId();
//
//        $testData = $this->testData['testRouteDefaultConfig'];
//
//        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';
//
//        $response = $this->runRequestResponseFlow($testData);
//
//        $testData = $this->testData['testFetchRouteConfig'];
//
//        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];
//
//        $response = $this->runRequestResponseFlow($testData);
//
//        $testData = $this->testData['testUpdateRouteConfigWithExtraFields'];
//
//        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $response['id'];
//
//        $this->runRequestResponseFlow($testData);
//    }

    public function testRouteWebhookEvent()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $subMerchant = $this->fixtures->create('merchant:marketplace_account');

        $this->fixtures->create('merchant_detail:associate_merchant',
            [
                'merchant_id' => $subMerchant->getId(),
                'business_type' => 4,
                'business_name' => 'Acme Corp'
            ]);

        $this->createMerchantFixtures($subMerchant, null);

        $testData = $this->testData['testRouteDefaultConfig'];

        $merchantId = $subMerchant->getId();

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/products';

        $merchantProductResponse = $this->runRequestResponseFlow($testData);

        $eventFired = false;

        $this->mockServiceStorkRequest(
            function($path, $payload) use ($merchantProductResponse, $merchantId, & $eventFired) {
                $this->validateStorkWebhookFireEvent($merchantProductResponse, $payload, $merchantId, $eventFired);

                return new \Requests_Response();
            });

        $testData = $this->testData['testUpdateRouteConfig'];

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/products/'.$merchantProductResponse['id'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateStakeholder'];

        $testData['request']['url'] = '/v2/accounts/acc_' . $merchantId . '/stakeholders';

        $this->runRequestResponseFlow($testData);

        $this->assertTrue($eventFired);
    }
    public function testProductConfigUpdateAfterActivation()
    {
        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['marketplace', 'route_la_penny_testing']);

        $response = $this->makeRequestAndGetContent($this->testData['createActivatedLinkedAccount']['request']);

        $accountIdPublic = $response['id'];

        $accountId = Account\Entity::verifyIdAndSilentlyStripSign($response['id']);

        $this->mockBVSResponse($accountId);

        $testData = $this->testData['testRouteDefaultConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountIdPublic . '/products';

        unset($testData['response']['content']['active_configuration']['settlements']);

        $merchantProductResponse = $this->makeRequestAndGetContent($testData['request']);

        $testData = $this->testData['testUpdateRouteConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountIdPublic . '/products/' . $merchantProductResponse['id'];

        $this->runRequestResponseFlow($testData);

        $this->mockBVSResponse($accountId);

        $testData = $this->testData['testFetchRouteConfig'];

        $testData['request']['url'] = '/v2/accounts/' .$accountIdPublic . '/products/' . $merchantProductResponse['id'];

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals('activated', $response['activation_status']);

    }

    protected function validateStorkWebhookFireEvent($testData, $storkPayload, $merchantId, &$eventFired)
    {
        if ($storkPayload['event']['name'] === 'product.route.under_review')
        {
            $this->assertEquals('merchant', $storkPayload['event']['owner_type']);
            $merchantProductInPayload = [
                'id'                => $testData['id'],
                'merchant_id'       => 'acc_' . $merchantId,
                'activation_status' => 'under_review'
            ];
            $completePayload          = json_decode($storkPayload['event']['payload'], true);
            $storkActualPayload       = $completePayload['payload'];
            $this->assertArraySelectiveEquals($storkActualPayload['merchant_product']['entity'], $merchantProductInPayload);
            $eventFired = true;
        }
    }

    private function createMerchantFixtures($subMerchant, $status)
    {
        $this->fixtures->on('live')->edit('merchant', $subMerchant->getId(), ['email' => 'testemail@gmail.com']);
        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), ['activation_status' => $status]);
        $this->fixtures->on('live')->create('user', ['name' => 'test', 'email' => 'testemail@gmail.com', 'contact_mobile' => '9999999999']);

        $this->fixtures->on('test')->edit('merchant', $subMerchant->getId(), ['email' => 'testemail@gmail.com']);
        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), ['activation_status' => $status]);
    }

    private function mockBVSResponse($ownerId,
                                     $artefactType = 'bank_account',
                                     $response = 'success')
    {
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($ownerId, 'merchant', $artefactType);

        $this->assertNotEmpty($bvsValidation);

        $mockBvsValidationInput  =  $this->testData['mockBVSInputData'][$artefactType][$response];

        $mockBvsValidationInput['validation_id']    = $bvsValidation->getValidationId();

        (new KafkaMessageProcessor)->process(KafkaMessageProcessor::API_BVS_EVENTS, ['data' => $mockBvsValidationInput], Mode::TEST);
    }
}


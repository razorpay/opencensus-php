<?php

namespace Functional\Merchant\Products;

use Mail;
use RZP\Constants\Mode;
use RZP\Models\User\Role;
use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PaymentGatewayConfigTest extends OAuthTestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const RZP_ORG = '100000razorpay';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PaymentGatewayConfigTestData.php';
        parent::setUp();
    }

    public function testCreateDefaultPaymentGatewayConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $this->runRequestResponseFlow($testData);

        $merchantProduct = $this->getDbLastEntity('merchant_product');

        $merchantProductRequest = $this->getDbLastEntity('merchant_product_request');

        $this->validateMerchantProductRequest($merchantProduct, $merchantProductRequest);

    }

    public function testFetchDefaultPaymentGatewayConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testFetchDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);
    }

    public function testUpdatePaymentGatewayConfig()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testUpdatePaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

    }

    /**
     * This testcase validates the following
     * 1. Create an unregistered account through V2 API
     * 2. Create default payment gateway configuration
     * 3. Fetch the requirements for unregistered account
     * 4. Update settlement details
     * 5. Fetch and verify settlements requirements are not shown in requirements
     * 6. Create stakeholder for the account
     * 7. Update POI details for stakeholder
     * 8. Fetch and verify POI field requirements are not shown in requirements
     * 9. Upload POA documents for stakeholders
     * 10. Fetch and verify POA document requirements are not shown in requirements
     */
    public function testRequirementsForUnregisteredBusiness()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createUnregisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsForUnregisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateSettlementFields'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testRequirementsForUnregisteredBusinessAfterStakeholderDetailsSubmission'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharFront');

        $testData = $this->testData['testPostStakeholderDocumentAadharFront'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharBack');

        $testData = $this->testData['testPostStakeholderDocumentAadharBack'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

    }

    /**
     * This testcase validates the following
     * 1. Create an registered account through V2 API
     * 2. Create default payment gateway configuration
     * 3. Fetch the requirements for registered account
     * 4. Update settlement details
     * 5. Fetch and verify settlements requirements are not shown in requirements
     * 6. Create stakeholder for the account
     * 7. Update POI details for stakeholder
     * 8. Fetch and verify POI field requirements are not shown in requirements
     * 9. Upload POA documents for stakeholders
     * 10. Fetch and verify POA document requirements are not shown in requirements
     * 11. Update business identification fields for registered business
     * 12. Upload business identification documents for registered business
     * 13. Fetch and verify business identification requirements are not shown in requirements
     */
    public function testRequirementsForRegisteredBusiness()
    {
        Mail::fake();

        $this->setupPrivateAuthForPartner();

        $testData = $this->testData['createRegisteredBusinessTypeAccount'];

        $accountResponse = $this->runRequestResponseFlow($testData);

        $accountId = $accountResponse['id'];

        $testData = $this->testData['testCreateDefaultPaymentGatewayConfig'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products';

        $response = $this->runRequestResponseFlow($testData);

        $merchantProductId = $response['id'];

        $testData = $this->testData['testRequirementsForRegisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateSettlementFieldsForRegisteredBusiness'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateStakeholderForThinRequest'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders';

        $stakeholderResponse = $this->runRequestResponseFlow($testData);

        $stakeholderId = $stakeholderResponse['id'];

        $testData = $this->testData['testUpdateStakeholderDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId;

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testRequirementsForRegisteredBusinessAfterStakeholderDetailsSubmission'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharFront');

        $testData = $this->testData['testPostStakeholderDocumentAadharFront'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostStakeholderDocumentAadharBack');

        $testData = $this->testData['testPostStakeholderDocumentAadharBack'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/stakeholders/' . $stakeholderId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['updateBusinessProofDetails'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId;

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostBusinessProofDocument');

        $testData = $this->testData['testPostBusinessProofDocument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/documents';

        $this->runRequestResponseFlow($testData);

        $this->updateUploadDocumentData('testPostBusinessPanDocument');

        $testData = $this->testData['testPostBusinessPanDocument'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/documents';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testEmptyRequirements'];

        $testData['request']['url'] = '/v2/accounts/' . $accountId . '/products/' . $merchantProductId;

        $this->runRequestResponseFlow($testData);

    }

    private function validateMerchantProductRequest($merchantProduct, $merchantProductRequest, $partnerId = 'DefaultPartner')
    {
        $expected = [
            'requested_entity_id' => $partnerId,
            'merchant_product_id' => $merchantProduct->getId(),
            'id'                  => $merchantProductRequest->getId()];

        $this->assertArraySelectiveEquals($expected, $merchantProductRequest->toArrayPublic());
    }

    protected function setupPrivateAuthForPartner()
    {
        list($partner, $app) = $this->createPartnerAndApplication();
        $this->fixtures->merchant->activate($partner->getId());

        $this->fixtures->user->createUserForMerchant($partner->getId(), [], Role::OWNER, Mode::LIVE);

        $orgHostName = $this->fixtures->org->build('org_hostname', [
            'org_id'   => self::RZP_ORG,
            'hostname' => 'dashboard.razorpay.in'
        ]);

        $orgHostName->setConnection('live')->saveOrFail();

        $key = $this->fixtures->on(Mode::LIVE)->create('key', ['merchant_id' => $partner->getId()]);
        $key = 'rzp_live_' . $key->getKey();

        $this->ba->privateAuth($key);
    }

    protected function updateUploadDocumentData(string $callee)
    {
        $testData                             = &$this->testData[$callee];
        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../../Storage/k.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../../Storage/k.png'),
            null,
            true);
    }
}


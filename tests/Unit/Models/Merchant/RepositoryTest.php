<?php

namespace Unit\Models\Merchant;

use Config;
use RZP\Tests\Functional;
use Razorpay\Asv\Error\GrpcError;
use RZP\Models\Merchant\Repository;
use Rzp\Accounts\Merchant\V1\MerchantResponse;
use RZP\Models\Merchant\Entity as MerchantEntity;
use Rzp\Accounts\Merchant\V1\Merchant as MerchantProto;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant;

use const Grpc\STATUS_DEADLINE_EXCEEDED;

class RepositoryTest extends Functional\TestCase
{
    use Functional\AsvFindOrFailAndFindOrFailPublicTrait;

    private $merchantEntityJson1 = '{
        "id": "CzmiCwTPCL3t2K",
        "org_id": "100000razorpay",
        "name": "consequatur",
        "email": "test@razorpay.com",
        "account_code": null,
        "parent_id": null,
        "legal_entity_id": null,
        "activated": 0,
        "activated_at": 1687262076,
        "archived_at": null,
        "suspended_at": null,
        "live": 0,
        "live_disable_reason": null,
        "hold_funds": 0,
        "hold_funds_reason": null,
        "pricing_plan_id": null,
        "website": "http://mertz.com/est-nam-quos-iste-aliquid-vel-et-est-mollitia",
        "category": "5399",
        "international": 1,
        "product_international": "1111000000",
        "billing_label": "Test Merchant",
        "display_name": null,
        "channel": "axis",
        "transaction_report_email": "test@razorpay.com",
        "fee_bearer": 0,
        "fee_model": 0,
        "fee_credits_threshold": null,
        "amount_credits_threshold": null,
        "refund_credits_threshold": null,
        "refund_source": 0,
        "linked_account_kyc": 0,
        "has_key_access": 0,
        "partner_type": null,
        "brand_color": null,
        "handle": null,
        "activation_source": null,
        "signup_source": null,
        "business_banking": 0,
        "logo_url": null,
        "icon_url": null,
        "invoice_label_field": null,
        "risk_rating": 3,
        "risk_threshold": null,
        "receipt_email_enabled": 1,
        "receipt_email_trigger_event": 1,
        "max_payment_amount": null,
        "max_international_payment_amount": null,
        "auto_refund_delay": null,
        "default_refund_speed": "normal",
        "auto_capture_late_auth": 0,
        "convert_currency": null,
        "category2": null,
        "invoice_code": "123456789011",
        "notes": null,
        "whitelisted_ips_live": null,
        "whitelisted_ips_test": null,
        "whitelisted_domains": null,
        "second_factor_auth": 0,
        "restricted": 0,
        "dashboard_whitelisted_ips_live": null,
        "dashboard_whitelisted_ips_test": null,
        "partnership_url": null,
        "external_id": null,
        "purpose_code": null,
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "signup_via_email": 1,
        "balance_threshold": null,
        "audit_id": "M4Au4oJAxUkdNV",
        "country_code": "IN"
     }';

    /**
     * @throws \Exception
     */
    public function testMerchantRepositoryFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantInDatabase($this->merchantEntityJson1);

        $merchantEntity1 = $this->getMerchantEntityFromJson($this->merchantEntityJson1);
        $merchantEntity1Array = $merchantEntity1->toArray();
        $merchantProto1 = $this->getMerchantProtoFromJson($this->merchantEntityJson1);

        // Test Case 1 - ExcludedRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");


        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantEntityForFindOrFail = $repo->findOrFail("CzmiCwTPCL3t2K", ["id"]);
        $merchantEntityForFindOrFailPublic = $repo->findOrFailPublic("CzmiCwTPCL3t2K", ["id"]);
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFail->toArray());
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFailPublic->toArray());

        // Test Case 5 - SaveRoute false - array of ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantEntityForFindOrFail = $repo->findOrFail(["CzmiCwTPCL3t2K"]);
        $merchantEntityForFindOrFailPublic = $repo->findOrFailPublic(["CzmiCwTPCL3t2K"]);
        $merchantEntityForFindOrFailArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantEntity1Array, $merchantEntityForFindOrFail->first()->toArray());
        $merchantEntityForFindOrFailPublicArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantEntity1Array, $merchantEntityForFindOrFailPublic->first()->toArray());
        $merchantEntityForFindOrFailArray['audit_id'] = $merchantEntity1Array['audit_id'];
        $merchantEntityForFindOrFailPublicArray['audit_id'] = $merchantEntity1Array['audit_id'];
        $this->assertEquals($merchantEntity1Array, $merchantEntityForFindOrFailArray);
        $this->assertEquals($merchantEntity1Array, $merchantEntityForFindOrFailPublicArray);

        $merchantResponse = (new MerchantResponse())->setMerchant($merchantProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 7 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service - Exception occurs fallback to DB
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", null, new GrpcError(STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");


        $repo = new Repository();

        // Test Case 8 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 9 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 10 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );

        // Test Case 11 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );
    }

    /**
     * @throws \Exception
     */
    protected function callFindOrFailAndFindOrFailPublicAndCompare($repo, $expectedMerchantArray, $merchantId)
    {
        $merchantEntityForFindOrFail = $repo->findOrFail($merchantId);
        $merchantEntityForFindOrFailPublic = $repo->findOrFailPublic($merchantId);
        $merchantEntityForFindOrFailArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantArray, $merchantEntityForFindOrFail->toArray());
        $merchantEntityForFindOrFailPublicArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantArray, $merchantEntityForFindOrFailPublic->toArray());
        $merchantEntityForFindOrFailArray['audit_id'] = $expectedMerchantArray['audit_id'];
        $merchantEntityForFindOrFailPublicArray['audit_id'] = $expectedMerchantArray['audit_id'];

        $this->assertEquals($expectedMerchantArray, $merchantEntityForFindOrFailArray);
        $this->assertEquals($expectedMerchantArray, $merchantEntityForFindOrFailPublicArray);
    }


    protected function removeNonExistingKeysFromEntityFetchedFromDB(array $entityArrayExpected, array $entityArrayFromDB): array
    {
        foreach ($entityArrayFromDB as $key => $value) {
            if (array_key_exists($key, $entityArrayExpected) !== true) {
                unset($entityArrayFromDB[$key]);
            }
        }

        return $entityArrayFromDB;
    }

    protected function setEntityMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchant = new Merchant();
        $merchantMockClient = $this->getMockClient();
        $merchantMockClient->expects($this->exactly($count))->method($method)->with($id, $merchant->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchant->getAsvSdkClient()->setMerchant($merchantMockClient);
    }

    private function createMerchantInDatabase($json)
    {
        $this->fixtures->create("merchant",
            $this->getMerchantEntityFromJson($json)->toArray(),
        );
    }

    private function getMerchantProtoFromJson(string $json): MerchantProto
    {
        $merchantProto = new MerchantProto();
        $merchantProto->mergeFromJsonString($json, false);
        return $merchantProto;
    }

    private function getMerchantEntityFromJson(string $json): MerchantEntity
    {
        $merchantArray = json_decode($json, true);
        $merchantEntity = new MerchantEntity();
        $merchantEntity->setRawAttributes($merchantArray);
        return $merchantEntity;
    }

    private function getMockClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\MerchantInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

}

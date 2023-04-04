<?php

namespace Unit\Services\Dcs;

use Razorpay\Dcs\DataFormatter;
use RZP\Error\ErrorCode;
use Razorpay\Dcs\Constants as DcsSdkConstants;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Services\Dcs\Features\Type;
use RZP\Services\Dcs\Features\Utility;
use RZP\Tests\TestCase;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

class DcsServiceConstantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $dcsReadEnabledFeatures = [
            "merchant" => [
                "eligibility_enabled" => "client",
                "auto_comm_inv_disabled"=>"direct",
                "cart_api_amount_check"=>"client",
                "order_receipt_unique"=>"client",
            ],
            "org"=>[
                "disable_free_credit_unreg"=>"client"
            ]
        ];

        (new AdminService)->setConfigKeys([ConfigKey::DCS_READ_WHITELISTED_FEATURES => $dcsReadEnabledFeatures]);
    }

    public function testDcsReadEnabledFeaturesByEntityType()
    {
        $res = DcsConstants:: dcsReadEnabledFeaturesByEntityType(null, false, true);
        $expected = [
            "eligibility_enabled" => "client",
            "auto_comm_inv_disabled"=>"direct",
            "cart_api_amount_check"=>"client",
            "order_receipt_unique"=>"client",
            "disable_free_credit_unreg"=>"client",
        ];
        $this->assertEquals($expected, $res,
            "read enabled config key fetch issue due to, fetching org+merchant with api names");

        $res = DcsConstants:: dcsReadEnabledFeaturesByEntityType("merchant", false, true);
        $expected = [
            "eligibility_enabled" => "client",
            "auto_comm_inv_disabled"=>"direct",
            "cart_api_amount_check"=>"client",
            "order_receipt_unique"=>"client",
        ];
        $this->assertEquals($expected, $res,
            "read enabled config key fetch issue, fetching merchant with api names");

        $res = DcsConstants:: dcsReadEnabledFeaturesByEntityType("org", false, true);
        $expected = [
            "disable_free_credit_unreg"=>"client",
        ];
        $this->assertEquals($expected, $res,
            "read enabled config key fetch issue, fetching org with api names");

        $res = DcsConstants:: dcsReadEnabledFeaturesByEntityType("", true, true);
        $expected = [
            "eligibility_enabled" => "client",
            "auto_invoice_generation_disabled"=>"direct",
            "cart_amount_check_enabled"=>"client",
            "receipt_unique_enabled"=>"client",
            "free_credit_unreg_disabled"=>"client",
        ];
        $this->assertEquals($expected, $res,
            "read enabled config key fetch issue, fetching org with dcs names");
    }

    public function testGetAPIFeatureNamesFromDcsNames()
    {
        $dcsFeatures = DcsConstants::$featureToDCSKeyMapping;
        $output = DcsConstants::getAPIFeatureNamesFromDcsNames($dcsFeatures);
        $this->assertEquals(sizeof($dcsFeatures), sizeof($output),
            "miss match in size of output and featureToDCSKeyMapping");

        $dcsFeatures = array_flip(DcsConstants::$apiFeatureNameToDCSFeatureName);
        $output = DcsConstants::getAPIFeatureNamesFromDcsNames($dcsFeatures);
        $this->assertEquals(sizeof($dcsFeatures), sizeof($output),
            "miss match in size of output and apiFeatureNameToDCSFeatureName");
    }

    public function testGetDcsFeatureNamesFromApiNames()
    {
        $dcsFeatures = DcsConstants::getAPIFeatureNamesFromDcsNames(DcsConstants::$featureToDCSKeyMapping);
        $output = DcsConstants::getDcsFeatureNamesFromApiNames($dcsFeatures);
        $this->assertEquals(sizeof($dcsFeatures), sizeof($output),
            "miss match in size of output and featureToDCSKeyMapping");
    }

    public function testDcsFeatureNameFromAPIName()
    {
        foreach (DcsConstants::$apiFeatureNameToDCSFeatureName as $apiName => $dcsName)
        {
            $output = DcsConstants::dcsFeatureNameFromAPIName($apiName);
            $this->assertEquals($dcsName, $output, "dcsFeature fetch mismatch");
            $this->assertNotEquals("",
                DcsConstants::$featureToDCSKeyMapping[$output], "$output missing in featureToDCSKeyMapping");
        }

        try
        {
            $output = DcsConstants::dcsFeatureNameFromAPIName("");
        }
        catch (\Throwable $e)
        {
            $this->assertEquals(ServerErrorException::class,
                get_class($e), "Error class miss match");
            $this->assertEquals(
                'Dcs feature name missing in $apiFeatureNameToDCSFeatureName please check',
                $e->getMessage(),
                "error message mismatch"
            );
            $this->assertEquals(ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                $e->getCode(), "Error Code mismatch");
        }
    }

    public function testApiFeatureNameFromDcsName()
    {
        foreach (DcsConstants::$featureToDCSKeyMapping as $dcsName => $key)
        {
            $output = DcsConstants::apiFeatureNameFromDcsName($dcsName);
            $dcsFeatureNameToAPIFeatureName = array_flip(DcsConstants::$apiFeatureNameToDCSFeatureName);

            $this->assertEquals($dcsFeatureNameToAPIFeatureName[$dcsName],
                $output, "apiFeature fetch from dcs Name mismatch");
        }

        try
        {
            $output = DcsConstants::apiFeatureNameFromDcsName("");
        }
        catch (\Throwable $e)
        {
            $this->assertEquals(ServerErrorException::class,
                get_class($e), "Error class miss match");
            $this->assertEquals(
                'Dcs feature name missing in $dcsFeatureNameToAPIFeatureName please check with dcs team',
                $e->getMessage(),
                "error message mismatch"
            );
            $this->assertEquals(ErrorCode::SERVER_ERROR_DCS_SERVICE_FAILURE,
                $e->getCode(), "Error Code mismatch");
        }
    }

    public function testIsShadowFeature()
    {
        $output = DcsConstants::isShadowFeature("on_client_shadow");
        $this->assertEquals(true, $output, "shadow  client variant changed");

        $output = DcsConstants::isShadowFeature("on_direct_dcs_shadow");
        $this->assertEquals(true, $output, "shadow direct variant changed");

        $output = DcsConstants::isShadowFeature("testing");
        $this->assertEquals(false, $output, "shadow variant changed");

        $output = DcsConstants::isShadowFeature("");
        $this->assertEquals(false, $output, "shadow variant changed");
    }

    public function testIsReverseShadowFeature()
    {
        $output = DcsConstants::isReverseShadowFeature("on_client_rs");
        $this->assertEquals(true, $output, "reverse shadow  client variant changed");

        $output = DcsConstants::isReverseShadowFeature("on_direct_dcs_rs");
        $this->assertEquals(true, $output, "reverse shadow direct variant changed");

        $output = DcsConstants::isReverseShadowFeature("testing");
        $this->assertEquals(false, $output, "reverse shadow variant changed");

        $output = DcsConstants::isReverseShadowFeature("");
        $this->assertEquals(false, $output, "reverse shadow variant changed");
    }

    public function testIsNewFeature()
    {
        $output = DcsConstants::isNewFeature("on_client_new");
        $this->assertEquals(true, $output, "new client variant changed");

        $output = DcsConstants::isNewFeature("on_direct_dcs_new");
        $this->assertEquals(true, $output, "new direct variant changed");

        $output = DcsConstants::isNewFeature("testing");
        $this->assertEquals(false, $output, "new variant changed");

        $output = DcsConstants::isNewFeature("");
        $this->assertEquals(false, $output, "new variant changed");
    }

    public function testIsDcsReadEnabledFeature()
    {
        $output = DcsConstants::isDcsReadEnabledFeature("cart_amount_check_enabled", true);
        $this->assertEquals(true, $output, "dcs feature mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("eligibility_enabled", true);
        $this->assertEquals(true, $output, "dcs feature mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("cart_api_amount_check", true);
        $this->assertEquals(false, $output, "dcs feature name mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("", true);
        $this->assertEquals(false, $output, "dcs feature name mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("cart_amount_check_enabled", false);
        $this->assertEquals(false, $output, "dcs feature mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("eligibility_enabled", false);
        $this->assertEquals(true, $output, "dcs feature mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("cart_api_amount_check", false);
        $this->assertEquals(true, $output, "dcs feature name mismatch");

        $output = DcsConstants::isDcsReadEnabledFeature("", false);
        $this->assertEquals(false, $output, "dcs feature name mismatch");
    }

    public function testGetAPIEntityTypeFromDCSType()
    {
        foreach (DcsConstants::$featureToDCSKeyMapping as $featureName => $dcsKey)
        {
            $data = DataFormatter::toKeyMapWithOutId($dcsKey);
            $this->assertNotEquals(null, $data, "key map with out Id map should not be null");
            $type = Type::getAPIEntityTypeFromDCSType($data[DcsSdkConstants::ENTITY]);
            $this->assertNotEquals("", $type, "api type cant be empty");
        }
        try
        {
            $type = Type::getAPIEntityTypeFromDCSType("dummy");
        }
        catch (\Throwable $e)
        {
            $this->assertEquals(BadRequestException::class, get_class($e), "Error class miss match");
            $this->assertEquals("SERVER_ERROR_DCS_SERVICE_FAILURE", $e->getCode(), "Error Code mismatch");
        }
    }

    public function testToKeyMapWithOutId()
    {
       $data = DataFormatter::toKeyMapWithOutId("rzp/pg/merchant/affordability/Widget");
       $this->assertEquals("rzp/pg", $data[DcsSdkConstants::NAMESPACE], "namespace mismatch");
       $this->assertEquals("merchant", $data[DcsSdkConstants::ENTITY], "entity mismatch");
       $this->assertEquals("affordability", $data[DcsSdkConstants::DOMAIN], "domain mismatch");
       $this->assertEquals("Widget", $data[DcsSdkConstants::OBJECT_NAME], "objectName mismatch");

        $data = DataFormatter::toKeyMapWithOutId("rzp/pg/merchant/order/cart/Features");
        $this->assertEquals("rzp/pg", $data[DcsSdkConstants::NAMESPACE], "namespace mismatch");
        $this->assertEquals("merchant", $data[DcsSdkConstants::ENTITY], "entity mismatch");
        $this->assertEquals("order/cart", $data[DcsSdkConstants::DOMAIN], "domain mismatch");
        $this->assertEquals("Features", $data[DcsSdkConstants::OBJECT_NAME], "objectName mismatch");

        $data = DataFormatter::toKeyMapWithOutId("rzp/pg/merchant/order/cart/Features");
        $this->assertEquals("rzp/pg", $data[DcsSdkConstants::NAMESPACE], "namespace mismatch");
        $this->assertEquals("merchant", $data[DcsSdkConstants::ENTITY], "entity mismatch");
        $this->assertEquals("order/cart", $data[DcsSdkConstants::DOMAIN], "domain mismatch");
        $this->assertEquals("Features", $data[DcsSdkConstants::OBJECT_NAME], "objectName mismatch");

        $data = DataFormatter::toKeyMapWithOutId("rzp/pg/merchant");
        $this->assertEquals(null, $data, "Invalid key");

        $data = DataFormatter::toKeyMapWithOutId("");
        $this->assertEquals(null, $data, "Invalid key");

        foreach (DcsConstants::$featureToDCSKeyMapping as $featureName => $dcsKey)
        {
            $data = DataFormatter::toKeyMapWithOutId($dcsKey);
            $this->assertNotEquals(null, $data, "key map with out Id map should not be null");
            $this->assertNotEquals("", $data[DcsSdkConstants::NAMESPACE], "namespace cant be empty");
            $values = explode("/", $data[DcsSdkConstants::NAMESPACE]);
            $this->assertEquals(2, sizeof($values), "namespace length mismatch");
            $this->assertNotEquals("", $data[DcsSdkConstants::ENTITY], "entity cant be empty");
            $this->assertNotEquals("", $data[DcsSdkConstants::DOMAIN], "domain cant be empty");
            $this->assertNotEquals("", $data[DcsSdkConstants::OBJECT_NAME], "objectName cant be empty");
        }
    }

    public function testConvertDCSKeyToClassName()
    {
        foreach (DcsConstants::$featureToDCSKeyMapping as $featureName => $dcsKey)
        {
            $keyClass = DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($dcsKey));
            $class = new $keyClass();
        }

        try
        {
            $keyClass = DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey("rzp/pg/merchnat/refund/Features"));
            $class = new $keyClass();
        }
        catch (\Throwable $ex)
        {
            $this->assertEquals("Error", get_class($ex));
            $this->assertEquals('Class "Rzp\Pg\Merchnat\Refund\Features" not found', $ex->getMessage());        }
    }

    public function testDataFormatterMarshaller()
    {
        $allEligibleFields = [];
        foreach (DcsConstants::$featureToDCSKeyMapping as $featureName => $dcsKey)
        {
            $class = DataFormatter::convertDCSKeyToClassName(DataFormatter::convertKeyStringToDCSKey($dcsKey));
            $fields = DataFormatter::getFields($class);
            foreach ($fields as $fieldname => $getter)
            {
                $allEligibleFields[$fieldname] = $getter;
                $actualName = Utility::extractActualDcsName($featureName);
                $output = DataFormatter::marshal([$actualName => true], $class);
                $this->assertNotEquals("", $output);
            }
        }

        foreach (DcsConstants::$apiFeatureNameToDCSFeatureName as $apiName => $dcsName)
        {
            $actualDcsName = Utility::extractActualDcsName($dcsName);
            $this->assertTrue(key_exists($actualDcsName, $allEligibleFields), "name is missing in sdk fields");
        }
    }
}

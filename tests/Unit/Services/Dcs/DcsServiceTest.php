<?php

namespace Unit\Services\Dcs;

use Razorpay\Dcs\Constants;
use Razorpay\Dcs\DataFormatter;
use Razorpay\Dcs\Kv\V1\ApiException;
use Razorpay\Dcs\Kv\V1\Model\V1GetEntityAggregateResponse;
use Razorpay\Dcs\Kv\V1\Model\V1GetResponse;
use Razorpay\Dcs\Kv\V1\Model\V1Key;
use Razorpay\Dcs\Kv\V1\Model\V1KeyValue;
use Razorpay\Dcs\Kv\V1\Model\V1PatchResponse;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Entity;
use RZP\Services\Dcs\Features\Type;
use RZP\Services\Dcs\Cache;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use RZP\Tests\TestCase;

class DcsServiceTest extends TestCase
{
    protected $dcsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dcsService = $this->app['dcs'];
    }

    public function testEditFeatureDirectDcs()
    {
        $data = [
            Entity::NAME => 'disable_amount_check',
            Entity::ENTITY_TYPE => Type::MERCHANT,
            Entity::ENTITY_ID => "LNWDzDK1sqQnjY",
        ];

        $entity = (new Entity)->build($data);
        $entity->setEntityType(Type::MERCHANT);
        $entity->setEntityId("LNWDzDK1sqQnjY");

        $testMockClient = $this->dcsService->client("test");

        /*
        ##############################################################
        #############  ON_DIRECT_DCS_NEW TESTING #####################
        ##############################################################
        */
        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo) {
                    $res = new V1PatchResponse();
                    $key = new \Razorpay\Dcs\Kv\V1\Model\V1Key($data);
                    $key->setEntityId($entityId);
                    $res->setKey($key);
                    return $res;
                }
            );

        $this->dcsService->editFeature($entity, "on_direct_dcs_new", true, "test");
        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo) {
                    throw new ApiException(
                        "unauthorized Error on_direct_dcs_new",
                        403
                    );
                }
            );
        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("unauthorized Error on_direct_dcs_new");
        $this->dcsService->editFeature($entity, "on_direct_dcs_new", true, "test");
    }

    public function testEditFeatureDirectDcsShadow()
    {
        $data = [
            Entity::NAME => 'disable_amount_check',
            Entity::ENTITY_TYPE => Type::MERCHANT,
            Entity::ENTITY_ID => "LNWDzDK1sqQnjY",
        ];

        $entity = (new Entity)->build($data);
        $entity->setEntityType(Type::MERCHANT);
        $entity->setEntityId("LNWDzDK1sqQnjY");

        $testMockClient = $this->dcsService->client("test");
        /*
               ##############################################################
               #############  ON_DIRECT_DCS_SHADOW TESTING ##################
               ##############################################################
        */
        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo) {
                    $res = new V1PatchResponse();
                    $key = new \Razorpay\Dcs\Kv\V1\Model\V1Key($data);
                    $key->setEntityId($entityId);
                    $res->setKey($key);
                    return $res;
                }
            );

        $this->dcsService->editFeature($entity, "on_direct_dcs_shadow", true, "test");

        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo)
                {
                    throw new ApiException(
                        "unauthorized Error",
                        403
                    );
                }
            );
        $this->dcsService->editFeature($entity, "on_direct_dcs_shadow", true, "test");
    }

    public function testEditFeatureControlNewFeature(): void
    {
        /*
               ##############################################################
               #############  CONTROL_FOR_NEW_FEATURE_TESTING ###############
               ##############################################################
        */
        $data = [
            Entity::NAME => 'enable_merchant_expiry_pp',
            Entity::ENTITY_TYPE => Type::MERCHANT,
            Entity::ENTITY_ID => "LNWDzDK1sqQnjY",
        ];

        $entityNewfeature = (new Entity)->build($data);
        $entityNewfeature->setEntityType(Type::MERCHANT);
        $entityNewfeature->setEntityId("LNWDzDK1sqQnjY");

        $this->expectException("RZP\Exception\ServerErrorException");
        $this->expectExceptionMessage("dcs service is disabled, please check with dcs team");
        $this->dcsService->editFeature($entityNewfeature, "control", true, "test");
    }

    public function testEditFeatureOnDirectDcsRS(): void
    {
        $data = [
            Entity::NAME => 'disable_amount_check',
            Entity::ENTITY_TYPE => Type::MERCHANT,
            Entity::ENTITY_ID => "LNWDzDK1sqQnjY",
        ];

        $entity = (new Entity)->build($data);
        $entity->setEntityType(Type::MERCHANT);
        $entity->setEntityId("LNWDzDK1sqQnjY");

        /*
               ##############################################################
               #############  ON_DIRECT_DCS_RS TESTING ######################
               ##############################################################
        */
        $testMockClient = $this->dcsService->client("test");
        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo) {
                    $res = new V1PatchResponse();
                    $key = new \Razorpay\Dcs\Kv\V1\Model\V1Key($data);
                    $key->setEntityId($entityId);
                    $res->setKey($key);
                    return $res;
                }
            );

        $this->dcsService->editFeature($entity, "on_direct_dcs_rs", true, "test");
        $testMockClient->shouldReceive('patch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, string $entityId, string $value,
                          array $modifiedFields, array $auditInfo)
                {
                    throw new ApiException(
                        "unauthorized Error on_direct_dcs_rs",
                        403
                    );
                }
            );
        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("unauthorized Error on_direct_dcs_rs");
        $this->dcsService->editFeature($entity, "on_direct_dcs_rs", true, "test");
    }

    public function testFetchByEntityIdAndName()
    {
        $testMockClient = $this->dcsService->client("test");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($entityIds as $id) {
                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($id)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("CAE=");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndName("LNWDzDK1sqQnjY", "enable_merchant_expiry_pp", "test");
        $this->assertNotNull($res, "response shouldn't come as null");
        $this->assertEquals("enable_merchant_expiry_pp", $res->getName(), "Feature Name Miss match");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($entityIds as $id) {
                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($id)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndName("LNWDzDK1sqQnjY", "enable_merchant_expiry_pp", "test");
        $this->assertNull($res, "response should  come as null");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    throw new ApiException(
                        "unauthorized Error",
                        403
                    );
                }
            );
        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("unauthorized Error");
        $res = $this->dcsService->fetchByEntityIdAndName("LNWDzDK1sqQnjY", "enable_merchant_expiry_pp", "test");
    }

    public function testFetchByEntityIdAndFeatureNames()
    {
        $testMockClient = $this->dcsService->client("test");
        /*
         * ######################################################################
         * ########################### TESTCASE 1 ###############################
         * ######################################################################
         */
        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $keysFieldsMap, string $entityId, bool $aggregate, string $entity) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($keysFieldsMap as $key => $fields)
                    {
                        $data = DataFormatter::toKeyMapWithOutId($key);

                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($entityId)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("CAE=");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndFeatureNames("LNWDzDK1sqQnjY",
            ["payment_link_no_expiry_enabled"], "test", false, "merchant");
        $this->assertNotNull($res, "response shouldn't come as null");
        $this->assertEquals(1, sizeof($res), "response should have only one element");
        $this->assertEquals("enable_merchant_expiry_pl",
            $res[0],
            "response name mismatch");

        /*
         * ######################################################################
         * ########################### TESTCASE 2 ###############################
         * ######################################################################
         */

        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $keysFieldsMap, string $entityId, bool $aggregate, string $entity) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($keysFieldsMap as $key => $fields)
                    {
                        $data = DataFormatter::toKeyMapWithOutId($key);

                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($entityId)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndFeatureNames("LNWDzDK1sqQnjP",
            ["payment_link_no_expiry_enabled"], "test", false, "merchant");
        $this->assertEmpty($res, "response should  come as empty");
        $this->assertEquals(0, sizeof($res), "response should have only one element");

        /*
         * ######################################################################
         * ########################### TESTCASE 3 ###############################
         * ######################################################################
         */

        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $keysFieldsMap, string $entityId, bool $aggregate, string $entity) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($keysFieldsMap as $key => $fields)
                    {
                        $data = DataFormatter::toKeyMapWithOutId($key);

                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($entityId)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("CAE=");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndFeatureNames("LNWDzDK1sqQnjZ",
            [
                "payment_link_no_expiry_enabled",
                "eligibility_enabled",
                "payment_page_customer_decide_amount_enabled",
                "payment_page_create_own_template_enabled"
            ], "test", false, "merchant");
        $this->assertNotEmpty($res, "response shouldnt come as empty");

        $this->assertEquals(2, sizeof($res), "response should have only 2 elements");

        /*
         * ######################################################################
         * ########################### TESTCASE 4 ###############################
         * ######################################################################
         */

        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $keysFieldsMap, string $entityId, bool $aggregate, string $entity) {
                    throw new ApiException(
                        "unauthorized Error",
                        403
                    );
                }
            );
        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("unauthorized Error");

        $res = $this->dcsService->fetchByEntityIdAndFeatureNames("LNWDzDK1sqQnjY",
            ["payment_link_no_expiry_enabled"], "test", false, "merchant");
    }

    public function testFetchByEntityIdsAndName()
    {
        $testMockClient = $this->dcsService->client("test");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($entityIds as $id) {
                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($id)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("CAE=");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdsAndName(["LNWDzDK1sqQnjY"], "enable_merchant_expiry_pp", "test");
        $this->assertNotNull($res, "response shouldn't come as null");
        $this->assertEquals("enable_merchant_expiry_pp", $res[0]->getName(), "Feature Name Miss match");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($entityIds as $id) {
                        $key = (new V1Key())->setNamespace($data[Constants::NAMESPACE])
                            ->setEntity($data[Constants::ENTITY])
                            ->setEntityId($id)
                            ->setDomain($data[Constants::DOMAIN])
                            ->setObjectName($data[Constants::OBJECT_NAME]);
                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("");
                        $kvs[] = $kv;
                    }
                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchByEntityIdsAndName(["LNWDzDK1sqQnjY"], "enable_merchant_expiry_pp", "test");
        $this->assertEmpty($res, "response should  come as null");

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    throw new ApiException(
                        "unauthorized Error",
                        403
                    );
                }
            );
        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("unauthorized Error");
        $res = $this->dcsService->fetchByEntityIdsAndName(["LNWDzDK1sqQnjY"], "enable_merchant_expiry_pp", "test");
    }

    public function testFetchByEntityIdAndEntityType()
    {
        $testMockClient = $this->dcsService->client("test");
        $dcsReadEnabledFeatures = [
            "merchant" => [
                "google_pay_omnichannel" => "direct",
                "excess_order_amount" => "direct",
                "disable_amount_check" => "direct",
            ],
            "org" => [

            ]
        ];
        DcsConstants::$loadedReadEnabledFeatures = [];
        (new AdminService())->setConfigKeys([ConfigKey::DCS_READ_WHITELISTED_FEATURES => $dcsReadEnabledFeatures]);

        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $data, $entityId, $aggregate,
                                $entityType, $disableCache) {

                    $this->assertNotEmpty($entityType, "Entity Type will not be empty");
                    $this->assertTrue($disableCache, "Cache Should be disabled in library");
                    $return_value =  new V1GetResponse();;
                    $keysWithIDAndFieldsEnabled = [
                        "rzp/pg/merchant/upi/collect/Omnichannel"  => [
                            "LNWDzDK1sqQnjY" => [
                                "google_pay_omnichannel"
                            ],
                        ],
                        "rzp/pg/merchant/order/payments/Features"  => [
                            "LNWDzDK1sqQnjY" => [
                                "excess_order_amount_enabled",
                                "allow_payments_on_paid_order"
                            ],
                        ],
                        "rzp/pg/merchant/order/cart/Features"  => [
                            "LNWDzDK1sqQnjY" => [],
                        ],
                    ];
                    $res123 = $this->buildDcsKvsResponse($keysWithIDAndFieldsEnabled);
                    $return_value->setKvs($res123);
                    return $return_value;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndEntityType("LNWDzDK1sqQnjY",
            Type::MERCHANT, "test");
        $this->assertNotNull($res, "response shouldn't come as null");
        foreach (["google_pay_omnichannel",
                     "disable_amount_check",
                     "excess_order_amount"] as $field) {
            $this->assertEquals(["google_pay_omnichannel",
                "excess_order_amount",
                "disable_amount_check"],
                $res->pluck(Entity::NAME)->toArray(),
                "response should contain 3 elements");
        }

        $val = (new Cache())->get('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
        $this->assertEquals($val,
            $res->pluck(Entity::NAME)->toArray(),
            "cache should contain all elements in response");
        $this->testEditFeatureDirectDcs();
        $val = (new Cache())->get('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
        $this->assertEquals(null,
            $val,
            "cache should contain null value");
       (new Cache())->remove('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
    }

    public function testFetchByEntityIdAndEntityTypeWithDisabledFeatures()
    {
        $testMockClient = $this->dcsService->client("test");
        $dcsReadEnabledFeatures = [
            "merchant" => [
                "google_pay_omnichannel" => "direct",
                "excess_order_amount" => "direct",
            ],
            "org" => [

            ]
        ];
        DcsConstants::$loadedReadEnabledFeatures = [];
        (new AdminService())->setConfigKeys([ConfigKey::DCS_READ_WHITELISTED_FEATURES => $dcsReadEnabledFeatures]);

        $testMockClient->shouldReceive('fetchMultipleKeysWithID')
            ->times(1)
            ->andReturnUsing(
                function (array $data, $entityId, $aggregate,
                                       $entityType, $disableCache) {

                    $this->assertNotEmpty($entityType, "Entity Type will not be empty");
                    $this->assertTrue($disableCache, "Cache Should be disabled in library");
                    $return_value =  new V1GetResponse();;
                    $keysWithIDAndFieldsEnabled = [
                        "rzp/pg/merchant/upi/collect/Omnichannel"  => [
                            "LNWDzDK1sqQnjY" => [
                                "google_pay_omnichannel"
                            ],
                        ],
                        "rzp/pg/merchant/order/payments/Features"  => [
                            "LNWDzDK1sqQnjY" => [
                                "excess_order_amount_enabled",
                                "allow_payments_on_paid_order"
                            ],
                        ],
                        "rzp/pg/merchant/order/cart/Features"  => [
                            "LNWDzDK1sqQnjY" => [],
                        ],
                    ];
                    $res123 = $this->buildDcsKvsResponse($keysWithIDAndFieldsEnabled);
                    $return_value->setKvs($res123);
                    return $return_value;
                }
            );

        $res = $this->dcsService->fetchByEntityIdAndEntityType("LNWDzDK1sqQnjY",
            Type::MERCHANT, "test");
        $this->assertNotNull($res, "response shouldn't come as null");
        $this->assertEquals([
            "google_pay_omnichannel",
            "excess_order_amount"],
            $res->pluck(Entity::NAME)->toArray(),
            "response should contain 2 elements");

        $val = (new Cache())->get('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
        $this->assertEquals($val,
            $res->pluck(Entity::NAME)->toArray(),
            "cache should contain all elements in response");
        $this->testEditFeatureDirectDcs();
        $val = (new Cache())->get('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
        $this->assertEquals(null,
            $val,
            "cache should contain null value");
        (new Cache())->remove('testing_test_dcs_fetch_by_id_type_LNWDzDK1sqQnjY_merchant');
    }

    public function testFetchByFeatureName()
    {
        $testMockClient = $this->dcsService->client("test");
        $dcsReadEnabledFeatures = [
            "merchant" => [
                "google_pay_omnichannel" => "direct",
                "excess_order_amount" => "direct",
                "disable_amount_check" => "direct",
            ],
            "org" => [

            ]
        ];
        DcsConstants::$loadedReadEnabledFeatures = [];
        (new AdminService())->setConfigKeys([ConfigKey::DCS_READ_WHITELISTED_FEATURES => $dcsReadEnabledFeatures]);
        (new Cache())->remove('testing_test_dcs_fetch_by_name_excess_order_amount');

        $testMockClient->shouldReceive('aggregateFetch')
            ->times(1)
            ->andReturnUsing(
                function (array $data, bool $disableCache) {

                    $this->assertEmpty($data[Constants::ENTITY_ID], "Entity Type will be empty");
                    $this->assertTrue($disableCache, "Cache Should be disabled in library");
                    $return_value = new V1GetEntityAggregateResponse();
                    $keysWithIDAndFieldsEnabled = [
                        "rzp/pg/merchant/order/payments/Features"  => [
                            "LNWDzDK1sqQnjY" => [
                                "allow_payments_on_paid_order"
                            ],
                            "LNWDzDK1sqQnjZ" => [
                                "excess_order_amount_enabled"
                            ],
                            "LNWDzDK1sqQnjX" => [
                                "excess_order_amount_enabled"
                            ],
                        ],
                    ];
                    $res123 = $this->buildDcsKvsResponse($keysWithIDAndFieldsEnabled);
                    $return_value->setKvs($res123);
                    return $return_value;
                }
            );

        $res = $this->dcsService->fetchByFeatureName("excess_order_amount",
            Type::MERCHANT, "test");

        $this->assertNotNull($res, "response shouldn't come as null");
        $this->assertEquals(["LNWDzDK1sqQnjZ",
            "LNWDzDK1sqQnjX"],
            $res->pluck(Entity::ENTITY_ID)->toArray(),
            "response should contain 3 elements");

        $val = (new Cache())->get('testing_test_dcs_fetch_by_name_excess_order_amount');
        $this->assertEquals($val,
            $res->pluck(Entity::ENTITY_ID)->toArray(),
            "cache should contain all elements in response");
        $this->testEditFeatureDirectDcs();
        $val = (new Cache())->get('testing_test_dcs_fetch_by_name_excess_order_amount');
        $this->assertEquals(null,
            $val,
            "cache should contain null value");
        (new Cache())->remove('testing_test_dcs_fetch_by_name_excess_order_amount');
    }

    public function testBuildDcsKvsResponse()
    {
        $keysWithIDAndFieldsEnabled = [
          "rzp/pg/merchant/upi/collect/Omnichannel"  => [
              "tedsting1dalk" => [
                  "google_pay_omnichannel"
              ],
              "tedsting1dalk1" => [
                  "google_pay_omnichannel"
              ],
              "tedsting1dalk2" => [],
          ],
            "rzp/pg/merchant/order/payments/Features"  => [
                "tedsting1dalk" => [
                    "excess_order_amount_enabled",
                    "allow_payments_on_paid_order"
                ],
                "tedsting1dalk1" => [
                    "excess_order_amount_enabled"
                ],
                "tedsting1dalk2" => [
                    "allow_payments_on_paid_order"
                ],
            ],
        ];
        $res = $this->buildDcsKvsResponse($keysWithIDAndFieldsEnabled);
        $this->assertEquals(6, sizeof($res), "response should have exactly 6 elements");
    }

    public function buildDcsKvsResponse($keysWithIDAndFieldsEnabled): array
    {
        $response = [];
        foreach ($keysWithIDAndFieldsEnabled as $keystr => $idWithFieldsEnabled)
        {
            foreach ($idWithFieldsEnabled as $id => $fields)
            {
                $key = DataFormatter::convertKeyStringToDCSKey($keystr);
                $key->setEntityId($id);
                $kv = new V1KeyValue();
                $kv->setKey($key);
                $request =  [];
                foreach ($fields as $field)
                {
                    $request[$field] = true;
                }
                $value = DataFormatter::marshal($request,
                    DataFormatter::convertDCSKeyToClassName($key));
                $kv->setValue($value);
                $response[] = $kv;
            }
        }
        return $response;
    }
}

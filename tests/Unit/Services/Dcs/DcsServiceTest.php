<?php

namespace Unit\Services\Dcs;

use Razorpay\Dcs\Constants;
use Razorpay\Dcs\DataFormatter;
use Razorpay\Dcs\Kv\V1\ApiException;
use Razorpay\Dcs\Kv\V1\Model\V1GetResponse;
use Razorpay\Dcs\Kv\V1\Model\V1Key;
use Razorpay\Dcs\Kv\V1\Model\V1KeyValue;
use Razorpay\Dcs\Kv\V1\Model\V1PatchResponse;
use RZP\Models\Feature\Entity;
use RZP\Services\Dcs\Features\Type;
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
            $res[0]->getName(),
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


}

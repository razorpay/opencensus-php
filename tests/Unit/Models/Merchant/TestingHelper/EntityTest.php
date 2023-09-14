<?php

namespace Unit\Models\Merchant\TestingHelper;

use Config;
use Razorpay\Asv\Error\GrpcError;
use RZP\Tests\Functional\TestCase;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;

class RepositoryTestHelper extends TestCase
{
    private $splitzResponse = [
        'id' => '10000000000000',
        'project_id' => 'K1ZCHBSn7hbCMN',
        'experiment' => [
            'id' => 'K1ZaAGS9JfAUHj',
            'name' => 'CallSyncDviationAPI',
            'exclusion_group_id' => '',
        ],
        'variant' => [
            'id' => 'K1ZaAHZ7Lnumc6',
            'name' => 'Dummy Enabled',
            'variables' => [
                [
                    'key' => 'enabled',
                    'value' => 'true',
                ]
            ],
            'experiment_id' => 'K1ZaAGS9JfAUHj',
            'weight' => 100,
            'is_default' => false
        ],
        'Reason' => 'bucketer',
        'steps' => [
            'sampler',
            'exclusion',
            'audience',
            'assign_bucket'
        ]
    ];

    public function runTestsForImplicitJoin( $entitiesData)
    {
        for ($i = 0; $i < count($entitiesData); $i++)
        {
            $data = $entitiesData[$i];
            if($data["isDependentEntity"])
            {
                for ($i = 0; $i < count($data["dependentEntity"]); $i++) {
                    $dependentData = $data["dependentEntity"][$i];
                    $this->createEntityInDatabase($dependentData["dependentEntityName"], $dependentData["dependentEntityData"], $dependentData["dependentEntityClass"]);
                }
            }
            $this->createEntityInDatabase($data["AssociatedEntityName"], $data["AssociatedEntityData"], $data["AssociatedEntityClass"]);

            if($data["shouldEntityNeedsToBeCreated"])
            {
                $this->createEntityInDatabase($data["entityName"], $data["entityData"], $data["entityClass"]);
            }

            $entityRepo = $data["AssociatedEntityRepo"];
            $relationName = $data["relationName"];
            $repoName = $data["entityRepoName"];
            $asvEntityClass = $data["asvEntity"];
            $setterFunction = $data["setterFunctionName"];
            $responseSetterFunctionName= $data["responseSetterFunctionName"];
            $asvResponseEntity = $data["asvResponseEntity"];
            $mockBuilderInterface = $data["mockBuilderInterface"];

            $associatedEntity = $entityRepo->find($data["merchant_id"]);
            $entity1 = $this->getEntityForJson($data["entityData"], $data["entityClass"]);
            $entity1Array = $entity1->toArray();
            $entityProto1 = $this->getEntityProtoForJson($data["entityData"], $data["entityProtoClass"]);

            // TestCase1 - when route belongs to exclusive flow splitz exp is off
            $this->setSplitzWithOutputForBulk(["false", "false"], 0);
            $entityRepo = $data["entityRepo"];
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            // TestCase2 - when route belongs to exclusive flow splitz exp is on
            $associatedEntity->unsetRelation($relationName);
            $this->setSplitzWithOutputForBulk(["true", "true"], 0);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase3- both experiment is false
            $associatedEntity->unsetRelation($relationName);
            $this->setSplitzWithOutputForBulk(["false", "false"],   1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase4 - experiment respons - false, true
            $associatedEntity->unsetRelation($relationName);
            $this->setSplitzWithOutputForBulk(["false", "true"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase5 - experiment respons - true, false
            $associatedEntity->unsetRelation($relationName);
            $this->setSplitzWithOutputForBulk(["true", "false"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase6 - call is going to asv
            $associatedEntity->unsetRelation($relationName);
            $entityResponse = $asvResponseEntity->$responseSetterFunctionName([$entityProto1]);
            $this->setEntityMockClientWithIdAndResponse($data["merchant_id"], $entityResponse, null, "getByMerchantId", 1, $asvEntityClass, $setterFunction, $mockBuilderInterface);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase7 - should go to account service - Exception occurs fallback to DB
            $associatedEntity->unsetRelation($relationName);
            $this->setEntityMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getByMerchantId", 1, $asvEntityClass, $setterFunction, $mockBuilderInterface);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            $this->updateAuditIdAndAssert($entityRepo, $associatedEntity, $entity1Array, $relationName, $repoName);

            //TestCase8 - Not found in asv;
            $associatedEntity->unsetRelation($relationName);
            $this->setEntityMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"), "getByMerchantId", 1, $asvEntityClass, $setterFunction, $mockBuilderInterface);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            app('repo')->$repoName = $entityRepo;
            $this->assertNull($associatedEntity->$relationName);

            //TestCase9 - invalid argument in asv;
            $associatedEntity->unsetRelation($relationName);
            $this->setEntityMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"), "getByMerchantId", 1, $asvEntityClass, $setterFunction, $mockBuilderInterface);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $entityRepo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
            app('repo')->$repoName = $entityRepo;
            $this->assertNull($associatedEntity->$relationName);
        }
    }

    public function createEntityInDatabase($entityName, $json, $entity)
    {
        $entityArray = json_decode($json, true);
        $entity->setRawAttributes($entityArray);
        $entityArray = $entity->toArray();

        if(array_key_exists('percentage_ownership', $entityArray)) {
            $entityArray['percentage_ownership'] = $entity->getAttributes()['percentage_ownership'];
        }

        $this->fixtures->create($entityName,
            $entityArray
        );
    }

    private function getEntityForJson($json, $entity){
        $entityArray = json_decode($json, true);
        $entity->setRawAttributes($entityArray);
        return $entity;
    }

    private function getEntityProtoForJson($json, $entityProto) {
        $entityProto->mergeFromJsonString($json, false);
        return $entityProto;
    }

    public function setEntityMockClientWithIdAndResponse($id, $response, $error, $method, $count, $entity, $setterFunction, $mockBuilderInterface)
    {
        $entityMockClient = $this->getMockClient($mockBuilderInterface);
        $entityMockClient->expects($this->exactly($count))->method($method)->with($id, $entity->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $entity->getAsvSdkClient()->$setterFunction($entityMockClient);
    }

    public function setSplitzWithOutputForBulk(array $output, $count = 1, $exception = false) {
        $response = [];
        for ($i = 0; $i < count($output); $i++) {
            $tempResponse = $this->splitzResponse;
            $tempResponse["variant"]["variables"][0]["value"] = $output[$i];
            $response[$i] = $tempResponse;
        }

        $splitzMock = $this->createSplitzMock(['bulkCallsToSplitz']);

        if ($exception) {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->willThrowException(new \Exception("sample"));
        } else {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->willReturn($response);
        }

        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return;
    }

    private function updateAuditIdAndAssert($entityRepo, $associatedEntity, $entityArray, $relationName, $repoName) {
        app('repo')->$repoName = $entityRepo;
        $entity = $associatedEntity->$relationName->toArray();
        $entity['audit_id'] = "testtesttest";
        $this->assertEquals($entityArray, $entity);
    }

    private function getMockClient($mockBuilderInterface) {
        return $this->getMockBuilder($mockBuilderInterface)
            ->enableOriginalConstructor()
            ->getMock();
    }

    public function getMockAsvRouterInRepository($method, $count, $response, $error)
    {
        $asvRouterMock = $this->getAsvRouteMock([$method]);
        if ($error === null) {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willReturn($response);
        } else {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willThrowException($error);
        }

        return $asvRouterMock;
    }

    private function getAsvRouteMock($methods = [])
    {
        return $this->getMockBuilder(AsvRouter::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }
}

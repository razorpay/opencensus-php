<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use App;
use RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData\TestDataInterface;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Base\RepositoryManager;
use RZP\Modules\Acs\Comparator;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

class Base
{
    protected $app;

    /** @var Logger */
    protected $trace;

    /** @var RepositoryManager */
    protected $repo;

    protected $merchantId;

    protected $parityCheckMethods;

    /**
     * @var Comparator\Base
     */
    protected $comparator;

    protected $website;

    protected TestDataInterface $testData;

    protected $entityRepoClass;

    protected $entityClass;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        $app = App::getFacadeRoot();
        $this->app = $app;
        $this->trace = $app[Constant::TRACE];
        $this->repo = $this->app[Constant::REPO];
        $this->comparator = new Comparator\Base();
        $this->merchantId = $merchantId;
        $this->parityCheckMethods = $parityCheckMethods;
    }

    /**
     * @param $entity1
     * @param $entity2
     * @return string[]
     */
    public function saveAndGetExceptions($entity1, $entity2): array
    {
        $apiExceptionMessage = "";
        $asvExceptionMessage = "";

        $repo = new $this->entityRepoClass();

        try {
            $repo->saveOrFail($entity1);
            $this->checkEntitySavedCorrectly($entity1);
        } catch (\Throwable $e) {
            $apiExceptionMessage = $e->getMessage();
        }

        try {
            $repo->saveOnAccountService($entity2);
            $this->checkEntitySavedCorrectly($entity2);
        } catch (\Throwable $e) {
            $asvExceptionMessage = $e->getMessage();
        }


        return array($apiExceptionMessage, $asvExceptionMessage);
    }

    public function checkEntitySavedCorrectly($entity): void
    {
        if(!$entity->exists){
            Throw new \Exception("Entity exists is set false after save/create.");
        }

        if($this->comparator->getExactDifference($entity->getRawOriginal(), $entity->getAttributes()) !== []){
            Throw new \Exception("Entity Original Attributes & Attributes is not same after save/create.");
        }
    }

    protected function compareAndLogApiAndAsvResponse(array $differenceRawAttributes, array $differenceArray, array $logDetailMatched,
                                                      array $additionalLogDetailUnmatched): void
    {
        if ($differenceRawAttributes === [] and $differenceArray === []) {
            $this->trace->info(TraceCode::ASV_COMPARE_MATCHED, $logDetailMatched);
        } else {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, array_merge($logDetailMatched, $additionalLogDetailUnmatched));
        }
    }

    protected function compareAndLogApiAndAsvResponseForNull(?array $apiResponse, ?array $asvResponse, array $logDetailMatched,
                                                             array  $additionalLogDetailUnmatched): void
    {
        if ($apiResponse === null and $asvResponse === null) {
            $this->trace->info(TraceCode::ASV_COMPARE_MATCHED, $logDetailMatched);
        } else {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, array_merge($logDetailMatched, $additionalLogDetailUnmatched));
        }
    }

    public function checkWriteParity(): array {
        $success = true;
        $details = [];

        $testData = $this->testData->getTestData();

        foreach($testData as $testDataItem) {
           $result =  $this->checkWriteParityForSingleEntity($testDataItem);
           $success = $success && $result['success'];
           $details[] = $result;
        }

        return [
            'success' => $success,
            'details' => $details,
        ];
    }

    private function checkWriteParityForSingleEntity(array $testDataItem): array
    {

        // not saved Entity
        $unsavedEntity = $this->getEntity($testDataItem);
        // Create ASV Entity
        $apiEntity = $this->getEntity($testDataItem);
        // Create API Entity
        $asvEntity = $this->getEntity($testDataItem);

        $response = $this->performParity($unsavedEntity, $apiEntity, $asvEntity, $testDataItem);

        if(!$this->shouldPerformUpdateParity($testDataItem)){
            return $response;
        }

        $unsavedEntity = $this->updateEntity($unsavedEntity, $testDataItem, false);
        $apiEntity = $this->updateEntity($apiEntity, $testDataItem, true);
        $asvEntity = $this->updateEntity($asvEntity, $testDataItem, true);

        if ($apiEntity == null or $asvEntity == null) {
            return [
                'success' => false,
                'details' => [
                    'message' => 'Error while updating entity.',
                ],
            ];
        }

        return $this->performParity($unsavedEntity, $apiEntity, $asvEntity, $testDataItem);
    }

    public function performParity($unsavedEntity, $apiEntity, $asvEntity, $testDataItem): array
    {
        list($apiExceptionMessage, $asvExceptionMessage) = $this->saveAndGetExceptions($apiEntity, $asvEntity);
        $response = $this->compareException($apiExceptionMessage, $asvExceptionMessage, $testDataItem, $this->getEntityId($apiEntity), $this->getEntityId($asvEntity));
        if(!$response['continue']) {
            return $response;
        }

        if($this->checkIfAuditedEntity($unsavedEntity) && !$this->checkAuditUnEqualAndLengthFourteen($unsavedEntity, $apiEntity, $asvEntity)){
            return [
                'success' => false,
                'details' => [
                    'message' => 'Audit is not equal and length is not 14.',
                ],
            ];
        }
        return $this->compareEntity($unsavedEntity, $this->getEntityId($apiEntity), $this->getEntityId($asvEntity));
    }

    public function getEntity($testDataItem) {
        $entity = new $this->entityClass();
        $entity->build(
            $testDataItem[Constant::API_BUILD_ATTRIBUTES]
        );

        foreach($testDataItem[Constant::CUSTOM_ATTRIBUTES] as $attribute => $value) {
            if(is_callable($value)) {
                $entity->$attribute  = $value($entity);
                continue;
            }
            $entity->$attribute = $value;
        }

        return $entity;
    }

    public function updateEntity($entity, array $testDataItem, $fetch = false) {

        try {
            if($fetch) {
                $entity = (new $this->entityRepoClass())->findOrFail($this->getEntityId($entity));
            }
        } catch (\Throwable $e) {
            return null;
        }

        foreach($testDataItem[Constant::UPDATE_ATTRIBUTES] as $attribute => $value) {
            if(is_callable($value)) {
                $entity->$attribute  = $value($entity);
                continue;
            }
            $entity->$attribute = $value;
        }

        return $entity;
    }

    private function compareEntity($entity, string $uniqueApiId, string $uniqueAsvId)
    {
        $repo = new $this->entityRepoClass();
        try {
            $apiEntity = $repo->findOrFail($uniqueApiId);
            $asvEntity = $repo->findOrFail($uniqueAsvId);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'details' => [
                    'message' => 'Exception while fetching entity',
                    'error' => $e->getMessage(),
                ]
            ];
        }

        if($this->checkIfAuditedEntity($entity) and !$this->checkAuditUnEqualAndLengthFourteen($entity, $apiEntity, $asvEntity) ) {
            return [
                    "success" => false,
                    "details" => [
                        "message" => "Audit mismatch: Length not 14, or not newly generated. For fetched entities.",
                        "entity" => $entity->toArray(),
                        "api_entity" => $apiEntity->toArray(),
                        "asv_entity" => $asvEntity->toArray(),
                        ],
                ];
        }

        $difference = $this->comparator->getExactDifference(
            $this->unsetIgnoreKeys($entity->toArray()),
            $this->unsetIgnoreKeys($asvEntity->toArray()),
            true
        );

        if($difference != []) {
            return [
                'success' => false,
                'details' => [
                    'message' => 'Entity mismatch',
                    'default_entity' => $entity->toArray(),
                    'asv_entity' => $asvEntity->toArray(),
                    'difference' => $difference
                ]
            ];
        }

        $difference = $this->comparator->getExactDifference(
            $this->unsetIgnoreKeys($apiEntity->toArray()),
            $this->unsetIgnoreKeys($asvEntity->toArray())
        );

        if($difference != []) {
            return [
                'success' => false,
                'details' => [
                    'message' => 'Entity mismatch',
                    'api_entity' => $apiEntity->toArray(),
                    'asv_entity' => $asvEntity->toArray(),
                    'difference' => $difference
                ]
            ];
        }

        return [
            'success' => true,
            'details' => [
                'message' => 'Entity matched',
                'api_id' => $uniqueApiId,
                'asv_id' => $uniqueAsvId,
            ]
        ];
    }

    public function compareException(string $apiExceptionMessage,
                                     string $asvExceptionMessage,
                                     array $testDataItem,
                                     string $apiId,
                                     string $asvId

    ): array
    {

        $testDataItem[Constant::ASV_EXPECTED_EXCEPTION]  = $testDataItem[Constant::ASV_EXPECTED_EXCEPTION]  ?? "";
        $testDataItem[Constant::API_EXPECTED_EXCEPTION]  = $testDataItem[Constant::API_EXPECTED_EXCEPTION]  ?? "";

        if($apiExceptionMessage !== ""
            or $asvExceptionMessage !== ""
            or $testDataItem[Constant::ASV_EXPECTED_EXCEPTION] !== ""
            or $testDataItem[Constant::API_EXPECTED_EXCEPTION] !== "") {

            if($this->checkStartsWithOrBothEmpty($asvExceptionMessage, $testDataItem[Constant::ASV_EXPECTED_EXCEPTION])
                and
                $this->checkStartsWithOrBothEmpty($apiExceptionMessage, $testDataItem[Constant::API_EXPECTED_EXCEPTION])
            ) {

                return [
                    'success' => true,
                    'continue' => false,
                    'details' => [
                        "message" => "Exception match",
                        "api_id" => $apiId,
                        "asv_id" => $asvId,
                    ]
                ];
            }

            return [
                'success' => false,
                'continue' => false,
                'details' => [
                    "message" => "Exception mismatch",
                    "api_id" => $apiId,
                    "asv_id" => $asvId,
                    'api_exception' => $apiExceptionMessage,
                    'asv_exception' => $asvExceptionMessage,
                    'excepted_asv_exception' => $testDataItem[Constant::ASV_EXPECTED_EXCEPTION] ?? "",
                    'excepted_api_exception' => $testDataItem[Constant::API_EXPECTED_EXCEPTION] ?? "",
                ]
            ];
        }

        return [
            'success' => true,
            'continue' => true,
        ];
    }

    private function checkStartsWithOrBothEmpty(string $string1, string $string2): bool
    {

        if(strlen($string1) == 0 and strlen($string2) != 0) {
            return false;
        }

        if(strlen($string2) == 0 and strlen($string1) != 0) {
            return false;
        }

        if(str_starts_with($string1, $string2)) {
            return true;
        }

        return false;
    }

    private function unsetIgnoreKeys(array $entityArray): array {

        $entityArray['id'] = '';
        $entityArray['merchant_id'] = '';
        $entityArray['created_at'] = '';
        $entityArray['updated_at'] = '';
        $entityArray['deleted_at'] = '';
        $entityArray['audit_id'] = '';
        return $entityArray;
    }

    /**
     * @param array $testDataItem
     * @return bool
     */
    private function shouldPerformUpdateParity(array $testDataItem): bool
    {
        return array_key_exists(Constant::UPDATE_ATTRIBUTES, $testDataItem);
    }

    private function checkAuditUnEqualAndLengthFourteen($notSavedEntity, $entity1, $entity2) : bool
    {
       if ($notSavedEntity["audit_id"] == $entity1["audit_id"]
           or $notSavedEntity["audit_id"] == $entity2["audit_id"]
           or $entity1["audit_id"] == $entity2["audit_id"]
       ) {
           return false;
       }

         if (strlen($entity1["audit_id"]) != 14
              or strlen($entity2["audit_id"]) != 14
         ) {
              return false;
         }

         return true;
    }

    private function checkIfAuditedEntity($entity) : bool
    {
        return in_array($entity->getEntityName(),\RZP\Constants\Entity::AUDITED_ENTITIES, true) === true;
    }

    private function getEntityId($entity) : string
    {
        return  $entity->getAttributes()["id"] ?? ($entity->getAttributes()["merchant_id"] ?? "");
    }
}

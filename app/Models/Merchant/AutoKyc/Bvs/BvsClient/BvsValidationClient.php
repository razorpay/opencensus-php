<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use Request;
use Twirp\Error;
use ErrorException;
use Google\Protobuf\Struct;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\MapField;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use Rzp\Bvs\Validation\V1 as validationV1;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class BvsValidationClient extends BaseClient
{
    private $ValidationApiClient;

    /**
     * BvsValidationClient constructor.
     */
    function __construct()
    {
        parent::__construct();

        $this->ValidationApiClient = New validationV1\ValidationAPIClient($this->host, $this->httpClient);
    }

    public function getValidation(array $payload)
    {
        $this->trace->info(TraceCode::BVS_GET_VALIDATION_REQUEST, $payload);

        $validationRequest = $this->getValidationRequest($payload);

        try
        {
            $response = $this->ValidationApiClient->GetValidation($this->apiClientCtx, $validationRequest);

            $this->trace->info(
                TraceCode::BVS_GET_VALIDATION_RESPONSE,
                ['validationId' => $response->getValidationId()]);

            return $response;
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('Could not receive proper response from BVS service');
        }
    }

    /**
     * @param array $validation
     *
     * @return validationV1\ValidationResponse
     * @throws ErrorException
     * @throws IntegrationException
     */
    public function createValidation(array $validation)
    {
        $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_REQUEST, ['artefact' => $validation['artefact']]);

        $validationCreateRequest = $this->getCreateValidationRequest($validation);

        $requestSuccess = false;

        $artefactType = $validation[Constant::ARTEFACT][Constant::TYPE] ?? '';

        try
        {
            $response = $this->ValidationApiClient->CreateValidation($this->apiClientCtx, $validationCreateRequest);

            $requestSuccess = true;

            $this->trace->count(
                Metric::BVS_REQUEST_TOTAL,
                [
                    Constant::ARTEFACT_TYPE => $artefactType,
                ]);

            $this->trace->info(
                TraceCode::BVS_CREATE_VALIDATION_RESPONSE,
                ['response' => $response->serializeToJsonString()]);

            return $response;
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
        finally
        {
            $dimension = [
                Constant::ARTEFACT_TYPE => $artefactType,
                Constant::SUCCESS       => $requestSuccess,
            ];

            $this->trace->count(Metric::BVS_RESPONSE_TOTAL, $dimension);
        }
    }

    /**
     * @param array $validation
     *
     * @return validationV1\CreateValidationRequest
     * @throws ErrorException
     */
    protected function getCreateValidationRequest(array $validation): validationV1\CreateValidationRequest
    {
        $createValidation = new validationV1\CreateValidationRequest();

        $artefact = $this->NewArtefact($validation[Constant::ARTEFACT]);;

        $createValidation->setArtefact($artefact);

        $enrichments = $this->NewEnrichments($validation[Constant::ENRICHMENTS]);

        $createValidation->setEnrichments($enrichments);

        $rules = $this->NewRules($validation[Constant::RULES]);

        $createValidation->setRules($rules);

        return $createValidation;
    }

    protected function getValidationRequest(array $payload): validationV1\GetValidationRequest
    {
        $requestPayload = new validationV1\GetValidationRequest();

        $requestPayload->setValidationId($payload[Constant::VALIDATION_ID]);
        $requestPayload->setEnrichmentDetailsFields($payload[Constant::ENRICHMENT_DETAIL_FIELDS]);
        return $requestPayload;
    }

    /**
     * @param array $artefactArray
     *
     * @return validationV1\Artefact
     * @throws \Exception
     */
    private function NewArtefact(array $artefactArray): validationV1\Artefact
    {
        $details = get_Protobuf_Struct($artefactArray[Constant::DETAILS]);
        $notes   = get_Protobuf_Struct($artefactArray[Constant::NOTES]);
        $proof   = $this->getProof($artefactArray[Constant::PROOFS]);

        $artefactArray[Constant::DETAILS] = $details;
        $artefactArray[Constant::NOTES]   = $notes;
        $artefactArray[Constant::PROOFS]  = $proof;

        $artefact = new validationV1\Artefact($artefactArray);

        return $artefact;
    }

    /**
     * @param array $proofsArr
     *
     * @return MapField
     * @throws ErrorException
     */
    private function getProof(array $proofsArr): MapField
    {
        $proofs = new MapField(
            GPBType::INT32,
            GPBType::MESSAGE,
            validationV1\ProofDetails::class);

        foreach ($proofsArr as $key => $proofDetailsArr)
        {
            $proofDetails = new validationV1\ProofDetails($proofDetailsArr);

            $proofs->offsetSet($key, $proofDetails);
        }

        return $proofs;
    }

    /**
     * @param array $enrichments
     *
     * @return MapField
     * @throws ErrorException
     */
    private function NewEnrichments(array $enrichments): MapField
    {
        $enrichmentMap = new MapField(
            GPBType::STRING,
            GPBType::MESSAGE,
            validationV1\Fields::class);

        foreach ($enrichments as $key => $fields)
        {
            $detailsJsonString = json_encode($fields);

            $fieldsMessage = new validationV1\Fields();

            $fieldsMessage->mergeFromJsonString($detailsJsonString);

            $enrichmentMap->offsetSet($key, $fieldsMessage);
        }

        return $enrichmentMap;
    }

    /**
     * @param array $rules
     *
     * @return validationV1\Rules
     * @throws ErrorException
     */
    private function NewRules(array $rules): validationV1\Rules
    {
        $ruleList = $rules['rules_list'];

        $ruleListMap = $this->getRuleList($ruleList);

        $rules['rules_list'] = $ruleListMap;

        return new validationV1\Rules($rules);
    }

    /**
     * @param array $ruleList
     *
     * @return MapField
     * @throws ErrorException
     */
    private function getRuleList(array $ruleList): MapField
    {
        $rulesListMapField = new MapField(
            GPBType::INT32,
            GPBType::MESSAGE,
            validationV1\Rule::class);

        foreach ($ruleList as $key => $rule)
        {
            $ruleObj = $this->NewRule($rule);

            $rulesListMapField->offsetSet($key, $ruleObj);
        }

        return $rulesListMapField;
    }

    /**
     * @param array $rule
     *
     * @return validationV1\Rule
     * @throws \Exception
     */
    private function NewRule(array $rule): validationV1\Rule
    {
        $ruleDef = json_encode($rule['rule_def']);

        $ruleDefObj = new Struct();

        $ruleDefObj->mergeFromJsonString($ruleDef);

        $rule['rule_def'] = $ruleDefObj;

        return new validationV1\Rule($rule);
    }
}

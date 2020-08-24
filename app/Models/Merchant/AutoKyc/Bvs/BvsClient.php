<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use App;
use Request;
use Twirp\Error;
use Twirp\Context;
use ErrorException;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use Google\Protobuf\Struct;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\MapField;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Exception\IntegrationException;
use Rzp\Bvs\Validation\V1 as validationV1;

class BvsClient
{
    /** @var Logger */
    private $trace;

    private $app;

    private $bvsConfig;

    private $ValidationApiClient;

    private $apiClientCtx;

    /**
     * BvsClient constructor.
     */
    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->bvsConfig = $app['config']['services.business_verification_service'];

        $this->trace = $app['trace'];

        $host = $this->bvsConfig['host'];

        $httpClient = app('bvs_http_client');

        $this->ValidationApiClient = New validationV1\ValidationAPIClient($host, $httpClient);

        $auth = 'Basic ' . base64_encode($this->bvsConfig['user'] . ':' . $this->bvsConfig['password']);

        $headers = ['Authorization' => $auth, 'X-Request-ID' => Request::getTaskId()];

        $this->apiClientCtx = Context::withHttpRequestHeaders([], $headers);
    }

    /**
     * @param array $validation
     *
     * @return validationV1\ValidationResponse
     * @throws ErrorException
     * @throws IntegrationException
     */
    public function CreateValidation(array $validation)
    {
        $this->trace->info(TraceCode::BVS_REQUEST_CREATE_VALIDATION, ['artefact' => $validation['artefact']]);

        $createValidation = new validationV1\CreateValidationRequest();

        $artefact = $this->NewArtefact($validation[Constant::ARTEFACT]);;

        $createValidation->setArtefact($artefact);

        $enrichments = $this->NewEnrichments($validation[Constant::ENRICHMENTS]);

        $createValidation->setEnrichments($enrichments);

        $rules = $this->NewRules($validation[Constant::RULES]);

        $createValidation->setRules($rules);

        try
        {
            $response = $this->ValidationApiClient->CreateValidation($this->apiClientCtx, $createValidation);

            $this->trace->count(Metric::BVS_REQUEST_SUCCESS_TOTAL);

            return $response;
        }
        catch (Error $e)
        {
            $this->trace->traceException($e, null, TraceCode::BVS_INTEGRATION_ERROR, $e->getMetaMap());

            $this->trace->count(Metric::BVS_REQUEST_FAILED_TOTAL);

            throw new IntegrationException('
                Could not receive proper response from BVS service');
        }
    }

    /**
     * @param array $artefactArray
     *
     * @return validationV1\Artefact
     * @throws \Exception
     */
    private function NewArtefact(array $artefactArray)
    {
        $detailsJsonString = json_encode($artefactArray[Constant::DETAILS]);

        $details = new Struct();

        $details->mergeFromJsonString($detailsJsonString);

        $artefactArray[Constant::DETAILS] = $details;

        $artefact = new validationV1\Artefact($artefactArray);

        return $artefact;
    }

    /**
     * @param array $enrichments
     *
     * @return MapField
     * @throws ErrorException
     */
    private function NewEnrichments(array $enrichments)
    {
        $enrichmentMap = new MapField(GPBType::STRING, GPBType::MESSAGE, validationV1\Fields::class);

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
    private function NewRules(array $rules)
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
    private function getRuleList(array $ruleList)
    {
        $rulesListMapField = new MapField(GPBType::INT32, GPBType::MESSAGE, validationV1\Rule::class);

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
    private function NewRule(array $rule)
    {
        $ruleDef = json_encode($rule['rule_def']);

        $ruleDefObj = new Struct();

        $ruleDefObj->mergeFromJsonString($ruleDef);

        $rule['rule_def'] = $ruleDefObj;

        return new validationV1\Rule($rule);
    }
}

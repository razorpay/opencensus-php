<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\BvsClient;

use App;
use Request;

use Twirp\Error;
use Carbon\Carbon;
use ErrorException;
use Google\Protobuf\Struct;
use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\MapField;

use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Exception\IntegrationException;
use Rzp\Bvs\Validation\V2 as validationV2;
use RZP\Models\Merchant\Detail\Metric;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;
use RZP\Models\Merchant\RazorxTreatment;

class BvsValidationClientV2 extends BaseClient
{
    private $ValidationApiClient;

    /**
     * BvsValidationClient constructor.
     *
     * @param null $merchant
     * @param bool $sync
     * @param int $timeout
     */
    function __construct($merchant = null, $sync = false, int $timeout = 2)
    {
        parent::__construct($merchant, $sync);

        $this->ValidationApiClient = new validationV2\ValidationAPIClientV2($this->host, $this->httpClient);

        $this->ValidationApiClient->setTimeout($timeout);
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
                ['validationId' => $response->getValidationId(),
                 'status'=>$response->getStatus(),
                 'code'=>$response->getErrorCode(),
                 'description'=>$response->getErrorDescription()]
            );

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
     * @return validationV2\ValidationResponse
     * @throws ErrorException
     * @throws IntegrationException
     */
    public function createValidation(array $validation)
    {
        $ownerId      = $validation[Constant::ARTEFACT][Constant::OWNER_ID] ?? '';
        $artefactType = $validation[Constant::ARTEFACT][Constant::TYPE] ?? '';

        $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_REQUEST, [
            'owner'         => $ownerId,
            'artefact_type' => $artefactType
        ]);

        $validationCreateRequest = $this->getCreateValidationRequest($validation);

        $requestSuccess = false;

        if ($validationCreateRequest->getMetadata() != null)
        {
            $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_METADATA, [Constant::META_DATA => $validationCreateRequest->getMetadata()->serializeToJsonString()]);
        }
        else
        {
            $this->trace->info(TraceCode::BVS_CREATE_VALIDATION_METADATA, [Constant::META_DATA => null]);
        }

        try
        {
            if (empty($this->merchant) === false)
            {
                $eventAttributes = [
                    'time_stamp'    => Carbon::now()->getTimestamp(),
                    'artefact_type' => $artefactType,
                    'sync'          => $this->sync
                ];

                $this->app['segment-analytics']->pushTrackEvent($this->merchant, $eventAttributes, SegmentEvent::BVS_IN_SYNC_CALL_REQUEST);
            }
            $response = $this->ValidationApiClient->CreateValidation($this->apiClientCtx, $validationCreateRequest);

            if (empty($this->merchant) === false)
            {
                $eventAttributes = [
                    'time_stamp'    => Carbon::now()->getTimestamp(),
                    'artefact_type' => $artefactType,
                    'response'      => $response,
                    'sync'          => $this->sync
                ];

                $this->app['segment-analytics']->pushTrackEvent($this->merchant, $eventAttributes, SegmentEvent::BVS_IN_SYNC_CALL_RESPONSE);
            }
            $requestSuccess = true;

            $this->trace->count(
                Metric::BVS_REQUEST_TOTAL,
                [
                    Constant::ARTEFACT_TYPE => $artefactType,
                ]
            );

            $this->trace->info(
                TraceCode::BVS_CREATE_VALIDATION_RESPONSE,
                ['response' => $response->serializeToJsonString()]
            );

            return $response;
        }
        catch (Error $e)
        {
            if (empty($this->merchant) === false)
            {
                $eventAttributes = [
                    'time_stamp'    => Carbon::now()->getTimestamp(),
                    'artefact_type' => $artefactType,
                    'sync'          => $this->sync,
                    'error'         => $e->getMessage()
                ];

                $this->app['segment-analytics']->pushTrackEvent($this->merchant, $eventAttributes, SegmentEvent::BVS_IN_SYNC_CALL_RESPONSE);
            }
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
     * @return validationV2\CreateValidationRequest
     * @throws ErrorException
     */
    protected function getCreateValidationRequest(array $validation): validationV2\CreateValidationRequest
    {
        $createValidation = new validationV2\CreateValidationRequest();

        $artefact = $this->NewArtefact($validation[Constant::ARTEFACT]);

        $createValidation->setArtefact($artefact);

        $enrichments = $this->NewEnrichments($validation[Constant::ENRICHMENTS]);

        $createValidation->setEnrichments($enrichments);

        $rules = $this->NewRules($validation[Constant::RULES]);

        $createValidation->setRules($rules);

        if ($artefact->getPlatform() === Constant::PG)
        {
            $metadata = $this->newMetadata($artefact->getOwnerId(), $artefact->getType());
        }

        if (isset($metadata))
        {
            $createValidation->setMetadata($metadata);
        }

        if ($this->sync === true)
        {
            $createValidation->setRuleExecutionListRequired(true);
        }

        if ($artefact->getType() == Constant::BANK_ACCOUNT)
        {
            $createValidation->setRuleExecutionListRequired(true);
        }

        return $createValidation;
    }

    protected function getValidationRequest(array $payload): validationV2\GetValidationRequest
    {
        $requestPayload = new validationV2\GetValidationRequest();

        $requestPayload->setValidationId($payload[Constant::VALIDATION_ID]);
        $requestPayload->setEnrichmentDetailsFields($payload[Constant::ENRICHMENT_DETAIL_FIELDS]);

        return $requestPayload;
    }

    /**
     * @param array $artefactArray
     *
     * @return validationV2\Artefact
     * @throws \Exception
     */
    private function NewArtefact(array $artefactArray): validationV2\Artefact
    {
        $details = get_Protobuf_Struct($artefactArray[Constant::DETAILS]);
        $notes   = get_Protobuf_Struct($artefactArray[Constant::NOTES]);
        $proof   = $this->getProof($artefactArray[Constant::PROOFS]);

        $artefactArray[Constant::DETAILS] = $details;
        $artefactArray[Constant::NOTES]   = $notes;
        $artefactArray[Constant::PROOFS]  = $proof;

        $artefact = new validationV2\Artefact($artefactArray);

        return $artefact;
    }

    /**
     * Here we try to identify through what flow the merchant/admin is trying to send request,
     * It is decided on the basis of state of onboarding form, merchant activation status, statys logs etc.
     * For example, if the merchant is activated and is trying to make a request to BVS we are calling it
     * post onboarding flow.
     *
     * @param string $merchant_id
     * @param string $artefactType
     *
     * @return string
     */
    public function getValidationFlow(string $merchant_id, string $artefactType): string
    {
        if ($artefactType == Constant::COMMON)
        {
            return Constant::MANUAL_VERIFICATION_FLOW;
        }

        [$merchant, $merchantDetailsEntity] = (new DetailCore())->getMerchantAndDetailEntities($merchant_id);

        if ($merchantDetailsEntity->getActivationStatus() === Status::ACTIVATED or
            $merchantDetailsEntity->getActivationStatus() === Status::ACTIVATED_MCC_PENDING
        )
        {
            return Constant::POST_ONBOARDING_EDIT;
        }

        $statusChangeLogs = (new MerchantCore)->getActivationStatusChangeLog($merchantDetailsEntity->merchant);

        $needsClarificationCount = (new MerchantDetailCore())->getStatusChangeCount($statusChangeLogs, Status::NEEDS_CLARIFICATION);

        if ($needsClarificationCount >= 1)
        {
            return Constant::NEEDS_CLARIFICATION;
        }

        return Constant::ONBOARDING_FLOW;
    }

    /**
     * Here we are trying to identify who is making this request to BVS,
     * The actor can be admin, merchant or partner and is decided on the basis of auth
     * For admin logged in as merchant using merchant auth, we keep actor as empty since
     * an admin not a merchant is performing this action, and currently we cannot identify on code level
     * which admin is logged in as merchant.
     *
     * @param $headers
     *
     * @return array
     */
    public function getActorDetailsForMetaData($headers): array
    {
        $actorDetails = [];

        if (strcmp($headers->get(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT), Constant::ADMIN_IS_LOGGED_IN_AS_MERCHANT_HEADER) == 0)
        {
            return $actorDetails;
        }

        if (empty($headers->get(RequestHeader::X_DASHBOARD_ADMIN_EMAIL)))
        {
            $actorDetails[Constant::ACTOR_EMAIL] = $headers->get(RequestHeader::X_DASHBOARD_USER_EMAIL);
            $actorDetails[Constant::ACTOR_ID]    = $headers->get(RequestHeader::X_DASHBOARD_USER_ID);
            $actorDetails[Constant::ACTOR_ROLE]  = $headers->get(RequestHeader::X_DASHBOARD_USER_ROLE);
        }
        else
        {
            $actorDetails[Constant::ACTOR_EMAIL] = $headers->get(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);
            $actorDetails[Constant::ACTOR_ID]    = $headers->get(RequestHeader::X_DASHBOARD_ADMIN_USERNAME);
            $actorDetails[Constant::ACTOR_ROLE]  = Constant::ADMIN_ROLE;
        }

        return $actorDetails;
    }


    /**
     * Here we try to identify where the request is coming from, it can take values like
     * admin dashboard, merchant dashboard etc. If the admin is logged in as merchant we append
     * a string that says 'admin logged in as merchant' to the source.
     *
     * @param $headers , $requestContext
     *
     * @return string
     */
    public function getSourceForMetaData($headers, $internalAppName): string
    {
        if (isset($internalAppName) === false)
        {
            return "";
        }

        if (strcmp($headers->get(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT), Constant::ADMIN_IS_LOGGED_IN_AS_MERCHANT_HEADER) == 0)
        {
            return $internalAppName . (Constant::ADMIN_LOGGED_IN_AS_MERCHANT_MESSAGE);
        }
        else
        {
            return $internalAppName;
        }
    }

    /**
     *
     * @param string $merchant_id
     * @param string $artefactType
     *
     * @return validationV2\Metadata|null
     */
    private function newMetadata(string $merchant_id, string $artefactType): ?validationV2\Metadata
    {

        $variant = $this->app->razorx->getTreatment(
            $merchant_id,
            RazorxTreatment::BVS_CREATE_VALIDATION_METADATA,
            Constant::LIVE_MODE
        );

        if (strcmp($variant, Constant::ON) != 0)
        {
            return null;
        }

        $requestContext = $this->app['request.ctx'];
        $request        = $this->app['request'];

        if (isset($requestContext) === false or isset($request) === false)
        {
            return null;
        }

        $headers = $request->headers;

        if (isset($headers) === false)
        {
            return null;
        }

        $metadataArray = [
            Constant::USER_AGENT => $headers->get(RequestHeader::X_USER_AGENT),
            Constant::IP         => $headers->get(RequestHeader::X_DASHBOARD_IP),
            Constant::FLOW       => $this->getValidationFlow($merchant_id, $artefactType),
            Constant::SOURCE     => $this->getSourceForMetaData($headers, $requestContext->getInternalAppName())
        ];

        $actor    = new validationV2\Actor($this->getActorDetailsForMetaData($headers));
        $metadata = new validationV2\Metadata($metadataArray);
        $metadata->setActor($actor);

        return $metadata;
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
            validationV2\ProofDetails::class
        );

        foreach ($proofsArr as $key => $proofDetailsArr)
        {
            $proofDetails = new validationV2\ProofDetails($proofDetailsArr);

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
            validationV2\Fields::class
        );

        foreach ($enrichments as $key => $fields)
        {
            $detailsJsonString = json_encode($fields);

            $fieldsMessage = new validationV2\Fields();

            $fieldsMessage->mergeFromJsonString($detailsJsonString);

            $enrichmentMap->offsetSet($key, $fieldsMessage);
        }

        return $enrichmentMap;
    }

    /**
     * @param array $rules
     *
     * @return validationV2\Rules
     * @throws ErrorException
     */
    private function NewRules(array $rules): validationV2\Rules
    {
        $ruleList = $rules['rules_list'];

        $ruleListMap = $this->getRuleList($ruleList);

        $rules['rules_list'] = $ruleListMap;

        return new validationV2\Rules($rules);
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
            validationV2\Rule::class
        );

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
     * @return validationV2\Rule
     * @throws \Exception
     */
    private function NewRule(array $rule): validationV2\Rule
    {
        $ruleDef = json_encode($rule['rule_def']);

        $ruleDefObj = new Struct();

        $ruleDefObj->mergeFromJsonString($ruleDef);

        $rule['rule_def'] = $ruleDefObj;

        return new validationV2\Rule($rule);
    }
}

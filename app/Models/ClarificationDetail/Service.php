<?php

namespace RZP\Models\ClarificationDetail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Detail;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Detail\Entity as DEntity;
use \WpOrg\Requests\Exception as RequestsException;
use RZP\Models\DeviceDetail\Constants as DDConstants;
use RZP\Http\Controllers\NeedsClarificationProxyController;

class Service extends Base\Service
{
    protected $core;

    protected $ba;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Merchant\Core
     */
    private $merchantCore;

    /**
     * @var Detail\Core
     */
    private $merchantDetailCore;

    /**
     * @var Detail\Service
     */
    private $merchantDetailService;

    public function __construct(Core $core = null)
    {
        parent::__construct();

        $this->core = $core ?? new Core();

        $this->ba = $this->app['basicauth'];

        $this->validator = new Validator();

        $this->merchantCore = new Merchant\Core;

        $this->merchantDetailCore = new Detail\Core;

        $this->merchantDetailService = new Detail\Service();
    }

    public function createClarificationDetailAdmin($merchantId, array $input)
    {
        // send the request to merchant onboarding service
        // this should not affect the current flow, hence wrapped in try catch
        try
        {
            $pgosNCProxyController = new NeedsClarificationProxyController();

            $shouldMerchantOnboardViaPGOS = $pgosNCProxyController->shouldMerchantOnboardViaPGOS($merchantId);

            if ($shouldMerchantOnboardViaPGOS === true) {

                $pgosInput['clarification_reasons'] = $input['clarification_reasons'];
                $pgosInput['merchant_id'] = $merchantId;
                $pgosInput['admin_email'] = $this->app['basicauth']->getAdmin()->getEmail();

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_activation_clarifications_save_admin', $pgosInput, $merchant);

                $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                    'route'    => 'merchant_activation_clarifications_save_admin',
                    'response' => $response,
                ]);

                return $response;
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'error_message' => $exception->getMessage()
            ]);

            throw new Exception\ServerErrorException(ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, [
                'error description' => 'submitted data could not be processed'
            ]);
        }

        $this->validator->validateAdminClarificationReasons($merchantId, $input);

        return $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $input) {

            if ($this->isEligibleForRevampNC($merchantId) === true)
            {
                $this->core->createClarificationDetailAdmin($merchantId, $input[Constants::CLARIFICATION_REASONS]);
            }

            $response = $this->core->getClarificationDetail($merchantId);

            if (empty($input['old_clarification_reasons']) === false)
            {
                $this->merchantDetailService->editMerchantDetails($merchantId, $input['old_clarification_reasons']);
            }

            $merchantDetails = $this->repo->merchant_detail->findByPublicId($merchantId);

            $response['kyc_clarification_reasons'] = $merchantDetails->getKycClarificationReasons();;

            return $response;
        });
    }

    public function isEligibleForRevampNC(string $merchantId): bool
    {

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        if ($merchant->isSignupCampaign(DDConstants::EASY_ONBOARDING) === false)
        {
            return false;
        }

        $isExptEnabled = $this->merchantCore->isRazorxExperimentEnable($merchantId, Merchant\RazorxTreatment::NC_REVAMP);

        $this->trace->info(TraceCode::NC_REVAMP_ELIGIBILITY, [
            'isExptEnabled' => $isExptEnabled
        ]);

        if ($isExptEnabled === false)
        {
            return false;
        }

        $doesV3Exist = $this->core->hasClarificationDetails($merchantId);

        if ($doesV3Exist === true)
        {
            return true;
        }

        $ncCount = $this->core->getNcCount($merchant);

        if ($ncCount === 0)
        {
            return true;
        }

        return false;
    }

    public function getClarificationDetail($merchantId = null): array
    {
        $isAdminRouteRequest = $merchantId ? true : false;

        $merchantId = $merchantId ?? $this->ba->getMerchantId();

        // send the request to merchant onboarding service
        // this should not affect the current flow, hence wrapped in try catch
        try
        {
            $payload = [
                "merchant_id" => $merchantId
            ];

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $pgosNCProxyController = new NeedsClarificationProxyController();

            $shouldMerchantOnboardViaPGOS = $pgosNCProxyController->shouldMerchantOnboardViaPGOS($merchantId);

            if ($shouldMerchantOnboardViaPGOS === true) {

                if ($isAdminRouteRequest === true)
                {
                    $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_activation_clarifications_fetch_admin', $payload, $merchant);

                    $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                        'route'    => 'merchant_activation_clarifications_fetch_admin',
                        'response' => $response
                    ]);

                    return $response;
                }
                else
                {
                    $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_activation_clarifications_fetch', $payload, $merchant);

                    $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                        'route'    => 'merchant_activation_clarifications_fetch',
                        'response' => $response
                    ]);

                    return $response;
                }
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'error_message' => $exception->getMessage()
            ]);

            throw new Exception\ServerErrorException(ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, [
                'error description' => 'submitted data could not be processed'
            ]);
        }

        return $this->core->getClarificationDetail($merchantId);
    }

    public function saveMerchantResponseToClarifications(array $input, string $merchantId)
    {

        // send the request to merchant onboarding service
        // this should not affect the current flow, hence wrapped in try catch
        try {
            $pgosNCProxyController = new NeedsClarificationProxyController();

            $shouldMerchantOnboardViaPGOS = $pgosNCProxyController->shouldMerchantOnboardViaPGOS($merchantId);

            if ($shouldMerchantOnboardViaPGOS === true) {

                $pgosInput['data'] = $input;

                if ($input['submit'] == 1 or $input['submit'] == "1") {
                    unset($pgosInput['data']);
                    $pgosInput['submit'] = 1;
                }

                $pgosInput['merchant_id'] = $merchantId;

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                foreach ($input as $groupName => $details)
                {
                    $this->validateFieldsData($merchantId, $groupName, $details);
                }

                $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_activation_clarifications_save', $pgosInput, $merchant);

                $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                    'route'     => 'merchant_activation_clarifications_save',
                    'response'  => $response,
                    'payload'   => $pgosInput,
                ]);

                return $response;
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'error_message' => $exception->getMessage()
            ]);

            if ($exception instanceof Exception\BadRequestValidationFailureException)
            {
                throw new Exception\BadRequestValidationFailureException($exception->getMessage());
            }

            throw new Exception\ServerErrorException(ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, [
                'error description' => 'submitted data could not be processed'
            ]);
        }

        $this->validator->validateClarificationDetails($merchantId, $input);

        //NC Partial Submission
        foreach ($input as $groupName => $details)
        {
            $this->saveCommentsIfApplicable($merchantId, $groupName, $details);

            $this->saveFieldsDataIfApplicable($merchantId, $groupName, $details);

            //mark all the entries in the group as submitted
            $this->submitGroupIfApplicable($merchantId, $groupName, $details);
        }

        //NC form submission
        $this->submitFormIfApplicable($merchantId, $input);

        return $this->core->getClarificationDetail($merchantId);
    }

    private function saveCommentsIfApplicable($merchantId, $groupName, $details)
    {
        if (isset($details[Entity::COMMENT_DATA]) === true)
        {
            return $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $groupName, $details) {

                //updating field values in the group
                $groupClarificationDetails = $this->saveClarifications($merchantId, $groupName, $details);

                foreach ($groupClarificationDetails->getFields() as $fieldName)
                {
                    $clarificationReasons[$fieldName] = [
                        [
                            "reason_type" => "custom",
                            "reason_code" => $details[Entity::COMMENT_DATA][Constants::TEXT]
                        ]
                    ];
                }

                if (empty($clarificationReasons) === false)
                {
                    $activationInput = [
                        "kyc_clarification_reasons" => [
                            "clarification_reasons" => $clarificationReasons
                        ]
                    ];

                    $merchant = $this->repo->merchant->findByPublicId($merchantId);
                    $this->app['basicauth']->setMerchant($merchant);

                    (new Detail\Service())->saveMerchantDetailsForActivation($activationInput);
                }
            });
        }
    }

    //NC Partial Submission
    private function saveFieldsDataIfApplicable($merchantId, $groupName, $details)
    {
        if (isset($details[Constants::FIELD_DETAILS]) === true)
        {
            return $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $groupName, $details) {

                $this->saveClarifications($merchantId, $groupName, $details);

                //validate document is existing or not and remove document fields details
                $fieldsInput = $this->validator->validateAndRemoveDocumentFields($details[Constants::FIELD_DETAILS]);

                $this->validator->validateFieldDetails($merchantId, $fieldsInput);

            });
        }
    }

    private function validateFieldsData($merchantId, $groupName, $details)
    {
        if (isset($details[Constants::FIELD_DETAILS]) === true)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($merchantId, $groupName, $details) {

                //validate document is existing or not and remove document fields details
                $fieldsInput = $this->validator->validateAndRemoveDocumentFields($details[Constants::FIELD_DETAILS]);

                $this->validator->validateFieldDetails($merchantId, $fieldsInput);

            });
        }
    }

    private function saveClarifications($merchantId, $groupName, $details): Entity
    {
        $groupClarificationDetails =
            $this->repo->clarification_detail->getLatestByMerchantIdAndGroup($merchantId, $groupName);

        $defaultFieldDetails = null;

        if (empty($groupClarificationDetails) === false)
        {
            $defaultFieldDetails = $groupClarificationDetails->generateDefaultFieldDetails();
        }

        $input = [
            Entity::GROUP_NAME    => $groupName,
            Entity::MERCHANT_ID   => $merchantId,
            Entity::FIELD_DETAILS => $details[Constants::FIELD_DETAILS] ?? $defaultFieldDetails,
            Entity::STATUS        => $groupClarificationDetails->getStatus(),
            Entity::COMMENT_DATA  => $details[Entity::COMMENT_DATA] ?? null,
            Entity::MESSAGE_FROM  => Constants::MERCHANT,
        ];

        $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
            "step"               => "save",
            "merchantId"         => $merchantId,
            "groupName"          => $groupName,
            "groupClarification" => $input
        ]);

        return $this->core->createMerchantClarificationDetails($merchantId, $input);
    }

    //submit/un-submit group
    private function submitGroupIfApplicable($merchantId, $groupName, $details)
    {
        if (isset($details[Constants::SUBMIT]) === true and
            $details[Constants::SUBMIT] == 1)
        {
            $groupClarifications = $this->repo->clarification_detail->getAllByMerchantIdStatusAndGroup($merchantId, $groupName, Constants::NEEDS_CLARIFICATION);

            foreach ($groupClarifications as $groupClarification)
            {

                $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
                    "step"               => "submit",
                    "merchant_id"        => $merchantId,
                    "groupName"          => $groupName,
                    "groupClarification" => $groupClarification->getId()
                ]);

                $groupClarification->edit([Entity::STATUS => Constants::SUBMITTED], 'edit');

                $this->repo->clarification_detail->saveOrFail($groupClarification);
            }
        }

        if (isset($details[Constants::SUBMIT]) === true and
            $details[Constants::SUBMIT] == 0)
        {
            $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
                "step"       => "un-submit",
                "merchantId" => $merchantId,
                "groupName"  => $groupName
            ]);

            $merchant = $this->repo->merchant->findOrFail($merchantId);

            $ncCount = $this->core->getNcCount($merchant);

            $groupClarifications = $this->repo->clarification_detail->getAllByMerchantIdStatusAndGroup($merchantId, $groupName, Constants::SUBMITTED);

            foreach ($groupClarifications as $groupClarification)
            {
                $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
                    "step"                      => "needs clarification",
                    "merchantId"                => $merchantId,
                    "groupName"                 => $groupName,
                    "groupClarificationId"      => $groupClarification->getId(),
                    "groupClarificationNcCount" => $groupClarification->getNcCount(),
                    "ncCount"                   => $ncCount
                ]);

                if ($groupClarification->getNcCount() === $ncCount)
                {
                    $groupClarification->edit([Entity::STATUS => Constants::NEEDS_CLARIFICATION], 'edit');

                    $this->repo->clarification_detail->saveOrFail($groupClarification);
                }
            }
        }
    }

    private function submitFormIfApplicable($merchantId, $input)
    {
        if (isset($input[Constants::SUBMIT]) === true and
            $input[Constants::SUBMIT] == 1)
        {
            //validate if all clarifications are in submitted state
            $this->validator->validateNCSubmission($merchantId);

            return $this->repo->transactionOnLiveAndTest(function() use ($merchantId) {

                $merchantActivationInput = [Constants::SUBMIT => '1'];

                $groupsAddedToMerchantActivationInput = [];

                //mark all groups to under_review
                $clarifications = $this->repo->clarification_detail->getByMerchantIdAndStatus($merchantId, Constants::SUBMITTED);

                foreach ($clarifications as $clarification)
                {
                    $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
                        "step"               => "under review",
                        "merchantId"         => $merchantId,
                        "groupClarification" => $clarification
                    ]);

                    if ($clarification->isMessageFromMerchant() === true and
                        in_array($clarification->getGroupName(),$groupsAddedToMerchantActivationInput) === false)
                    {
                        $fields = $clarification->getFieldDetailsIfApplicable();

                        //validate document is existing or not and remove document fields details
                        $fields = $this->validator->validateAndRemoveDocumentFields($fields);

                        $this->trace->info(TraceCode::NC_REVAMP_SAVE_DETAILS, [
                            "step"       => "submit merchant details",
                            "merchantId" => $merchantId,
                            "input"      => $merchantActivationInput,
                            'fields'     => $fields
                        ]);

                        $merchantActivationInput = array_merge($merchantActivationInput, $fields);

                        array_push($groupsAddedToMerchantActivationInput,$clarification->getGroupName());
                    }

                    $clarification->edit([Entity::STATUS => Constants::UNDER_REVIEW], 'edit');

                    $this->repo->clarification_detail->saveOrFail($clarification);

                }

                $this->trace->info(TraceCode::NC_REVAMP_SAVE_DETAILS, [
                    "step"       => "submit merchant details",
                    "merchantId" => $merchantId,
                    "input"      => $merchantActivationInput
                ]);

                (new Detail\Service())->saveMerchantDetailsForActivation($merchantActivationInput);
            });
        }
    }

    public function getMerchantNcRevampEligibility($merchantId = null)
    {
        $merchantId = $merchantId ?? $this->ba->getMerchantId();

        $this->trace->info(TraceCode::NC_REVAMP_ELIGIBILITY, [
            'merchantId' => $merchantId
        ]);

        // send the request to merchant onboarding service
        // this should not affect the current flow, hence wrapped in try catch
        try
        {
            $pgosNCProxyController = new NeedsClarificationProxyController();

            $shouldMerchantOnboardViaPGOS = $pgosNCProxyController->shouldMerchantOnboardViaPGOS($merchantId);

            if ($shouldMerchantOnboardViaPGOS === true) {

                $pgosInput['merchant_id'] = $merchantId;

                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_nc_revamp_eligibility', $pgosInput, $merchant);

                $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                    'route' => 'merchant_nc_revamp_eligibility',
                    'response' => $response,
                ]);

                return $response;
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                'error_message' => $exception->getMessage()
            ]);

            throw new Exception\ServerErrorException(ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, [
                'error description' => 'submitted data could not be processed'
            ]);
        }

        return ["nc_revamp_enabled" => $this->isEligibleForRevampNC($merchantId)];
    }

    public function updateClarificationDetails($merchantId,$status)
    {
        if ($status === Status::UNDER_REVIEW)
        {
            // send the request to merchant onboarding service
            // this should not affect the current flow, hence wrapped in try catch
            try {
                $pgosNCProxyController = new NeedsClarificationProxyController();

                $shouldMerchantOnboardViaPGOS = $pgosNCProxyController->shouldMerchantOnboardViaPGOS($merchantId);

                if ($shouldMerchantOnboardViaPGOS === true) {

                    $pgosInput['merchant_id'] = $merchantId;

                    $merchant = $this->repo->merchant->findOrFail($merchantId);

                    $response = $pgosNCProxyController->handlePGOSProxyRequests('merchant_update_clarifications', $pgosInput, $merchant);

                    $this->trace->info(TraceCode::PGOS_PROXY_RESPONSE, [
                        'route'     => 'merchant_update_clarifications',
                        'response'  => $response,
                        'payload'   => $pgosInput,
                    ]);

                    return $response;
                }
            }
            catch (\Throwable $exception)
            {
                $this->trace->error(TraceCode::PGOS_PROXY_ERROR, [
                    'error_message' => $exception->getMessage()
                ]);

                if ($exception instanceof Exception\BadRequestValidationFailureException)
                {
                    throw new Exception\BadRequestValidationFailureException($exception->getMessage());
                }

                throw new Exception\ServerErrorException(ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, ErrorCode::SERVER_ERROR_PGOS_PROCESSNG_FAILED, [
                    'error description' => 'submitted data could not be processed'
                ]);
            }

            $clarifications = $this->repo->clarification_detail->getByMerchantIdAndStatuses($merchantId, [Constants::SUBMITTED, Constants::NEEDS_CLARIFICATION]);

            foreach ($clarifications as $clarification)
            {
                $this->trace->info(TraceCode::NC_REVAMP_MERCHANT_RESPONSE, [
                    "step"               => "under review",
                    "merchantId"         => $merchantId,
                    "groupClarification" => $clarification
                ]);

                $clarification->edit([Entity::STATUS => Constants::UNDER_REVIEW], 'edit');

                $this->repo->clarification_detail->saveOrFail($clarification);

            }
        }
    }
}

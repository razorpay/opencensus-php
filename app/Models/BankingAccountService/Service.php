<?php

namespace RZP\Models\BankingAccountService;

use Illuminate\Http\Request;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Exception\ServerErrorException;
use RZP\Models\BankingAccount\Gateway\Processor;
use RZP\Models\Base;
use RZP\Constants\Product;
use RZP\Models\Card\BuNamespace;
use RZP\Services\CardVault as CardVaultService;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
use RZP\Models\Merchant\Attribute\Entity as MerchantAttributeEntity;
use RZP\Models\Merchant\Attribute\Group as Group;
use RZP\Models\Merchant\Attribute\Repository as MerchantAttributeRepository;
use RZP\Models\Merchant\Attribute\Type as MerchantAttributeType;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Trace\TraceCode;
use RZP\Services\BankingAccountService;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Entity as ValidationEntity;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\BusinessPanForExternalRequest;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PersonalPanForExternalRequest;

class Service extends Base\Service
{
    /* @var Request $request */
    protected $request;

    protected $validator;

    /* @var BankingAccountService $bankingAccountService*/
    protected $bankingAccountService;

    public function __construct()
    {
        parent::__construct();

        $this->request = $this->app['request'];

        $this->bankingAccountService = $this->app['banking_account_service'];

        $this->validator = new Validator();
    }

    public function createCurrentAccountBankingDependencies($merchantId, $input)
    {
        $this->validator->validateInput('create', $input);

        return $this->core()->createCaBankingDependencies($merchantId, $input);
    }

    public function assignBusinessId($merchantId, $input)
    {
        $this->validator->validateInput('business', $input);

        return $this->core()->assignBusinessId($merchantId, $input);
    }

    public function preProcessAndForwardRequest($path, $input)
    {
        //path is not empty for all except during create business call.
        $isBusinessCreation = empty($path) === true;

        if($isBusinessCreation === false)
        {
            //pull businessId and validate before forwarding request to banking account service
            $this->core()->isvalidBusinessId($path);

            $path = Constants::BUSINESS_PATH . '/' . $path;
        }
        else
        {
            //check if business already created for the merchant
            $this->core()->isBusinessExists();

            if(array_key_exists(Constants::MERCHANT_ID, $input) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_REQUIRED);
            }

            // X doesn't support "individual" constitution (business type) at the moment
            if (array_key_exists(Constants::CONSTITUTION, $input) === true
                    && $input[Constants::CONSTITUTION] === Merchant\Detail\BusinessType::INDIVIDUAL) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_CONSTITUTION_NOT_SUPPORTED);
            }

            $merchant = $this->app['basicauth']->getMerchant();

            $input[Constants::MERCHANT_ID]  = $merchant->getId();

            $path = Constants::BUSINESS_PATH;
        }

        $method = $this->request->getMethod();

        //check for business deletion request not allowed.
        $this->core()->isDeleteBusinessRequest($path, $method);

        $queryParams = $this->request->query();

        $queryString = $this->request->getQueryString();

        $input = $this->core()->removeRequestParamsFromInput($queryParams, $input);

        $uri = $this->core()->attachRequestParamsToPath($queryString, $path);

        $personId = $this->fetchPersonIdForApplicationPatchRequest($input);

        if(empty($personId) === false)
        {
            $this->createOrUpdateSignatory($input, $personId, $path);

            $input = $this->getPersonDocumentDetails($input, $personId);

            unset($input[Constants::SIGNATORIES]);
        }

        if($method === Request::METHOD_DELETE)
        {
            $idArray = $this->getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS($path, $method);

            if(empty($idArray[Constants::SIGNATORY_ID]) === false)
            {
                return $this->deleteSignatory($idArray, $method, $input);
            }
        }

        $response =  $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, $input);

        if(empty($personId) === false)
        {
            //personId is attached back to the response to avoid duplicate creation of person again.
            $response[Constants::PERSON_ID] = $personId;
        }

        if($method === Request::METHOD_POST and
           $path === Constants::BUSINESS_PATH and
           isset($response['data']) === true)
        {
            //attaching businessId to the merchant_details entity
            $this->assignBusinessId($input[Constants::MERCHANT_ID], [Constants::BUSINESS_ID => $response['data']['id']]);
        }

        return $response;
    }

    public function forwardCronRequest($path, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CRON_REQUEST,
            [
                'input'  => $input,
                'method' => $this->request->getMethod(),
                'path'    => $path
            ]);

        return $this->forwardRequest($path, $input);
    }

    public function forwardLMSRequest($path, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_LMS_REQUEST,
            [
                'input'  => $input,
                'method' => $this->request->getMethod(),
                'path'    => $path
            ]);

        $response = $this->forwardRequest($path, $input);

        if($this->request->getMethod() === Request::METHOD_POST and
            $path === Constants::ADMIN_BANKING_ACCOUNT_APPLY_PATH)
        {
            if(isset($response['data']['business_id']) === true)
            {
                $this->trace->info(TraceCode::ASSIGN_BUSINESS_ID_FOR_ADMIN_APPLY_FOR_BANKING_ACCOUNT_IN_LMS,
                    [
                        'merchant_id' => $input['merchant_id'],
                        'business_id' => $response['data']['business_id'],
                    ]);

                //attaching businessId to the merchant_details entity
                $this->assignBusinessId($input[Constants::MERCHANT_ID], [Constants::BUSINESS_ID => $response['data']['business_id']]);
            }
            else
            {
                $this->trace->error(
                    TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_BUSINESS_ID_NOT_RETURNED_IN_RESPONSE,
                    [
                        'data' => $response['data'],
                    ]);

                throw new Exception\ServerErrorException(
                    'Internal Server Error occurred',
                    ErrorCode::SERVER_ERROR);
            }
        }

        return $response;
    }

    protected function forwardRequest($path, $input)
    {
        if(empty($path) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_PATH_MISSING);
        }

        $method = $this->request->getMethod();

        $queryParams = $this->request->query();

        $queryString = $this->request->getQueryString();

        $input = $this->core()->removeRequestParamsFromInput($queryParams, $input);

        $uri = $this->core()->attachRequestParamsToPath($queryString, $path);

        return $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, $input);
    }

    protected function getBusinessType($merchant)
    {
        // load merchantDetail
        $merchant->load('merchantDetail');

        $businessType = $merchant->merchantDetail->getBusinessType();

        return strtoupper($businessType);
    }

    public function fetchPersonIdForApplicationPatchRequest($input)
    {
        $personId = null;

        $requestPath = $this->request->getRequestUri();

        $method = $this->request->getMethod();

        $applicationPath = '/' . Constants::APPLICATIONS_PATH . '/';

        if ($method === Request::METHOD_PATCH and
            isset($input[Constants::SIGNATORIES]) === true and
            strpos($requestPath, $applicationPath) !== false)
        {
            //if the person payload is not present then return the person_id.
            if(isset($input[Constants::SIGNATORIES][Constants::PERSON]) === false)
            {
                return $input[Constants::SIGNATORIES][Constants::PERSON_ID];
            }

            $personId = $this->createOrUpdatePerson($input[Constants::SIGNATORIES]);
        }

        return $personId;
    }

    public function createOrUpdatePerson($signatories)
    {
        $method = Request::METHOD_POST;

        $personInput = $signatories[Constants::PERSON];

        $businessId = $this->core()->fetchBusinessId();

        $path = Constants::BUSINESS_PATH . '/' . $businessId . '/' . Constants::PERSON_PATH . '/';

        //Patch person if person_id exists else post person call
        if(isset($signatories[Constants::PERSON_ID]) === true)
        {
            $method = Request::METHOD_PATCH;

            $path = $path . $signatories[Constants::PERSON_ID];
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, $method, $personInput);

        if (isset($response['data']) === true)
        {
            //person id
            return $response['data']['id'];
        }

        $this->trace->error(
            TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_PERSON_API_FAILURE,
            $response['error']
        );

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_PERSON_API_FAILURE);
    }

    /**
     * Fetches accounts from banking service based on merchantId
     *
     * @param $merchantId
     * @param $bankingAccounts
     *
     * @return Base\PublicCollection
     */
    public function fetchAccountDetailsFromBas($merchantId, $bankingAccounts)
    {
        //To avoid login issue for the merchant if external call to banking_account_service fails.
        try
        {
            $bas = $this->app['banking_account_service']->fetchAccountDetails($merchantId);

            $bankingAccounts = $this->core()->attachBasBankingAccount($merchantId, $bas, $bankingAccounts);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_FETCH_ACCOUNT_DETAILS
            );
        }

        return $bankingAccounts;
    }

    public function slotBookForBankingAccount($bookingDetails)
    {
        $path = 'booking/slot/book';

        $channel = $bookingDetails['channel'];

        $id = $bookingDetails['id'];

        $slotBookingDateTime = $bookingDetails['slotDateAndTime'];

        $epochSlotBookingDateTime = strtotime($slotBookingDateTime);

        if($channel === 'rbl')
        {
            $bankingAccount = $this->repo->banking_account->findByPublicId($id);

            // check in db if slot is already booked for the same id and dateAnTime
            $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

            $this->trace->debug(TraceCode::SLOT_BOOKING_AND_SAVED_TIME,
                                   [
                                       'booking time' => $epochSlotBookingDateTime,
                                       'Saved time' => $activationDetail['booking_date_and_time']
                                   ]);

            if(empty($activationDetail['booking_date_and_time']) === false && $activationDetail['booking_date_and_time'] === $epochSlotBookingDateTime)
            {
                $this->trace->error(
                    TraceCode::SLOT_IS_ALREADY_BOOKED_FOR_SAME_TIME_SO_SLOT_CANNOT_BE_RESCHEDULED,
                    [
                        'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                    ]);

                return [
                    'bookingDetails' => null,
                    'status' => 'Failure',
                    'ErrorDetail' => [
                        "errorReason" => 'Slot is already booked for the same date and time, it cannot be booked again'
                    ]

                ];
            }
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'POST', $bookingDetails);

        $responseStatus = 'Failure';

        if (key_exists('data',$response) and key_exists(Constants::STATUS,$response['data']))
        {
            $responseStatus = $response['data'][Constants::STATUS];
        }

        if ($channel === 'rbl')
        {
            $clarityContextCollection = (new Merchant\Attribute\Service())->getPreferencesByGroupAndType(
                Group::X_MERCHANT_CURRENT_ACCOUNTS, MerchantAttributeType::CLARITY_CONTEXT)->first();

            $clarityContextEnabled = !empty($clarityContextCollection) and $clarityContextCollection->getValue() === 'enabled';

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_CLARITY_CONTEXT_ENABLED,
                [
                    'bank_account_id'               => $bankingAccount->getId(),
                    'clarity_context_enabled'       => $clarityContextEnabled,
                    'slot_booking_response'         => $responseStatus
                ]);

            if ($responseStatus !== 'Failure' and $clarityContextEnabled === true)
            {
                (new \RZP\Models\BankingAccount\Core())->notifyOpsAboutProActivation($bankingAccount->toArray());
            }
        }

        return $response['data'];
    }

    /**
     * Fetches banking account along with banking_balance and adds to the input array
     * Called from get user call
     *
     * @param $merchantId
     * @param $bankingAccounts
     *
     * @return array
     */
    public function fetchBankingAccountWithBalanceFromBas($merchantId, $bankingAccounts)
    {
        //To avoid login issue for the merchant if external call to banking_account_service fails.
        try
        {
            $bas = $this->app['banking_account_service']->fetchAccountDetails($merchantId);

            if (empty($bas) === false)
            {
                $result = $this->core()->attachBankingAccountWithBalance($merchantId, $bas);

                $bankingAccounts[] = $result;
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_FETCH_ACCOUNT_DETAILS
            );
        }

        return $bankingAccounts;
    }

    /**
     * @param $input
     * @param $personId
     * @return mixed
     */
    public function getPersonDocumentDetails($input, $personId)
    {
        if (empty($input[Constants::SIGNATORIES][Constants::DOCUMENT]) === false)
        {
            $personDocumentMapping = [
                Constants::PERSONS_DOCUMENT_MAPPING => [
                    $personId => [
                        Constants::ID_PROOF => $input[Constants::SIGNATORIES][Constants::DOCUMENT][Constants::ID_PROOF],
                        Constants::ADDRESS_PROOF => $input[Constants::SIGNATORIES][Constants::DOCUMENT][Constants::ADDRESS_PROOF],
                    ]
                ]
            ];

            if (empty($input[Constants::APPLICATION_SPECIFIC_FIELDS][Constants::PERSONS_DOCUMENT_MAPPING]) == false)
            {
                $personsExistingDocumentMapping = $input[Constants::APPLICATION_SPECIFIC_FIELDS][Constants::PERSONS_DOCUMENT_MAPPING];

                $personsUpdatedDocumentMapping = array_replace($personsExistingDocumentMapping, $personDocumentMapping[Constants::PERSONS_DOCUMENT_MAPPING]);

                $personDocumentMapping = [
                    Constants::PERSONS_DOCUMENT_MAPPING => $personsUpdatedDocumentMapping
                ];
            }

            $applicationSpecificFields = $input[Constants::APPLICATION_SPECIFIC_FIELDS];

            $applicationSpecificFields = array_replace($applicationSpecificFields, $personDocumentMapping);

            $input[Constants::APPLICATION_SPECIFIC_FIELDS] = $applicationSpecificFields;
        }

        return $input;
    }

    /**
     * @throws Exception\ServerErrorException
     */
    public function requestBvsValidation(array $input): array
    {
        (new Validator)->validateInput(Validator::BVS_INITIATE_VALIDATION, $input);

        $artefactType = $input[Constant::ARTEFACT_TYPE];

        $processor = $this->getProcessorByArtefactType($artefactType, $input);

        return $processor->triggerBVSRequest();
    }

    private function getProcessorByArtefactType(string $artefactType, array $input)
    {
        switch ($artefactType)
        {
            case Constant::BUSINESS_PAN:
                return new BusinessPanForExternalRequest($input[Constant::OWNER_ID], $input[Constant::DETAILS]);
            case Constant::PERSONAL_PAN:
                return new PersonalPanForExternalRequest($input[Constant::OWNER_ID], $input[Constant::DETAILS]);
        }
    }

    public function checkPinCodeServiceability($input)
    {
        $basPinCodeServiceabilityPath = Constants::BAS_PIN_CODE_SERVICEABILITY;

        return $this->bankingAccountService->sendRequestAndProcessResponse($basPinCodeServiceabilityPath, 'GET', $input);
    }

    public function checkPinCodeServiceabilityBulk($input)
    {
        $path = Constants::BAS_SERVICEABILITY_BULK;

        $queryParams = $this->request->query();

        $queryString = $this->request->getQueryString();

        $input = $this->core()->removeRequestParamsFromInput($queryParams, $input);

        $uri = $this->core()->attachRequestParamsToPath($queryString, $path);

        return $this->bankingAccountService->sendRequestAndProcessResponse($uri, 'GET', $input);
    }

    public function checkServiceability(string $pincode): array
    {
        return $this->bankingAccountService->sendRequestAndProcessResponse(Constants::BAS_CHECK_SERVICEABILITY . '?pincode='. $pincode, 'GET', []);
    }

    public function checkCommonServiceability($input)
    {
        return $this->bankingAccountService->sendRequestAndProcessResponse(Constants::ALLOCATE_LEAD, 'POST', $input);

    }
    /**
     * @param $input
     * @param $personId
     * @param string $path
     * @return array
     * This method create/update the signatory for the specific application
     */
    public function createOrUpdateSignatory($input, $personId, string $path)
    {
        if (empty($input[Constants::SIGNATORIES][Constants::SIGNATORY_ID]) === false)
        {
            $method = Request::METHOD_PATCH;

            $idArray = $this->getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS($path, $method);

            $signatoryId = $input[Constants::SIGNATORIES][Constants::SIGNATORY_ID];

            //PATCH :business/{id}/application/{id}/signatory/{id}
            $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::APPLICATION_PATH . '/' . $idArray[Constants::APPLICATION_ID] . '/' . Constants::SIGNATORY_PATH . '/' . $signatoryId;
        }
        else
        {
            $method = Request::METHOD_POST;

            $idArray = $this->getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS($path, $method);

            //POST : business/{id}/application/{id}/signatory
            $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::APPLICATION_PATH . '/' . $idArray[Constants::APPLICATION_ID] . '/' . Constants::SIGNATORY_PATH;
        }

        $signatoryPayload = [
            Constants::PERSON_ID      => $personId,
            Constants::SIGNATORY_TYPE => $input[Constants::SIGNATORIES][Constants::SIGNATORY_TYPE],
        ];

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, $method, $signatoryPayload);

        if (isset($response['data']) === true)
        {
            //person id
            return $response['data']['id'];
        }

        $this->trace->error(
            TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_SIGNATORY_API_FAILURE,
            $response['error']
        );

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_SIGNATORY_API_FAILURE);

    }

    /**
     * @param string $path
     * @param $method
     * @return array
     * This method parses the url and returns the Ids related to Business and Application
     */
    public function getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS(string $path, $method)
    {
        $result = preg_split("/[\/]/", $path);

        if($method === Request::METHOD_POST || $method === Request::METHOD_PATCH)
        {
            return [
                Constants::BUSINESS_ID => $result[1],
                Constants::APPLICATION_ID => $result[3]
            ];
        }
        else if($method === Request::METHOD_DELETE)
        {
            return [
                Constants::BUSINESS_ID => $result[1],
                Constants::APPLICATION_ID => $result[3],
                Constants::PERSON_ID=> $result[5],
                Constants::SIGNATORY_ID => $result[7]
            ];
        }
    }

    /**
     * @param array $idArray
     * @param string $path
     * @param string $method
     * @param $input
     * @return array
     * @throws Exception\BadRequestException
     * Delete Signatory -
     * call the delete Bas signatory Api
     * call the delete person person Api
     * get the application patch(get the application specific fields for that application)
     * call the application patch and remove the person doc mapping for that person_id
     */
    public function deleteSignatory(array $idArray, string $method, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_DELETE_SIGNATORY_REQUEST,
            [
                Constants::BUSINESS_ID    => $idArray[Constants::BUSINESS_ID],
                Constants::APPLICATION_ID => $idArray[Constants::APPLICATION_ID],
                Constants::SIGNATORY_ID => $idArray[Constants::SIGNATORY_ID ],
                'method' => $method,
            ]);

        //Delete the signatory entity
        $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::APPLICATION_PATH . '/' . $idArray[Constants::APPLICATION_ID] . '/' . Constants::SIGNATORY_PATH . '/' . $idArray[Constants::SIGNATORY_ID];

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, $method, $input);

        if (empty($response['deleted']) === true || $response['deleted'] === false)
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_SIGNATORY_API_FAILURE,
                $response['error']);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_SIGNATORY_API_FAILURE);
        }

        //Delete the person entity
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_DELETE_PERSON_REQUEST,
            [
                Constants::BUSINESS_ID => $idArray[Constants::BUSINESS_ID],
                Constants::PERSON_ID  => $idArray[Constants::PERSON_ID],
                'method' => $method,
            ]);

        $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] .  '/' . Constants::PERSON_PATH . '/' . $idArray[Constants::PERSON_ID];

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, $method, $input);

        if (empty($response['deleted']) === true || $response['deleted'] === false)
        {
            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_PERSON_API_FAILURE,
                $response['error']
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_PERSON_API_FAILURE);
        }

        //Update the application entity(application_specific_fields)
        return $this->getAndUpdateApplicationSpecificFields($idArray, $input);
    }

    /**
     * @param $idArray
     * @param $input
     * @return bool[]
     * @throws Exception\BadRequestException
     */
    public function getAndUpdateApplicationSpecificFields($idArray, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_GET_APPLICATION_REQUEST,
            [
                Constants::BUSINESS_ID => $idArray[Constants::BUSINESS_ID],
                Constants::PERSON_ID  => $idArray[Constants::PERSON_ID],
                'input' => $input
            ]);

        $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::APPLICATIONS_PATH . '/' . $idArray[Constants::APPLICATION_ID];

        // Get the application
        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, Request::METHOD_GET, $input);

        if (isset($response['data']) === false) {

            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_GET_APPLICATION_API_FAILURE,
                $response['error']
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_GET_APPLICATION_API_FAILURE);
        }

        $applicationSpecificFields = $response['data'][Constants::APPLICATION_SPECIFIC_FIELDS];

        unset($applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING][$idArray[Constants::PERSON_ID]]);

        $personDocumentMapping = $applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING];

        if (count($personDocumentMapping) === 0) {
            unset($applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING]);
        }

        $applicationSpecificFields = [
            Constants::APPLICATION_SPECIFIC_FIELDS => $applicationSpecificFields
        ];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_REQUEST,
            [
                Constants::BUSINESS_ID => $idArray[Constants::BUSINESS_ID],
                Constants::PERSON_ID  => $idArray[Constants::PERSON_ID],
                'input' => $applicationSpecificFields
            ]);

        //PATCH the application specific fields
        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, Request::METHOD_PATCH, $applicationSpecificFields);

        if (isset($response['data']) === true) {
            //person id
            return [
                'deleted' => true
            ];
        }

        $this->trace->error(
            TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_PATCH_APPLICATION_API_FAILURE,
            $response['error']
        );

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_PATCH_APPLICATION_API_FAILURE);
    }

    public function sendCaLeadToSalesForce($input)
    {
        $repo = new MerchantAttributeRepository();

        $merchantAttribute = $repo->getKeyValues($input[MerchantConstants::MERCHANT_ID], ProductType::BANKING, Group::X_MERCHANT_CURRENT_ACCOUNTS, [MerchantAttributeType::CA_ONBOARDING_FLOW])->first();

        $caOnboardingFlow = $merchantAttribute[MerchantAttributeEntity::VALUE] ?? null;

        return $this->core()->sendCaLeadToSalesForce($input, $caOnboardingFlow);
    }

    public function sendCaLeadStatusToSalesForce($input)
    {
        return $this->core()->sendCaLeadStatusToSalesForce($input);
    }

    public function sendCaLeadToFreshDesk($input)
    {
        return $this->core()->sendCaLeadToFreshDesk($input);
    }

    public function sendRblApplicationInProgressLeadsToSalesForce(): array
    {
        return $this->core()->sendRblApplicationInProgressLeadsToSalesForce();
    }

    public function archiveBankingAccount(array $input): array
    {
        $balanceId = $input[Constants::BALANCE_ID];

        $balance = null;

        if ($balanceId !== '')
        {
            $balance = $this->repo->balance->find($balanceId);
        }

        $this->core()->removeBusinessId($input[Constants::MERCHANT_ID]);

        (new \RZP\Models\BankingAccount\Core())->archiveBankingAccount($balance);

        return ['success' => true];
    }

    public function unArchiveBankingAccount(array $input): array
    {
        $merchant = $this->repo->merchant->find($input[Constants::MERCHANT_ID]);

        $this->core()->assignBusinessId($input[Constants::MERCHANT_ID], $input);

        (new Merchant\Attribute\Core())->updateMerchantAttributeByGroupTypesAndValue(
            $merchant->getMerchantId(),
            Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS,
            [
                Merchant\Attribute\Type::CA_ALLOCATED_BANK,
                Merchant\Attribute\Type::CA_PROCEEDED_BANK
            ],
            'ICICI'
        );


        return ['success' => true];
    }

    public function handleNotifications(array $inputs): array
    {
        $res = array();

        foreach ($inputs as $input)
        {
            $errorMsg = null;
            try
            {
                (new Validator)->validateInput(Validator::HANDLE_NOTIFICATION_VALIDATION, $input);

                $notificationType = $input[Constants::NOTIFICATION_TYPE];

                $bankingAccount = $input[Constants::BANKING_ACCOUNT];

                $bankingAccountCore = new \RZP\Models\BankingAccount\Core;

                switch ($notificationType)
                {
                    case Constants::NOTIFICATION_TYPE_X_PRO_ACTIVATION:
                        $validatorOp = $input[Constants::VALIDATOR_OP];

                        $bankingAccountCore->shouldNotifyOpsAboutProActivation($validatorOp, $bankingAccount);
                        break;

                    case Constants::NOTIFICATION_TYPE_STATUS_CHANGE:
                        $bankingAccountStatusChanged    = $input[Constants::BANKING_ACCOUNT_STATUS_CHANGED];
                        $bankingAccountSubStatusChanged = $input[Constants::BANKING_ACCOUNT_SUB_STATUS_CHANGED];

                        // called when a banking_account's status or sub status is updated
                        $bankingAccountCore->notifyIfStatusChanged($bankingAccount, $bankingAccountStatusChanged, $bankingAccountSubStatusChanged);
                        break;

                    default:
                        throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE, $input);
                }

            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::BAS_SEND_NOTIFICATION_FAILED,
                    [
                        'banking_account_id' => array_get($input,'banking_account.id',''),
                        'error'              => $e->getMessage()
                    ]);

                $errorMsg = $e->getMessage();
            }
            finally
            {
                array_push($res,[
                    'banking_account_id' => array_get($input,'banking_account.id',''),
                    'success'            => empty($errorMsg),
                    'error'              => $errorMsg,
                ]);
            }
        }

        return $res;
    }

    protected function tokenizeValueViaVault(string $element) : string
    {
        $request = [
            'namespace'    => Processor::CREDENTIALS_VAULT_NAMESPACE,
            'bu_namespace' => BuNamespace::RAZORPAYX_NODAL_CERTS,
            'secret'       => $element,
        ];

        /** @var CardVaultService $cardVaultService */
        $cardVaultService = app('card.cardVault');

        $response = $cardVaultService->createVaultToken($request);

        return $response[CardVaultService::TOKEN];
    }

    /**
     * @throws ServerErrorException
     */
    public function tokenizeValues($input): array
    {
        $secretsPairs    = $input['secrets'];
        $tokenizedValues = [];

        try
        {
            foreach ($secretsPairs as $secretsPair)
            {
                $key   = $secretsPair['key'];
                $value = $secretsPair['value'];

                $tokenizedValue    = $this->tokenizeValueViaVault($value);
                $tokenizedValues[] = [
                    'key'   => $key,
                    'token' => $tokenizedValue,
                ];
            }

            return [
                'tokenized_values' => $tokenizedValues
            ];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_TOKENIZING_VALUES
            );

            throw new Exception\ServerErrorException('Error while tokenizing values', ErrorCode::SERVER_ERROR);
        }
    }

    public function getMerchantAttributes(string $merchantId, string $group): array
    {
        $merchant = $this->repo->merchant->find($merchantId);

        $attributes = (new Merchant\Attribute\Core())->fetchKeyValues($merchant, Product::BANKING, $group, []);

        return $attributes->toArrayPublic()['items'];
    }

    public function getFreeSlotForBankingAccount($input): array
    {
        $path = 'booking/slot/availableSlots';

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'GET', $input);

        return $response['data'];
    }

    public function getRecentFreeSlotForBankingAccount($input): array
    {
        $path = 'booking/slot/recentSlots';

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'GET', $input);

        return $response['data'];
    }

    public function rescheduleSlotForBankingAccount($input): array
    {
        $path = 'booking/slot/reschedule';

        $bankingAccount = $this->repo->banking_account->findByPublicId($input['id']);

        $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

        $additionalDetails = json_decode($activationDetail['additional_details'], true);

        $slotBookingDateTime = $input['slotDateAndTime'];

        $epochSlotBookingDateTime = strtotime($slotBookingDateTime);

        if(empty($activationDetail['booking_date_and_time']) === true)
        {
            $this->trace->error(
                TraceCode::SLOT_BOOKING_DATE_AND_TIME_IS_EMPTY_SLOT_CANNOT_BE_RESCHEDULED,
                [
                    'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                ]);

            return [
                'bookingDetails' => null,
                'status' => 'Failure',
                'ErrorDetail' => [
                    "errorReason" => 'Slot is not booked previously, so you cannot reschedule it, as bookingId is empty, Please book the slot first'
                ]

            ];
        }

        if($activationDetail['booking_date_and_time'] === $epochSlotBookingDateTime)
        {
            $this->trace->error(
                TraceCode::SLOT_IS_ALREADY_BOOKED_FOR_SAME_TIME_SO_SLOT_CANNOT_BE_RESCHEDULED,
                [
                    'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                ]);

            return [
                'bookingDetails' => null,
                'status' => 'Failure',
                'ErrorDetail' => [
                    "errorReason" => 'Slot is already booked for the same date and time, it cannot be booked again'
                ]

            ];
        }

        $reschedulePayload = [
            'bookingId' => $additionalDetails['booking_id'],
            'id' => $input['id'],
            'slotDateAndTime' => $input['slotDateAndTime'],
            'channel' => $input['channel'],
        ];

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'POST', $reschedulePayload);

        return $response['data'];
    }

    private function sendBusinessRequestAndProcessResponse(string $merchantId, string $path, string $method, bool $preProcess = true): array
    {
        $businessId = $this->bankingAccountService->getBusinessId($merchantId);

        $basePath = Constants::BUSINESS_PATH . '/'. $businessId;

        // if the path contains only query params
        if (strpos($path, '?') === 0)
        {
            $uri = $basePath . $path;
        }
        else
        {
            $uri = $basePath . '/' . $path;
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, [], [], $preProcess);

        return $response;
    }

    public function fetchMerchantBaApplicationStatusForIcici(string $merchantId)
    {
        $response = $this->sendBusinessRequestAndProcessResponse($merchantId, Constants::APPLICATIONS_PATH, 'GET', false);

        if (isset($response['data'][0]['application_status']) === true)
        {
            return $response['data'][0]['application_status'];
        }

        return null;
    }

    public function fetchMerchantBaPanStatusForIcici(string $merchantId)
    {
        $response = $this->sendBusinessRequestAndProcessResponse($merchantId, Constants::EXPAND_DOCUMENTS, 'GET', false);

        if (isset($response['data']['associated_documents']) === false)
        {
            return null;
        }

        $associatedDocuments = $response['data']['associated_documents'];

        $validDocTypes = ['PERSONAL_PAN', 'BUSINESS_PAN'];

        $panStatus = null;

        foreach ($associatedDocuments as $associatedDocument)
        {
            $docType = $associatedDocument[Merchant\Detail\Constants::DOCUMENT_TYPE];

            if (in_array($docType, $validDocTypes, true) === true)
            {
                $panStatus = $associatedDocument[Merchant\Detail\Constants::DOCUMENT_VERIFICATION_STATUS];

                break;
            }
        }

        return $panStatus;
    }
}

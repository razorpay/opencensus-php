<?php

namespace RZP\Models\BankingAccountService;

use Illuminate\Http\Request;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Product;
use RZP\Exception;
use RZP\Exception\ServerErrorException;
use RZP\Models\BankingAccount\Activation\Notification\Event;
use RZP\Models\BankingAccount\Activation\Notification\Notifier;
use RZP\Models\BankingAccount\Gateway\Processor;
use RZP\Models\Base;
use RZP\Models\Card\BuNamespace;
use RZP\Services\CardVault as CardVaultService;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Attribute\Entity as MerchantAttributeEntity;
use RZP\Models\Merchant\Attribute\Group as Group;
use RZP\Models\Merchant\Attribute\Repository as MerchantAttributeRepository;
use RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationDetailEntity;
use RZP\Models\BankingAccountService\Core as BankingAccountServiceCore;
use RZP\Models\Merchant\Attribute\Type as MerchantAttributeType;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Trace\TraceCode;
use RZP\Services\BankingAccountService as BasService;
use RZP\Services\Mock\BankingAccountService as BasServiceMock;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\BusinessPanForExternalRequest;
use RZP\Models\Merchant\AutoKyc\Bvs\requestDispatcher\PersonalPanForExternalRequest;

class Service extends Base\Service
{
    /** @var Request $request */
    protected $request;

    protected $validator;

    /** @var BasService|BasServiceMock $bankingAccountService */
    protected $bankingAccountService;

    /** @var BasDtoAdapter $basDtoAdapter */
    protected $basDtoAdapter;

    const SENSITIVE_UPDATE_FIELDS = [
        'password',
        'details',
        'credentials',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->request = $this->app['request'];

        /** @var BasService|\RZP\Services\Mock\BankingAccountService $bankingAccountService */
        $this->bankingAccountService = $this->app['banking_account_service'];

        $this->basDtoAdapter = new BasDtoAdapter();

        $this->validator = new Validator();
    }

    /**
     * Returns the merchant Id which should be the owner of the application
     *
     * Used for RBL Applications to fetch the business ID and validate the ownership on BAS
     *
     * The request is made on behalf of the merchant either from Admin LMS, Merchant Dashboard or MOB
     *
     * This will not be application for Partner LMS and Batch File Upload
     * In those cases the merchant Id is null
     */
    private function getRequestMerchantId()
    {
        $merchant = $this->merchant;

        if (empty($merchant) === false)
        {
            if ($this->auth->isBankLms() === false)
            {
                return $merchant->getId();
            }
        }

        return null;
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

        if ($isBusinessCreation === false)
        {
            //pull businessId and validate before forwarding request to banking account service

            /**
             * NOTE: __multi_ca__ Removing this validation from here
             * as there will be multiple business for an MID
             * and checking business id in the URL belongs to merchant id should be on BAS
             *
             * This would increase latency for calls where this should fail
             * but almost no call fails because of this validation
             * */
            // $this->core()->isvalidBusinessId($path);

            $path = Constants::BUSINESS_PATH . '/' . $path;
        }
        else
        {
            //check if business already created for the merchant
            $this->core()->isBusinessExists();

            if (array_key_exists(Constants::MERCHANT_ID, $input) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_REQUIRED);
            }

            // X doesn't support "individual" constitution (business type) at the moment
            if (array_key_exists(Constants::CONSTITUTION, $input) === true
                && $input[Constants::CONSTITUTION] === Merchant\Detail\BusinessType::INDIVIDUAL)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_CONSTITUTION_NOT_SUPPORTED);
            }

            $merchant = $this->app['basicauth']->getMerchant();

            $input[Constants::MERCHANT_ID] = $merchant->getId();

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

        if (empty($personId) === false)
        {
            $this->createOrUpdateSignatory($input, $personId, $path);

            $input = $this->getPersonDocumentDetails($input, $personId);

            unset($input[Constants::SIGNATORIES]);
        }

        if ($method === Request::METHOD_DELETE)
        {
            $idArray = $this->getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS($path, $method);

            if (empty($idArray[Constants::SIGNATORY_ID]) === false)
            {
                return $this->deleteSignatory($idArray, $method, $input);
            }
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, $input);

        if (empty($personId) === false)
        {
            //personId is attached back to the response to avoid duplicate creation of person again.
            $response[Constants::PERSON_ID] = $personId;
        }

        if ($method === Request::METHOD_POST and
            $path === Constants::BUSINESS_PATH and
            isset($response['data']) === true)
        {
            // attaching businessId to the merchant_details entity
            /**
             * NOTE: __multi_ca__ Let this remain
             * We won't allow creating another business id from merchant dashboard
             */
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
                               'path'   => $path
                           ]);

        return $this->forwardRequest($path, $input);
    }

    public function forwardLMSRequest($path, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_LMS_REQUEST,
                           [
                               'input'  => $input,
                               'method' => $this->request->getMethod(),
                               'path'   => $path
                           ]);

        $response = $this->forwardRequest($path, $input);

        if ($this->request->getMethod() === Request::METHOD_POST and
            $path === Constants::ADMIN_BANKING_ACCOUNT_APPLY_PATH)
        {
            if (isset($response['data']['business_id']) === true)
            {
                $this->trace->info(TraceCode::ASSIGN_BUSINESS_ID_FOR_ADMIN_APPLY_FOR_BANKING_ACCOUNT_IN_LMS,
                                   [
                                       'merchant_id' => $input['merchant_id'],
                                       'business_id' => $response['data']['business_id'],
                                   ]);

                // attaching businessId to the merchant_details entity
                /**
                 * NOTE: __multi_ca__ we will let this remain
                 * The latest business_id would be set
                 * This should not cause any problem for multi_ca v1
                 * as the second business id would be created for all activated merchants
                 * and FE uses bas_business_id only during onboarding
                 */
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

        if ($path === BasService::MULTI_CA_SEARCH_PATH)
        {
            return $this->multiCaSearch($input);
        }

        return $response;
    }

    protected function forwardRequest($path, $input)
    {
        if (empty($path) === true)
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
            if (isset($input[Constants::SIGNATORIES][Constants::PERSON]) === false)
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
        if (isset($signatories[Constants::PERSON_ID]) === true)
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
     * Only returns in-progress RBL banking_account and activated banking_accounts
     *
     * @param $merchantId
     * @param $bankingAccounts
     *
     * @return Base\PublicCollection
     */
    public function fetchMultipleBankingAccountsFromBas($merchantId, $bankingAccounts)
    {
        //To avoid login issue for the merchant if external call to banking_account_service fails.
        try
        {
            $bankingAccountsFromBas = $this->bankingAccountService->fetchMultipleBankingAccountsFromBas($merchantId);

            $bankingAccounts = $this->core()->attachBasBankingAccount($merchantId, $bankingAccountsFromBas, $bankingAccounts);
        }
        catch (\Throwable $e)
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

        if ($channel === 'rbl')
        {
            $bankingAccount = $this->repo->banking_account->findByPublicId($id);

            // check in db if slot is already booked for the same id and dateAnTime
            $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

            $this->trace->debug(TraceCode::SLOT_BOOKING_AND_SAVED_TIME,
                                [
                                    'booking time' => $epochSlotBookingDateTime,
                                    'Saved time'   => $activationDetail['booking_date_and_time']
                                ]);

            if (empty($activationDetail['booking_date_and_time']) === false && $activationDetail['booking_date_and_time'] === $epochSlotBookingDateTime)
            {
                $this->trace->error(
                    TraceCode::SLOT_IS_ALREADY_BOOKED_FOR_SAME_TIME_SO_SLOT_CANNOT_BE_RESCHEDULED,
                    [
                        'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                    ]);

                return [
                    'bookingDetails' => null,
                    'status'         => 'Failure',
                    'ErrorDetail'    => [
                        "errorReason" => 'Slot is already booked for the same date and time, it cannot be booked again'
                    ]

                ];
            }
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'POST', $bookingDetails);

        $responseStatus = 'Failure';

        if (key_exists('data', $response) and key_exists(Constants::STATUS, $response['data']))
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
                    'bank_account_id'         => $bankingAccount->getId(),
                    'clarity_context_enabled' => $clarityContextEnabled,
                    'slot_booking_response'   => $responseStatus
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
            $basBankingAccounts = $this->bankingAccountService->fetchMultipleActivatedAccountDetails($merchantId);

            if (empty($basBankingAccounts) === false)
            {
                $bankingAccountsFromBas = $this->core()->attachBankingAccountWithBalance($merchantId, $basBankingAccounts);

                foreach ($bankingAccountsFromBas as $basBankingAccount)
                {
                    $bankingAccounts[] = $basBankingAccount;
                }
            }
        }
        catch (\Throwable $e)
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
     *
     * @return mixed
     */
    public function getPersonDocumentDetails($input, $personId)
    {
        if (empty($input[Constants::SIGNATORIES][Constants::DOCUMENT]) === false)
        {
            $personDocumentMapping = [
                Constants::PERSONS_DOCUMENT_MAPPING => [
                    $personId => [
                        Constants::ID_PROOF      => $input[Constants::SIGNATORIES][Constants::DOCUMENT][Constants::ID_PROOF],
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
        return $this->bankingAccountService->checkServiceability($pincode);
    }

    public function checkCommonServiceability($input)
    {
        return $this->bankingAccountService->sendRequestAndProcessResponse(Constants::ALLOCATE_LEAD, 'POST', $input);

    }

    /**
     * @param        $input
     * @param        $personId
     * @param string $path
     *
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
     * @param        $method
     *
     * @return array
     * This method parses the url and returns the Ids related to Business and Application
     */
    public function getBusinessRelatedAndApplicationRelatedIdsForSignatoryCallToBAS(string $path, $method)
    {
        $result = preg_split("/[\/]/", $path);

        if ($method === Request::METHOD_POST || $method === Request::METHOD_PATCH)
        {
            return [
                Constants::BUSINESS_ID    => $result[1],
                Constants::APPLICATION_ID => $result[3]
            ];
        }
        else
        {
            if ($method === Request::METHOD_DELETE)
            {
                return [
                    Constants::BUSINESS_ID    => $result[1],
                    Constants::APPLICATION_ID => $result[3],
                    Constants::PERSON_ID      => $result[5],
                    Constants::SIGNATORY_ID   => $result[7]
                ];
            }
        }
    }

    /**
     * @param array  $idArray
     * @param string $path
     * @param string $method
     * @param        $input
     *
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
                               Constants::SIGNATORY_ID   => $idArray[Constants::SIGNATORY_ID],
                               'method'                  => $method,
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
                               Constants::PERSON_ID   => $idArray[Constants::PERSON_ID],
                               'method'               => $method,
                           ]);

        $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::PERSON_PATH . '/' . $idArray[Constants::PERSON_ID];

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
     *
     * @return bool[]
     * @throws Exception\BadRequestException
     */
    public function getAndUpdateApplicationSpecificFields($idArray, $input)
    {
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_GET_APPLICATION_REQUEST,
                           [
                               Constants::BUSINESS_ID => $idArray[Constants::BUSINESS_ID],
                               Constants::PERSON_ID   => $idArray[Constants::PERSON_ID],
                               'input'                => $input
                           ]);

        $path = Constants::BUSINESS_PATH . '/' . $idArray[Constants::BUSINESS_ID] . '/' . Constants::APPLICATIONS_PATH . '/' . $idArray[Constants::APPLICATION_ID];

        // Get the application
        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, Request::METHOD_GET, $input);

        if (isset($response['data']) === false)
        {

            $this->trace->error(
                TraceCode::BANKING_ACCOUNT_SERVICE_ERROR_GET_APPLICATION_API_FAILURE,
                $response['error']
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_GET_APPLICATION_API_FAILURE);
        }

        $applicationSpecificFields = $response['data'][Constants::APPLICATION_SPECIFIC_FIELDS];

        unset($applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING][$idArray[Constants::PERSON_ID]]);

        $personDocumentMapping = $applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING];

        if (count($personDocumentMapping) === 0)
        {
            unset($applicationSpecificFields[Constants::PERSONS_DOCUMENT_MAPPING]);
        }

        $applicationSpecificFields = [
            Constants::APPLICATION_SPECIFIC_FIELDS => $applicationSpecificFields
        ];

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_REQUEST,
                           [
                               Constants::BUSINESS_ID => $idArray[Constants::BUSINESS_ID],
                               Constants::PERSON_ID   => $idArray[Constants::PERSON_ID],
                               'input'                => $applicationSpecificFields
                           ]);

        //PATCH the application specific fields
        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, Request::METHOD_PATCH, $applicationSpecificFields);

        if (isset($response['data']) === true)
        {
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
        $this->validator->validateInput(Validator::ARCHIVE_BANKING_ACCOUNT, $input);

        $balanceId = $input[Constants::BALANCE_ID];

        $merchantId = $input[Constants::MERCHANT_ID];

        $encodedAccountNumber = '';

        /** @var Merchant\Balance\Entity $balance */
        $balance = null;

        if ($balanceId !== '')
        {
            $balance = $this->repo->balance->find($balanceId);
        }

        if ($input[Constants::IS_CA_TRANSFER])
        {
            $encodedAccountNumber = $this->encodeAccountNumberForCaTransfer($balance->getAccountNumber());
        }

        $this->repo->transactionOnLiveAndTestAndAsv(function()
            use ($merchantId, $encodedAccountNumber, $balance)
        {
            if (!empty($encodedAccountNumber))
            {
                $balance->setAccountNumber($this->encodeAccountNumberForCaTransfer($encodedAccountNumber));

                $this->repo->balance->saveOrFail($balance);
            }

            (new \RZP\Models\BankingAccount\Core())->archiveBankingAccount($balance, $encodedAccountNumber);

            $this->core()->removeBusinessId($merchantId);
        });

        return ['success' => true];
    }

    public function unArchiveBankingAccount(array $input): array
    {
        $this->validator->validateInput(Validator::UNARCHIVE_BANKING_ACCOUNT, $input);

        $merchant = $this->repo->merchant->find($input[Constants::MERCHANT_ID]);

        $bank = $input[Constants::PARTNER_BANK]; // RBL, ICICI

        $this->core()->assignBusinessId($input[Constants::MERCHANT_ID], $input);

        (new Merchant\Attribute\Core())->updateMerchantAttributeByGroupTypesAndValue(
            $merchant->getMerchantId(),
            Merchant\Attribute\Group::X_MERCHANT_CURRENT_ACCOUNTS,
            [
                Merchant\Attribute\Type::CA_ALLOCATED_BANK,
                Merchant\Attribute\Type::CA_PROCEEDED_BANK
            ],
            $bank
        );

        return ['success' => true];
    }

    public function handleNotifications(array $inputs): array
    {
        $res = array();

        foreach ($inputs as $input)
        {
            try
            {
                $bankingAccountCore = new \RZP\Models\BankingAccount\Core();
                $notifier = new Notifier();

                $validator = new Validator();
                $validator->setStrictFalse(); // to allow extra fields in input

                // Validate notification type
                $validator->validateInput(Validator::NOTIFICATION_INPUT_VALIDATION, $input);

                $notificationType = $input[Constants::NOTIFICATION_TYPE];

                switch ($notificationType)
                {
                    case Constants::NOTIFICATION_TYPE_DOCKET_EMAIL:
                        $this->handleDocketEmailNotification($input);
                        break;

                    case Constants::NOTIFICATION_TYPE_X_PRO_ACTIVATION:
                        $validator->validateInput(Validator::HANDLE_NOTIFICATION_VALIDATION, $input);
                        $bankingAccount = $input[Constants::BANKING_ACCOUNT];

                        $bankingAccountCore->notifyOpsAboutProActivation($bankingAccount);
                        break;

                    case Constants::NOTIFICATION_TYPE_STATUS_CHANGE:
                        $validator->validateInput(Validator::HANDLE_NOTIFICATION_VALIDATION, $input);
                        $bankingAccount = $input[Constants::BANKING_ACCOUNT];

                        $bankingAccountStatusChanged    = $input[Constants::BANKING_ACCOUNT_STATUS_CHANGED];
                        $bankingAccountSubStatusChanged = $input[Constants::BANKING_ACCOUNT_SUB_STATUS_CHANGED];
                        $freshDeskTicketRequired        = $input[Constants::FRESHDESK_TICKET_REQUIRED];
                        $assigneeTeamChanged            = $input[Constants::ASSIGNEE_TEAM_CHANGED];

                        // called when a banking_account's status or sub status is updated
                        $bankingAccountCore->notifyIfStatusChanged($bankingAccount, $bankingAccountStatusChanged, $bankingAccountSubStatusChanged);

                        // send push notification if status changed
                        if ($bankingAccountStatusChanged) {
                            $bankingAccountCore->notifyMerchantAboutUpdatedStatusOnMobileViaPushNotification($bankingAccount);
                        }

                        // create FD ticket if needed
                        if ($freshDeskTicketRequired) {
                            $bankingAccountCore->notifyOpsAboutProActivation($bankingAccount);
                        }

                        // trigger assignee change notification if needed
                        if ($assigneeTeamChanged) {
                            $notifier->notify($bankingAccount, Event::ASSIGNEE_CHANGE, Event::ALERT);
                        }

                        break;

                    case Constants::NOTIFICATION_TYPE_BANK_PARTNER_POC_ASSIGNED:
                        $validator->validateInput(Validator::HANDLE_NOTIFICATION_VALIDATION, $input);
                        $bankingAccount = $input[Constants::BANKING_ACCOUNT];

                        $notifier->notify($bankingAccount, Event::BANK_PARTNER_POC_ASSIGNED);
                        break;

                    case Constants::NOTIFICATION_TYPE_ACCOUNT_ACTIVATION:
                        $validator->validateInput(Validator::NOTIFICATION_ACCOUNT_ACTIVATION, $input);
                        $bankingAccount = $input[Constants::BANKING_ACCOUNT];

                        $merchant = $this->repo->merchant->findOrFail($bankingAccount['merchant_id']);

                        $bankingAccountCore->sendBankingCaActivationSmsIfApplicable($bankingAccount, $merchant);
                        break;

                    case Constants::NOTIFICATION_TYPE_WEBHOOK_DATA_AMBIGUITY:
                        $validator->validateInput(Validator::NOTIFICATION_WEBHOOK_AMBIGUITY, $input);
                        $bankingAccount = $input[Constants::BANKING_ACCOUNT];
                        $webhookData = $input[Constants::NOTIFICATION_INPUT_WEBHOOK_DATA];

                        $notifier->notify($bankingAccount, Event::ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY, Event::ALERT, $webhookData);

                        break;

                    default:
                        throw new Exception\BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INPUT_VALIDATION_FAILURE, $input);
                }

                $res[] = [
                    'notification_type'  => $notificationType,
                    'banking_account_id' => array_get($input, 'banking_account.id', ''),
                    'success'            => true,
                    'error'              => null,
                ];

            }
            catch (\Exception $e)
            {
                $this->trace->error(
                    TraceCode::BAS_SEND_NOTIFICATION_FAILED,
                    [
                        'banking_account_id' => array_get($input, 'banking_account.id', ''),
                        'error'              => $e->getMessage()
                    ]);

                $errorMsg = $e->getMessage();

                $res[] = [
                    'banking_account_id' => array_get($input, 'banking_account.id', ''),
                    'success'            => false,
                    'error'              => $errorMsg,
                ];
            }
        }

        return $res;
    }

    /**
     */
    protected function handleDocketEmailNotification($input)
    {
        $notificationData = $input[Constants::NOTIFICATION_INPUT_DOCKET_DATA];
        $validator        = new Validator();
        $validator->setStrictFalse(); // to allow extra fields in input

        $validator->validateInput(Validator::DOCKET_EMAIL_DATA_VALIDATION, $notificationData);

        $subject             = $notificationData['subject'];
        $viewData            = $notificationData['view_data'];
        $viewData['subject'] = $subject;

        $recipients      = $notificationData['recipients'];
        $otherRecipients = array_slice($recipients, 1);

        $bankingAccountCore = new \RZP\Models\BankingAccount\Core;
        $bankingAccountCore->enqueueDocketEmail($viewData, $recipients[0], $otherRecipients);
    }

    protected function tokenizeValueViaVault(string $element): string
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

        if (empty($activationDetail['booking_date_and_time']) === true)
        {
            $this->trace->error(
                TraceCode::SLOT_BOOKING_DATE_AND_TIME_IS_EMPTY_SLOT_CANNOT_BE_RESCHEDULED,
                [
                    'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                ]);

            return [
                'bookingDetails' => null,
                'status'         => 'Failure',
                'ErrorDetail'    => [
                    "errorReason" => 'Slot is not booked previously, so you cannot reschedule it, as bookingId is empty, Please book the slot first'
                ]

            ];
        }

        if ($activationDetail['booking_date_and_time'] === $epochSlotBookingDateTime)
        {
            $this->trace->error(
                TraceCode::SLOT_IS_ALREADY_BOOKED_FOR_SAME_TIME_SO_SLOT_CANNOT_BE_RESCHEDULED,
                [
                    'booking_date_and_time' => $activationDetail['booking_date_and_time'],
                ]);

            return [
                'bookingDetails' => null,
                'status'         => 'Failure',
                'ErrorDetail'    => [
                    "errorReason" => 'Slot is already booked for the same date and time, it cannot be booked again'
                ]

            ];
        }

        $reschedulePayload = [
            'bookingId'       => $additionalDetails['booking_id'],
            'id'              => $input['id'],
            'slotDateAndTime' => $input['slotDateAndTime'],
            'channel'         => $input['channel'],
        ];

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($path, 'POST', $reschedulePayload);

        return $response['data'];
    }

    private function sendBusinessRequestAndProcessResponse(string $merchantId, string $path, string $method, bool $preProcess = true): array
    {
        $businessId = $this->bankingAccountService->getBusinessId($merchantId);

        $basePath = Constants::BUSINESS_PATH . '/' . $businessId;

        // if the path contains only query params
        if (strpos($path, '?') === 0)
        {
            $uri = $basePath . $path;
        }
        else
        {
            $uri = $basePath . '/' . $path;
        }

        $response = $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, [], [], [], $preProcess);

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

    public function fetchMerchantBaApplicationStatusForRbl(string $merchantId)
    {
        $response = $this->sendBusinessRequestAndProcessResponse($merchantId, Constants::APPLICATION_PATH, 'GET', false);

        foreach ($response['data'] as $basApplication)
        {
            if ($basApplication['application_type'] === Constants::RBL_ONBOARDING_APPLICATION)
            {
                return $basApplication['application_status'];
            }
        }

        return null;
    }

    public function fetchMerchantBaPanStatus(string $merchantId)
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

    /**
     * @throws \Throwable
     */
    public function getRblApplicationFromBasForInternalFetch(string $applicationId): array
    {
        $basResponse = $this->getCompositeApplicationFromBas($applicationId);

        // convert to API structure for interal auth route and return
        $apiResponse = $this->basDtoAdapter->fromBasResponseToApiResponseForInternalFetch($basResponse);

        return $apiResponse;
    }

    /**
     *
     * @param string $applicationId Banking account id
     *
     * @param array  $apiInput
     *
     * @throws \Throwable
     */
    public function updateRBLApplicationByApplicationId(string $applicationId, array $apiInput): array
    {
        $basInput = $this->basDtoAdapter->fromApiInputToBasInput($apiInput);

        // Remove sensitive fields for tracing purposes
        $apiInputTrace = $apiInput;
        $basInputTrace = $basInput;

        foreach (self::SENSITIVE_UPDATE_FIELDS as $sensitiveAccountDetailKey)
        {
            unset($apiInputTrace[$sensitiveAccountDetailKey]);
            unset($basInputTrace[$sensitiveAccountDetailKey]);
        }

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE, [
            'stage'    => 'Send request to BAS',
            'apiInput' => $apiInputTrace,
            'basInput' => $basInputTrace,
        ]);

        $response = $this->bankingAccountService->patchRBLApplicationComposite($applicationId, $basInput, $this->getMerchantIdOrPlaceholder());

        // convert to API structure and return
        $data = $this->basDtoAdapter->fromBasResponseToApiResponse($response);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE, [
            'stage'    => 'Successful response',
            'apiInput' => $data,
        ]);

        return $data;
    }

    /**
     *
     * @param string $bankingAccountId Banking account id
     *
     * @param array $apiInput
     *
     * @throws \Throwable
     */
    public function updateRBLApplicationByReferenceNumber(string $applicationIdOrReferenceNumber, array $apiInput): array
    {
        $basInput = $this->basDtoAdapter->fromApiInputToBasInput($apiInput);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE, [
            'stage'     => 'Send request to BAS',
            'apiInput'  => $apiInput,
            'basInput'  => $basInput,
        ]);

        $response = $this->bankingAccountService->patchRBLApplicationCompositeByReferenceNumber($applicationIdOrReferenceNumber, $basInput, $this->getMerchantIdOrPlaceholder());

        // convert to API structure and return
        $data = (new BasDtoAdapter())->fromBasResponseToApiResponse($response);
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_PATCH_APPLICATION_COMPOSITE, [
            'stage'     => 'Successful response',
            'apiInput'  => $data,
        ]);
        return $data;
    }

    /**
     * @throws \Throwable
     */
    public function createRblOnboardingApplicationOnBas(Merchant\Entity $merchant, array $input, \RZP\Models\Admin\Admin\Entity|null $admin): array
    {
        $businessId = '';
        $basInput   = $this->basDtoAdapter->fromApiInputToBasInput($input);

        // update input payload
        $basInput[Constants::BUSINESS][Constants::MERCHANT_ID]                         = $merchant->getId();
        $basInput[Constants::BANKING_ACCOUNT_APPLICATION][Constants::APPLICATION_TYPE] = Constants::RBL_ONBOARDING_APPLICATION;
        $basInput[Constants::BANKING_ACCOUNT_APPLICATION][Constants::PINCODE]          = $basInput[Constants::BUSINESS][Constants::REGISTERED_ADDRESS_DETAILS][Constants::ADDRESS_PIN_CODE];
        $basInput[Constants::BANKING_ACCOUNT_APPLICATION][Constants::PERSON_DETAILS]   = [
            Constants::EMAIL_ID     => $basInput[Constants::PERSON][Constants::EMAIL_ID],
            Constants::PHONE_NUMBER => $basInput[Constants::PERSON][Constants::PHONE_NUMBER],
        ];

        // 1. check if business exists for merchantId
        $merchantDetail = $merchant->merchantDetail;
        $businessId     = $merchantDetail->getBasBusinessId();

        if (empty($businessId) || !empty($admin))
        {
            // 1.1. business does not exist or is an admin request, create business on BAS
            $response   = $this->bankingAccountService->createBusinessOnBas($basInput[Constants::BUSINESS]);
            $businessId = $response[Constants::ID];

            // 1.2. store business_id in merchant_details
            $this->assignBusinessId($merchant->getId(), [Constants::BUSINESS_ID => $businessId]);
        }

        // 2. create rbl application on BAS
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CREATE_RBL_APPLICATION_REQUEST, [
            'apiInput' => $input,
            'basInput' => $basInput
        ]);

        $response = $this->bankingAccountService->createRblOnboardingApplicationOnBas($businessId, $basInput[Constants::BANKING_ACCOUNT_APPLICATION]);

        // 3. convert to API structure & return
        $basResponse = [
            Constants::BANKING_ACCOUNT_APPLICATION => $response,
        ];

        $application = $this->basDtoAdapter->fromBasResponseToApiResponse($basResponse);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CREATE_RBL_APPLICATION_REQUEST, [
            'bas_response' => $basResponse,
            'application'  => $application,
        ]);

        return $application;
    }

    private function getProcessedQueryParamsForRblSearchLeads(array $input): array
    {
        $queryParams = [
            'application_type'        => 'RBL_ONBOARDING_APPLICATION',
            'expand_account_managers' => 'true',
        ];

        foreach ($input as $key => $value)
        {
            // BAS response will already include the required sub-entities, so passing expand[] params is not required
            if (str_starts_with($key, 'expand'))
            {
                continue;
            }

            // In some cases, BAS query param name is different. Replace if defined in the mapping
            $finalKey = Constants::SEARCH_LEADS_API_TO_BAS_QUERY_PARAM_MAPPING[$key] ?? $key;

            // BAS uses uppercase enum values for this param
            if ($key === 'business_category')
            {
                $value = strtoupper($value);
            }

            // BAS uses uppercase enum values for this param
            if ($key === 'account_type')
            {
                if (isset(Constants::API_TO_BAS_ACCOUNT_TYPE_MAPPING[$value]))
                {
                    $value = Constants::API_TO_BAS_ACCOUNT_TYPE_MAPPING[$value];
                }
                else
                {
                    // Drop query param if account_type value is not present in the mapping
                    continue;
                }
            }

            // Remove admin_ prefix for account manager filters
            if ($key === 'reviewer_id' or $key === 'ops_mx_poc_id' or $key === 'sales_poc_id' or $key === 'pending_on')
            {
                $value = str_replace('admin_', '', $value);
            }

            $queryParams[$finalKey] = $value;
        }

        return $queryParams;
    }

    /**
     * @throws \Throwable
     */
    public function fetchApplicationsForRblLms(array $input): array
    {
        $processedQueryParams = $this->getProcessedQueryParamsForRblSearchLeads($input);

        $applications = $this->bankingAccountService->fetchRblApplications($processedQueryParams);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiSearchLeadsResponseBulk($applications);
    }

    /**
     * @throws \Throwable
     */
    public function getCompositeApplicationFromBas(string $bankingAccountId): array
    {
        $bankingAccountId = $this->removeBankingAccountIdPrefix($bankingAccountId);

        return $this->bankingAccountService->getRblCompositeApplication($this->getMerchantIdOrPlaceholder(), $bankingAccountId);
    }

    /**
     * @throws \Throwable
     */
    public function fetchCompositeApplicationForRbl(string $bankingAccountId): array
    {
        $application = $this->getCompositeApplicationFromBas($bankingAccountId);

        // convert to API structure and return
        return (new BasDtoAdapter())->fromBasResponseToApiResponse($application);
    }

    /**
     *
     * @param string $bankingAccountId Banking account id
     *
     * @throws \Throwable
     */
    public function getApplicationStatusLogsForRblLms(string $bankingAccountId): array
    {
        $bankingAccountId = $this->removeBankingAccountIdPrefix($bankingAccountId);

        // Default sort order is desc in BAS, changing to asc for RBL LMS
        $queryParams = [
            'sort_order' => 'asc',
        ];

        $response = $this->bankingAccountService->getApplicationStatusLogs($this->getMerchantIdOrPlaceholder(), $bankingAccountId, $queryParams);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiStatusChangeLogsResponseBulk($response);
    }

    /**
     *
     * @param string $bankingAccountId Banking account id
     *
     * @throws \Throwable
     */
    public function getCommentsForRblLms(string $bankingAccountId): array
    {
        $bankingAccountId = $this->removeBankingAccountIdPrefix($bankingAccountId);

        $response = $this->bankingAccountService->getApplicationComments($this->getMerchantIdOrPlaceholder(), $bankingAccountId);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiCommentResponseBulk($response);
    }

    /**
     *
     * @param string $bankingAccountId Banking account id
     * @param array  $input
     *
     * @throws \Throwable
     */
    public function addCommentForRblLms(string $bankingAccountId, array $input): array
    {
        $bankingAccountId = $this->removeBankingAccountIdPrefix($bankingAccountId);

        $basInput = $this->basDtoAdapter->toBasCommentCreateRequest($input);

        $response = $this->bankingAccountService->addApplicationComment($this->getMerchantIdOrPlaceholder(), $bankingAccountId, $basInput);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiCommentResponse($response);
    }

    /**
     *
     * @param string $bankingAccountId Banking account id
     * @param array  $input
     *
     * @throws \Throwable
     */
    public function updateCommentForRbl(string $bankingAccountId, string $commentId, array $input): array
    {
        $bankingAccountId = $this->removeBankingAccountIdPrefix($bankingAccountId);

        $basInput = $this->basDtoAdapter->toBasCommentUpdateRequest($input);

        $response = $this->bankingAccountService->updateApplicationComment($this->getMerchantIdOrPlaceholder(), $bankingAccountId, $commentId, $basInput);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiCommentResponse($response);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function bulkAssignAccountManagerForRbl(array $input): array
    {
        $basInput = $this->basDtoAdapter->toBasBulkAssignAccountManagerRequest($input);

        return $this->bankingAccountService->bulkAssignAccountManagerForRbl($basInput);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function processAccountOpeningWebhookForRbl(array $input): array
    {
        $response = $this->bankingAccountService->processRblAccountOpeningWebhook($input);

        return $response;
    }

    /**
     *
     * @param string $applicationId BAS banking account application id
     * @param array  $input
     *
     * @throws \Throwable
     */
    public function activateAccountForRbl(string $applicationId): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $response = $this->bankingAccountService->activateRblAccount($this->getMerchantIdOrPlaceholder(), $applicationId);

        // convert to API structure and return
        return $this->basDtoAdapter->fromBasResponseToApiResponse($response);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function getMultipleApplicationsForRblPartnerLms(array $input): array
    {
        $response = $this->bankingAccountService->fetchRblApplicationsForPartnerLms($input);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiPartnerLmsLeadsResponse($response);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function getApplicationForRblPartnerLms(string $applicationId): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $basResponse = $this->bankingAccountService->getApplicationForRblPartnerLms($this->getMerchantIdOrPlaceholder(), $applicationId);

        // convert to API structure and return
        $apiResponse = $this->basDtoAdapter->fromBasResponseToApiResponseForPartnerLms($basResponse);

        return $apiResponse;
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function assignBankPocForRblPartnerLms(string $applicationId, string $bankPocUserId): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $basInput = $this->basDtoAdapter->toBasAssignBankPocRequest($bankPocUserId);

        $response = $this->bankingAccountService->assignBankPocForRblPartnerLms($this->getMerchantIdOrPlaceholder(), $applicationId, $basInput);

        // convert to API structure and return
        return $this->basDtoAdapter->fromBasResponseToApiResponse($response);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function getActivityForRblPartnerLms(string $applicationId, array $input): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $response = $this->bankingAccountService->getActivityForRblPartnerLms($this->getMerchantIdOrPlaceholder(), $applicationId);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiPartnerLmsActivityResponse($response, $input);
    }

    /**
     *
     * @param array $input
     *
     * @throws \Throwable
     */
    public function getCommentsForRblPartnerLms(string $applicationId): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $merchantId = $this->getMerchantIdOrPlaceholder();

        $response = $this->bankingAccountService->getCommentsForRblPartnerLms($this->getMerchantIdOrPlaceholder(), $applicationId);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiCommentResponseBulk($response);
    }

    /**
     *
     * @throws \Throwable
     */
    public function addCommentForRblPartnerLms(string $applicationId, array $input): array
    {
        $applicationId = $this->removeBankingAccountIdPrefix($applicationId);

        $merchantId = $this->getMerchantIdOrPlaceholder();

        $basInput = $this->basDtoAdapter->toBasPartnerLmsCommentCreateRequest($input);

        $this->appendBankPocUserDetails($basInput);

        $response = $this->bankingAccountService->addCommentForRblPartnerLms($this->getMerchantIdOrPlaceholder(), $applicationId, $basInput);

        // convert to API structure and return
        return $this->basDtoAdapter->toApiCommentResponse($response);
    }

    public function getMerchantIdOrPlaceholder(): string
    {
        $merchantId = $this->getRequestMerchantId();

        if (empty($merchantId) === true)
        {
            $merchantId = '_';
        }

        return $merchantId;
    }

    public function getBusinessIdForRblOnBas(): string
    {
        $businessId = '_';

        $merchantId = $this->getRequestMerchantId();

        if (empty($merchantId) === false)
        {
            // Business ID is guaranteed to exist,
            // this will throw error if business ID does not exist in merchant details
            $businessId = $this->bankingAccountService->getBusinessId($merchantId);
        }

        return $businessId;
    }

    /**
     * In case of CAs implemented in BAS (ICICI, Axis, Yesbank, RBL Migration) balance exists but not banking_account entity.
     * We make a call to banking account service to fetch the banking account id.
     *
     * @param string $balanceId
     *
     * @return string
     */
    public function fetchBankingAccountIdByBalanceId(string $balanceId) : string
    {
        $bankingAccountId = null;

        /* @var BalanceEntity $balance */
        $balance = $this->repo->balance->findOrFailById($balanceId);

        //banking account does not exist for BAS CAs only.
        if ((empty($balance->bankingAccount) === true) and
            (in_array($balance->getChannel(), Channel::getDirectTypeChannels())) and
            ($balance->getAccountType() === Merchant\Balance\AccountType::DIRECT))
        {
            //call to bas to fetch the banking_account_id.
            $bankingAccountId = $this->bankingAccountService->fetchBankingAccountId($balanceId);
        }
        else
        {
            $bankingAccountId = optional($balance->bankingAccount)->getPublicId();
        }

        return $bankingAccountId;
    }

    /**
     * Fetches account details for a specific merchant's account based on account number and channel.
     *
     * @param string $merchantId
     * @param string $accountNumber
     * @param string $channel
     *
     * @return \RZP\Models\BankingAccount\Entity|null
     *
     */
    public function fetchAccountByMerchantIdAccountNumberChannel(string $merchantId, string $accountNumber, string $channel) : \RZP\Models\BankingAccount\Entity|null
    {
        $balance = $this->repo->balance->getBalanceEntityByMerchantIdAccountNumberChannel($merchantId, $accountNumber, $channel);

        if (empty($balance))
        {
            return null;
        }

        return $this->fetchAccountByBalance($balance);
    }

    public function fetchAccountByBalance($balance) : \RZP\Models\BankingAccount\Entity|null
    {
        if (empty($balance) === true)
        {
            return null;
        }

        // This will be empty only for bankingAccounts stored on BAS
        if (empty($balance->bankingAccount) &&
            in_array($balance->getChannel(), Channel::getDirectTypeChannels()) &&
            $balance->getAccountType() === Merchant\Balance\AccountType::DIRECT)
        {
            $basBankingAccount = $this->bankingAccountService->fetchAccountDetailsByBalance($balance);

            if(empty($basBankingAccount))
            {
                return null;
            }

            return $this->core()->generateInMemoryBankingAccount($balance->getMerchantId(), $basBankingAccount);
        }

        return $balance->bankingAccount;
    }

    /**
     * Fetches activated account details for a specific merchant's account based on account number and channel.
     *
     * @param string $merchantId
     * @param string $accountNumber
     * @param string $channel
     *
     * @return \RZP\Models\BankingAccount\Entity|null
     *
     */
    public function fetchActivatedAccountByMerchantIdAccountNumberChannel(string $merchantId, string $accountNumber, string $channel) : \RZP\Models\BankingAccount\Entity|null
    {
        $bankingAccount = $this->fetchAccountByMerchantIdAccountNumberChannel($merchantId, $accountNumber, $channel);

        if (!empty($bankingAccount) && $bankingAccount->getStatus() !== \RZP\Models\BankingAccount\Status::ACTIVATED)
        {
            return null;
        }

        return $bankingAccount;
    }

    /**
     * Returns banking sensitive credentials like key, secret required to communicate to mozart
     * for fetching latest balances, payouts by checking both API & BAS DBs
     *
     * @param string $merchantId
     * @param string $channel
     * @param string $accountNumber
     *
     * @return array|null
     */
    public function fetchCredentialsFromApiAndBas(string $merchantId, string $channel, string $accountNumber) : array|null
    {
        $balance = $this->repo->balance->getBalanceEntityByMerchantIdAccountNumberChannel($merchantId, $accountNumber, $channel);

        // safety check
        if (empty($balance))
        {
            return null;
        }

        // This will be empty only for bankingAccounts stored on BAS
        if (empty($balance->bankingAccount) &&
            in_array($channel, Channel::getDirectTypeChannels()) &&
            $balance->getAccountType() === Merchant\Balance\AccountType::DIRECT)
        {
            return $this->bankingAccountService->fetchBankingCredentials($merchantId, $channel, $accountNumber);
        }

        $bankingAccount = $balance->bankingAccount;

        return [
            Constants::ID             => $bankingAccount->getId(),
            Constants::CORP_ID_CRED   => '',
            Constants::URN_CRED       => '',
            Constants::ACCOUNT_NUMBER => $accountNumber,
            Constants::CREDENTIALS    => [
                Constants::AUTH_USERNAME         => $bankingAccount->getUsername(),
                Constants::AUTH_PASSWORD         => $bankingAccount->getPassword(),
                Constants::CLIENT_ID             => $bankingAccount->getDetailsDataUsingKey(Constants::CLIENT_ID),
                Constants::CLIENT_SECRET         => $bankingAccount->getDetailsDataUsingKey(Constants::CLIENT_SECRET),
                Constants::CORP_ID_CRED          => $bankingAccount->getReference1(),
                Constants::BANK_REFERENCE_NUMBER => $bankingAccount->getBankReferenceNumber()
            ]
        ];
    }

    /**
     * Fetches ca source fund_account_id that's registered at FTS
     *
     * @param string $merchantId
     * @param string $channel
     * @param string $accountNumber
     *
     * @return string|null
     */
    public function fetchFtsFundAccountIdFromApiAndBas(string $merchantId, string $channel, string $accountNumber) : string|null
    {
        $balance = $this->repo->balance->getBalanceEntityByMerchantIdAccountNumberChannel($merchantId, $accountNumber, $channel);

        // safety check
        if (empty($balance))
        {
            return null;
        }

        // This will be empty only for CAs stored on BAS
        if (empty($balance->bankingAccount) &&
            in_array($channel, Channel::getDirectTypeChannels()) &&
            $balance->getAccountType() === Merchant\Balance\AccountType::DIRECT)
        {
            return $this->bankingAccountService->fetchFtsFundAccountIdFromBas($merchantId, $channel, $accountNumber);
        }

        return $balance->bankingAccount->getFtsFundAccountId();
    }

    /**
     * @param array $queryParams
     *
     * @return array|null
     * @throws \Throwable
     */
    public function multiCaSearch(array $queryParams)
    {
        $basResponse = $this->bankingAccountService->multiCaLeadsSearch($queryParams);

        if(empty($basResponse))
        {
            return [];
        }

        $merchantIds = array_pluck($basResponse, Constants::MERCHANT_ID);

        $vaEnabledMerchantIds = $this->repo->banking_account->fetchMerchantIdsWithActivatedNodalAccount($merchantIds);

        $multiAccountRoutingEnabledIds = $this->repo->feature->getMerchantIdsHavingFeature(\RZP\Models\Feature\Constants::ENABLE_SMART_ROUTING, $merchantIds);

        foreach ($basResponse as $index => $application)
        {
            $basResponse[$index][Constants::VA_ENABLED] = in_array($application[Constants::MERCHANT_ID], $vaEnabledMerchantIds, true);

            $basResponse[$index][Constants::MULTI_ACCOUNT_ROUTING_ENABLED] = in_array($application[Constants::MERCHANT_ID], $multiAccountRoutingEnabledIds, true);
        }

        return $basResponse;
    }

    public function rblMigrationBas(array $input) : array
    {
        $result = [];

        $bankingAccountIds = $input['banking_account_ids'];

        foreach ($bankingAccountIds as $bankingAccountId)
        {
            try
            {
                // 1. fetch necessary items from DB and prepare base $basInput
                /** @var \RZP\Models\BankingAccount\Entity $bankingAccount */
                $bankingAccount = $this->repo->banking_account->find($bankingAccountId);

                if ($bankingAccount->getStatus() === \RZP\Models\BankingAccount\Status::MIGRATED)
                {
                    $result[$bankingAccountId] = 'skipped';

                    continue;
                }

                $activationDetail = $this->repo->banking_account_activation_detail->findByBankingAccountId($bankingAccount->getId());

                $additionalDetails = json_decode(optional($activationDetail)->getAdditionalDetails() ?? '{}', true);

                // clean up additional_details
                $additionalDetails['sales_pitch_completed'] = (int)$additionalDetails['sales_pitch_completed'] ?? 0;

                $additionalDetails['calendly_slot_booking_completed'] = (int)$additionalDetails['calendly_slot_booking_completed'] ?? 0;

                $additionalDetails['agree_to_allocated_bank_and_amb'] = (int)$additionalDetails['agree_to_allocated_bank_and_amb'] ?? 0;

                if (isset($additionalDetails['rbl_new_onboarding_flow_declarations']))
                {
                    foreach ($additionalDetails['rbl_new_onboarding_flow_declarations'] as $key => $val)
                    {
                        $additionalDetails['rbl_new_onboarding_flow_declarations'][$key] = (int)$val ?? 0;
                    }
                }

                if (isset($additionalDetails['dwt_response']))
                {
                    unset($additionalDetails['dwt_response']['at_least_one_signatory_in_given_location']);
                }

                $rblActivationDetails = json_decode(optional($activationDetail)->getRblActivationDetails() ?? '{}', true);

                // add banking_account
                $apiInput = $bankingAccount->toArray();

                unset($apiInput['spocs']);

                unset($apiInput['reviewers']);

                // add banking_account_activation_detail
                $apiInput['activation_detail'] = $activationDetail->toArray();

                $bankPocUserId = $apiInput['activation_detail']['bank_poc_user_id'];

                unset($apiInput['activation_detail']['sales_poc_phone_number']);

                unset($apiInput['activation_detail']['ops_mx_poc_id']);

                unset($apiInput['activation_detail']['bank_poc_user_id']);

                $apiInput['activation_detail']['additional_details'] = $additionalDetails;

                $apiInput['activation_detail']['rbl_activation_details'] = $rblActivationDetails;

                // add banking_account_details
                $apiInput['details'] = [];
                foreach ($bankingAccount->bankingAccountDetails as $detail)
                {
                    $apiInput['details'][$detail->getAttribute('gateway_key')] = $detail->getAttribute('gateway_value');
                }

                $basInput = $this->basDtoAdapter->fromApiInputToBasInput($apiInput);

                $credentials = array_merge($basInput['credentials'], [
                    'bank_reference_number' => $bankingAccount->getAttribute('bank_reference_number')
                ]);

                unset($basInput['credentials']);

                // 2. generate business
                $business = array_merge($basInput['business'], [
                    'created_at'            => $bankingAccount->getAttribute('created_at') * 1000,
                    'updated_at'            => max($bankingAccount->getAttribute('updated_at'), $activationDetail->getAttribute('updated_at')) * 1000,
                    'merchant_id'           => $bankingAccount->getMerchantId()
                ]);

                // 3. generate person
                $person = array_merge($basInput['person'], [
                    'created_at'            => $bankingAccount->getAttribute('created_at') * 1000,
                    'updated_at'            => max($bankingAccount->getAttribute('updated_at'), $activationDetail->getAttribute('updated_at')) * 1000,
                ]);

                // 3. generate banking_account_application for BAS
                $bankingAccountApplication = array_merge($basInput['banking_account_application'], [
                    'id'                    => $bankingAccountId,
                    'created_at'            => $bankingAccount->getAttribute('created_at') * 1000,
                    'updated_at'            => max($bankingAccount->getAttribute('updated_at'), $activationDetail->getAttribute('updated_at')) * 1000,
                    'business_id'           => '', // will be computed at BAS
                    'banking_account_id'    => $bankingAccountId,
                    'application_type'      => 'RBL_ONBOARDING_APPLICATION'
                ]);

                if ($bankingAccount->getAttribute('status') === \RZP\Models\BankingAccount\Status::ARCHIVED)
                {
                    $bankingAccountApplication['sales_team'] = empty($activationDetail->getSalesTeam())? $activationDetail->getSalesTeam(): 'X_SME';
                }

                // compute partner LMS flag
                try
                {
                    (new \RZP\Models\BankingAccount\BankLms\Validator())->validateMerchantIsAttachedToPartner(
                        $bankingAccount->merchant,
                        (new \RZP\Models\BankingAccount\BankLms\Service())->getPartnerMerchant());

                    if (isset($bankingAccountApplication['metadata']))
                    {
                        $bankingAccountApplication['metadata']['is_allowed_on_partner_lms'] = true;
                    }
                    else
                    {
                        $bankingAccountApplication['metadata'] = [
                            'is_allowed_on_partner_lms' => true
                        ];
                    }
                }
                catch(\Exception $e)
                {
                    if (isset($bankingAccountApplication['metadata']))
                    {
                        $bankingAccountApplication['metadata']['is_allowed_on_partner_lms'] = false;
                    }
                    else
                    {
                        $bankingAccountApplication['metadata'] = [
                            'is_allowed_on_partner_lms' => false
                        ];
                    }
                }

                // is_documents_walkthrough_complete is stored as 0/1 in API DB
                if (isset($bankingAccountApplication['metadata']['additional_details']))
                {
                    if ($bankingAccountApplication['metadata']['additional_details']['is_documents_walkthrough_complete'] === 1)
                    {
                        $bankingAccountApplication['metadata']['additional_details']['is_documents_walkthrough_complete'] = true;
                    }
                    else
                    {
                        $bankingAccountApplication['metadata']['additional_details']['is_documents_walkthrough_complete'] = false;
                    }
                }

                // verification_date has to be converted to string
                if (isset($bankingAccountApplication['metadata']) &&
                    !empty($bankingAccountApplication['metadata']['verification_date']))
                {
                    $bankingAccountApplication['metadata']['verification_date'] = strval($bankingAccountApplication['metadata']['verification_date']);
                }

                // 4. generate banking_account for BAS
                $balance = $bankingAccount->balance;

                $basBankingAccount = array_merge($basInput['banking_account'], [
                    'id'                    => $bankingAccountId,
                    'created_at'            => $bankingAccount->getAttribute('created_at') * 1000,
                    'updated_at'            => max($bankingAccount->getAttribute('updated_at'), $activationDetail->getAttribute('updated_at')) * 1000,
                    'business_id'           => '', // will be computed at BAS
                    'account_type'          => 'CA_DIRECT',
                    'partner_bank'          => 'RBL',
                    'balance_id'            => empty($balance) ? '' : $balance->getId(),
                    'fts_fund_account_id'   => $bankingAccount->getAttribute('fts_fund_account_id'),
                    'credentials'           => $credentials
                ]);

                $bankingAccountStatus = $bankingAccount->getAttribute('status');

                if ($bankingAccountStatus === \RZP\Models\BankingAccount\Status::ACTIVATED ||
                    $bankingAccountStatus === \RZP\Models\BankingAccount\Status::ACCOUNT_ACTIVATION ||
                    $bankingAccountStatus === \RZP\Models\BankingAccount\Status::ACCOUNT_OPENING)
                {
                    $basBankingAccount['status'] = 'ACTIVE';
                }
                else
                {
                    $basBankingAccount['status'] = 'IN_PROGRESS';
                }

                // 5. generate partner_bank_application for BAS
                $partnerBankApplication = array_merge($basInput['partner_bank_application'], [
                    'created_at'            => $bankingAccount->getAttribute('created_at') * 1000,
                    'updated_at'            => max($bankingAccount->getAttribute('updated_at'), $activationDetail->getAttribute('updated_at')) * 1000,
                ]);

                if (isset($partnerBankApplication['account_opening_details']['account_opening_ftnr']))
                {
                    if ($partnerBankApplication['account_opening_details']['account_opening_ftnr'] === 1)
                    {
                        $partnerBankApplication['account_opening_details']['account_opening_ftnr'] = true;
                    }
                    else
                    {
                        $partnerBankApplication['account_opening_details']['account_opening_ftnr'] = false;
                    }
                }

                if (isset($partnerBankApplication['api_onboarding_details']['api_onboarding_ftnr']))
                {
                    if ($partnerBankApplication['api_onboarding_details']['api_onboarding_ftnr'] === 1)
                    {
                        $partnerBankApplication['api_onboarding_details']['api_onboarding_ftnr'] = true;
                    }
                    else
                    {
                        $partnerBankApplication['api_onboarding_details']['api_onboarding_ftnr'] = false;
                    }
                }

                // 6. generate banking_account_account_managers
                $bankingAccountAccountManagers = [];
                // SALES_POC(spoc)
                $spoc = $bankingAccount->spocs()->first();

                if(!empty($spoc))
                {
                    $bankingAccountAccountManagers[] = [
                        'rzp_admin_id'      => $spoc->getId(),
                        'relationship_type' => 'SALES_POC',
                    ];
                }

                // OPS_POC(reviewer)
                $reviewer = $bankingAccount->reviewers()->first();

                if(!empty($reviewer))
                {
                    $bankingAccountAccountManagers[] = [
                        'rzp_admin_id'      => $reviewer->getId(),
                        'relationship_type' => 'OPS_POC',
                    ];
                }

                // OPS_MX_POC(ops_mx_poc)
                $opxMxPoc = $bankingAccount->opsMxPocs->first();

                if (!empty($opxMxPoc))
                {
                    $bankingAccountAccountManagers[] = [
                        'rzp_admin_id'      => $opxMxPoc->getId(),
                        'relationship_type' => 'OPS_MX_POC',
                    ];
                }

                // RBL_BANK_POC(bank_poc)
                if (!empty($bankPocUserId))
                {
                    $bankingAccountAccountManagers[] = [
                        'rzp_admin_id'      => $bankPocUserId,
                        'relationship_type' => 'RBL_BANK_POC',
                    ];
                }

                // 7. generate comments
                $apiComments = $bankingAccount->getActivationComments();

                $basComments = [];

                foreach ($apiComments as $apiComment)
                {
                    if (!empty($apiComment->getAttribute('user_id')))
                    {
                        // comment was added by bank
                        $onBehalfOf = 'bank';

                        $commentedBy = $apiComment->getAttribute('user_id');
                    }
                    else
                    {
                        // comment was added by admin
                        $onBehalfOf = $apiComment->getAttribute('source_team');

                        $commentedBy = $apiComment->getAttribute('admin_id');
                    }

                    $basComment = [
                        'created_at'                        => $apiComment->getAttribute('created_at') * 1000,
                        'updated_at'                        => $apiComment->getAttribute('updated_at') * 1000,
                        'type'                              => $apiComment->getAttribute('type'),
                        'added_at'                          => $apiComment->getAttribute('added_at'),
                        'banking_account_application_id'    => $bankingAccountId,
                        'comment'                           => $apiComment->getAttribute('comment'),
                        'on_behalf_of'                      => $onBehalfOf,
                        'commented_by'                      => $commentedBy,
                        'notes'                             => json_encode([
                            'first_disposition'     => '',
                            'second_disposition'    => '',
                            'third_disposition'     => ''
                        ])
                    ];

                    $basComments[] = $basComment;
                }

                // 8. generate application_status_logs
                $bankingAccountStates = $bankingAccount->getActivationStatusChangeLog();

                $applicationStatusLogs = [];

                foreach ($bankingAccountStates as $bankingAccountState)
                {
                    if (!empty($bankingAccountState->getAttribute('user_id')))
                    {
                        // status log was added by bank
                        $createdBy = $bankingAccountState->getAttribute('user_id');
                    }
                    else
                    {
                        $createdBy = $bankingAccountState->getAttribute('admin_id');
                    }

                    $assigneeTeam = $bankingAccountState->getAttribute('assignee_team');

                    $assigneeTeam = ($assigneeTeam === 'bank_ops') ? 'ops_and_bank' : $assigneeTeam;

                    $applicationStatusLog = [
                        'created_at'                        => $bankingAccountState->getAttribute('created_at') * 1000,
                        'application_status'                => $bankingAccountState->getAttribute('status'),
                        'bank_status'                       => $bankingAccountState->getAttribute('bank_status'),
                        'banking_account_application_id'    => $bankingAccountId,
                        'created_by'                        => $createdBy,
                        'bank_sub_status'                   => '', // not applicable for RBL
                        'registration_status'               => '', // not applicable for RBL
                        'sub_status'                        => $bankingAccountState->getAttribute('sub_status'),
                        'assignee_team'                     => $assigneeTeam,
                    ];

                    $applicationStatusLogs[] = $applicationStatusLog;
                }

                $basRequest = [
                    'business'                          => $business,
                    'person'                            => $person,
                    'banking_account'                   => $basBankingAccount,
                    'banking_account_application'       => $bankingAccountApplication,
                    'partner_bank_application'          => $partnerBankApplication,
                    'application_status_logs'           => $applicationStatusLogs
                ];

                if (count($bankingAccountAccountManagers) > 0)
                {
                    $basRequest['banking_account_account_managers'] = $bankingAccountAccountManagers;
                }

                if (count($basComments) > 0)
                {
                    $basRequest['comments'] = $basComments;
                }

                $merchantId = $bankingAccount->getMerchantId();

                $this->repo->transactionOnLiveAndTestAndAsv(function() use ($basRequest, $merchantId, $bankingAccount)
                {
                    $response = $this->bankingAccountService->rblMigrationBas($basRequest);

                    $merchantDetail = $this->repo->merchant_detail->getByMerchantId($merchantId);

                    if (empty($merchantDetail->getBasBusinessId()))
                    {
                        // In a few places before making BAS call, validation is done on existence of bas_business_id
                        $merchantDetail->setBasBusinessId($response['business_id']);

                        $this->repo->merchant_detail->saveOrFail($merchantDetail);
                    }

                    $bankingAccount->edit([
                        'status'  => \RZP\Models\BankingAccount\Status::MIGRATED
                    ]);

                    $this->repo->banking_account->saveOrFail($bankingAccount);

                });

                $result[$bankingAccountId] = 'success';
            }
            catch(\Exception $e)
            {
                $result[$bankingAccountId] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Changes the last char of accountNumber to ASCII uppercase equivalent
     *
     * Example: 1234 becomes 123E
     *
     * @param string|null $accountNumber
     * @return string|null
     */
    public function encodeAccountNumberForCaTransfer(?string $accountNumber): ?string
    {
        if (empty($accountNumber) or
            ($accountNumber[-1] < '0' or $accountNumber[-1] > '9'))
        {
            return $accountNumber;
        }

        $encodedAccountNumber = substr($accountNumber, 0, strlen($accountNumber) - 1);

        $encodedAccountNumber .= chr(ord('A') + intval($accountNumber[-1]));

        return $encodedAccountNumber;
    }

    /**
     * Changes the last char of accountNumber from uppercase to integer
     *
     * Example: 123E becomes 1234
     *
     * @param string|null $accountNumber
     * @return string|null
     */
    public function decodeAccountNumberForCaTransfer(?string $accountNumber): ?string
    {
        if (empty($accountNumber) or
            ($accountNumber[-1] < 'A' or $accountNumber[-1] > 'J'))
        {
            return $accountNumber;
        }

        $decodedAccountNumber = substr($accountNumber, 0, strlen($accountNumber) - 1);

        $decodedAccountNumber .= ord($accountNumber[-1]) - ord('A');

        return $decodedAccountNumber;
    }

    /**
     * Fetches fee-recovery metadata from BAS for a given list of account_numbers
     *
     * @param array $input
     * @return mixed
     * @throws \Throwable
     */
    public function fetchFeeRecoveryMetadata(array $input)
    {
        return $this->bankingAccountService->fetchFeeRecoveryMetadata($input);
    }

    /**
     * Updates fee-recovery metadata on BAS
     *
     * @param string $businessId
     * @param string $bankingAccountId
     * @param array $input
     * @return mixed
     * @throws \Throwable
     */
    public function updateFeeRecoveryMetadata(string $businessId, string $bankingAccountId, array $input)
    {
        return $this->bankingAccountService->updateFeeRecoveryMetadata($businessId, $bankingAccountId, $input);
    }

    private function removeBankingAccountIdPrefix(string $bankingAccountId): string
    {
        if (str_starts_with($bankingAccountId, "bacc_"))
        {
            return substr($bankingAccountId, 5);
        }
        else
        {
            return $bankingAccountId;
        }
    }

    private function appendBankPocUserDetails(array &$input)
    {
        /* @var UserEntity $bankPoc */
        $bankPoc = $this->app['basicauth']->getUser();

        $input = array_merge($input, [
            ActivationDetailEntity::BANK_POC_USER_ID        => $bankPoc->getId(),
            ActivationDetailEntity::BANK_POC_NAME           => $bankPoc->getName(),
            ActivationDetailEntity::BANK_POC_EMAIL          => $bankPoc->getEmail(),
            ActivationDetailEntity::BANK_POC_PHONE_NUMBER   => $bankPoc->getContactMobile(),
        ]);
    }
}

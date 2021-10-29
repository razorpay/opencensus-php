<?php

namespace RZP\Models\BankingAccountService;

use Illuminate\Http\Request;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Core;
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

            $merchant = $this->app['basicauth']->getMerchant();

            $input[Constants::MERCHANT_ID]  = $merchant->getId();

            $input[Constants::CONSTITUTION] = $this->getBusinessType($merchant);

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
        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CRON_REQUEST,
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
        if (empty($input[Constants::SIGNATORIES][Constants::PERSON_ID]) === false)
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
        return $this->core()->sendCaLeadToSalesForce($input);
    }

    public function sendRblApplicationInProgressLeadsToSalesForce(): array
    {
        return $this->core()->sendRblApplicationInProgressLeadsToSalesForce();
    }
}

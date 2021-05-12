<?php

namespace RZP\Models\BankingAccountService;

use Illuminate\Http\Request;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\BankingAccountService;

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
        }

        $path = Constants::BUSINESS_PATH . '/' . $path;

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
            $signatories = $this->buildSignatoryPayload($personId, $input);

            $input[Constants::SIGNATORIES] = $signatories;
        }

        $response =  $this->bankingAccountService->sendRequestAndProcessResponse($uri, $method, $input);

        if(isset($response['error']) === true and
           empty($personId) === false)
        {
            //personId is attached back to the response to avoid duplicate creation of person again.
            $response[Constants::PERSON_ID] = $personId;
        }

        if($method === Request::METHOD_POST and
           $path === Constants::BUSINESS_PATH . '/' and
           isset($response['data']) === true)
        {
            //attaching businessId to the merchant_details entity
            $this->assignBusinessId($input[Constants::MERCHANT_ID], [Constants::BUSINESS_ID => $response['data']['id']]);
        }

        return $response;
    }

    public function forwardCronRequest($path, $input)
    {
        if(empty($path) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_BAS_CRON_PATH_MISSING);
        }

        $method = $this->request->getMethod();

        $queryParams = $this->request->query();

        $queryString = $this->request->getQueryString();

        $input = $this->core()->removeRequestParamsFromInput($queryParams, $input);

        $uri = $this->core()->attachRequestParamsToPath($queryString, $path);

        $this->trace->info(TraceCode::BANKING_ACCOUNT_SERVICE_CRON_REQUEST,
                           [
                               'input'  => $input,
                               'method' => $method,
                               'uri'    => $uri
                           ]);

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

    public function buildSignatoryPayload($personId, $input)
    {
        return
            [
                [
                    Constants::PERSON_ID      => $personId,
                    Constants::SIGNATORY_TYPE => $input[Constants::SIGNATORIES][Constants::SIGNATORY_TYPE],
                ]
            ];
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

}

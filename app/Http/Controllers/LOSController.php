<?php
namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Mail\Los\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use RZP\Http\Request\Requests as RzpRequest;

class LOSController extends Controller
{
    const GET    = 'GET';
    const POST   = 'POST';
    const PUT    = 'PUT';
    const PATCH  = 'PATCH';
    const DELETE = 'DELETE';

    const LEEGALITY_WEBHOOK_URL                       = 'twirp/rzp.capital.los.contracts.v1.DocSignAPI/LeegalityWebhook';

    const DISBURSE_LOAN_REGEX                         = 'DISBURSE_LOAN_REGEX';
    const SCHEDULE_VERIFICATION_REGEX                 = 'SCHEDULE_VERIFICATION_REGEX';
    const GET_SEED_INFO_REGEX                         = 'GET_SEED_INFO_REGEX';
    const GET_PRODUCTS_REGEX                          = 'GET_PRODUCTS_REGEX';
    const GET_APPLICATION_REGEX                       = 'GET_APPLICATION_REGEX';
    const UPDATE_APPLICATION_REGEX                    = 'UPDATE_APPLICATION_REGEX';
    const CREATE_APPLICATION_REGEX                    = 'CREATE_APPLICATION_REGEX';
    const GET_BUSINESS_DETAILS_REGEX                  = 'GET_BUSINESS_DETAILS_REGEX';
    const GET_BUSINESS_DETAILS_BY_REFERENCE_ID_REGEX  = 'GET_BUSINESS_DETAILS_BY_REFERENCE_ID_REGEX';
    const UPDATE_BUSINESS_DETAILS_REGEX               = 'UPDATE_BUSINESS_DETAILS_REGEX';
    const CREATE_BUSINESS_REGEX                       = 'CREATE_BUSINESS_REGEX';
    const GET_APPLICANT_REGEX                         = 'GET_APPLICANT_REGEX';
    const UPDATE_APPLICANT_REGEX                      = 'UPDATE_APPLICANT_REGEX';
    const CREATE_APPLICANT_API                        = 'CREATE_APPLICANT_API';
    const GET_DOCUMENT_GROUPS                         = 'GET_DOCUMENT_GROUPS';
    const GET_ALL_OFFERS                              = 'GET_ALL_OFFERS';
    const ACCEPT_OFFER_REGEX                          = 'ACCEPT_OFFER_REGEX';
    const GET_ALL_LOC_OFFERS                          = 'GET_ALL_LOC_OFFERS';
    const ACCEPT_LOC_OFFER_REGEX                      = 'ACCEPT_LOC_OFFER_REGEX';
    const GET_APPLICATION_CONTRACTS_REGEX             = 'GET_APPLICATION_CONTRACTS_REGEX';
    const CHECK_AGREEMENT_STATUS                      = 'CHECK_AGREEMENT_STATUS';
    const CREATE_NACH_REGEX                           = 'CREATE_NACH_REGEX';
    const GET_NACH_BY_APPLICATION_REGEX               = 'GET_NACH_BY_APPLICATION_REGEX';
    const UPLOAD_NACH_REGEX                           = 'UPLOAD_NACH_REGEX';
    const GET_NETBANKING_PAYLOAD_REGEX                = 'GET_NETBANKING_LINK_REGEX';
    const PROCESS_BANK_STATEMENT_PAYLOAD              = 'PROCESS_BANK_STATEMENT_PAYLOAD';
    const SUBMIT_OTP_REGEX                            = 'SUBMIT_OTP_REGEX';
    const GET_BUREAU_REPORT_REGEX                     = 'GET_BUREAU_REPORT_REGEX';
    const SCHEDULE_MERCHANT_VERIFICATION_REGEX        = 'SCHEDULE_MERCHANT_VERIFICATION_REGEX';
    const SCHEDULE_ADMIN_VERIFICATION_REGEX           = 'SCHEDULE_ADMIN_VERIFICATION_REGEX';
    const LIST_OR_SEARCH_REGEX                        = 'LIST_OR_SEARCH_REGEX';
    const UPLOAD_DOCUMENTS_REGEX                      = 'UPLOAD_DOCUMENTS_REGEX';
    const FETCH_LEGAL_AGREEMENT_REGEX                 = 'FETCH_LEGAL_AGREEMENT_REGEX';
    const GET_SCHEDULED_DETAILS                       = 'GET_SCHEDULED_DETAILS';
    const GET_DISBURSAL_REGEX                         = 'GET_DISBURSAL_REGEX';
    const GET_LENDER_REGEX                            = 'GET_LENDER_REGEX';
    const GET_OFFER_VERIFICATION_TASKS_REGEX          = 'GET_OFFER_VERIFICATION_TASKS_REGEX';

    const WORKFLOW_REGEX_ROUTES = [
        self::DISBURSE_LOAN_REGEX => '/capital\.los\.admin\.v1\.DisbursalAPI\/CreateDisbursal/',
    ];

    const MERCHANT_ROUTES_REGEX = [
        self::SCHEDULE_VERIFICATION_REGEX                 => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/ScheduleVerification/',
        self::GET_SEED_INFO_REGEX                         => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/GetSeedInfo/',
        self::GET_PRODUCTS_REGEX                          => '/rzp\.capital\.los\.admin\.v1\.ProductAPI\/GetProducts/',
        self::GET_APPLICATION_REGEX                       => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/GetApplication/',
        self::UPDATE_APPLICATION_REGEX                    => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/UpdateApplication/',
        self::CREATE_APPLICATION_REGEX                    => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/CreateApplication/',
        self::LIST_OR_SEARCH_REGEX                        => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/ListOrSearch/',
        self::UPLOAD_DOCUMENTS_REGEX                      => '/rzp\.capital\.los\.origination\.v1\.ApplicationAPI\/UploadDocuments/',
        self::GET_BUSINESS_DETAILS_REGEX                  => '/rzp\.capital\.los\.client\.v1\.BusinessAPI\/GetBusinessDetails/',
        self::GET_BUSINESS_DETAILS_BY_REFERENCE_ID_REGEX  => '/rzp\.capital\.los\.client\.v1\.BusinessAPI\/GetBusinessDetailsByReferenceID/',
        self::UPDATE_BUSINESS_DETAILS_REGEX               => '/rzp\.capital\.los\.client\.v1\.BusinessAPI\/UpdateBusinessDetails/',
        self::CREATE_BUSINESS_REGEX                       => '/rzp\.capital\.los\.client\.v1\.BusinessAPI\/CreateBusiness/',
        self::GET_APPLICANT_REGEX                         => '/rzp\.capital\.los\.client\.v1\.ApplicantAPI\/GetApplicant/',
        self::UPDATE_APPLICANT_REGEX                      => '/rzp\.capital\.los\.client\.v1\.ApplicantAPI\/UpdateApplicant/',
        self::CREATE_APPLICANT_API                        => '/rzp\.capital\.los\.client\.v1\.ApplicantAPI\/CreateApplicant/',
        self::GET_DOCUMENT_GROUPS                         => '/rzp\.capital\.los\.admin\.v1\.DocumentsAPI\/GetDocumentGroups/',
        self::GET_ALL_OFFERS                              => '/rzp\.capital\.los\.admin\.v1\.CreditOfferAPI\/GetAllOffers/',
        self::ACCEPT_OFFER_REGEX                          => '/rzp\.capital\.los\.admin\.v1\.CreditOfferAPI\/AcceptOffer/',
        self::GET_ALL_LOC_OFFERS                          => '/rzp\.capital\.los\.admin\.v1\.LocCreditOfferAPI\/GetAllLocOffers/',
        self::ACCEPT_LOC_OFFER_REGEX                      => '/rzp\.capital\.los\.admin\.v1\.LocCreditOfferAPI\/AcceptLocOffer/',
        self::GET_APPLICATION_CONTRACTS_REGEX             => '/rzp\.capital\.los\.contracts\.v1\.ContractsAPI\/GetApplicationContracts/',
        self::CHECK_AGREEMENT_STATUS                      => '/rzp\.capital\.los\.contracts\.v1\.DocSignAPI\/CheckAgreementStatus/',
        self::CREATE_NACH_REGEX                           => '/rzp\.capital\.los\.nach\.v1\.NachAPI\/CreateNach/',
        self::GET_NACH_BY_APPLICATION_REGEX               => '/rzp\.capital\.los\.nach\.v1\.NachAPI\/GetNachByApplication/',
        self::UPLOAD_NACH_REGEX                           => '/rzp\.capital\.los\.nach\.v1\.NachAPI\/UploadNachForm/',
        self::GET_NETBANKING_PAYLOAD_REGEX                => '/rzp\.capital\.los\.fds\.v1\.FDSBankStatementAPI\/GetNetBankingPayload/',
        self::PROCESS_BANK_STATEMENT_PAYLOAD              => '/rzp\.capital\.los\.fds\.v1\.FDSBankStatementAPI\/ProcessBankStatement/',
        self::SUBMIT_OTP_REGEX                            => '/rzp\.capital\.los\.d2c\.v1\.D2CBureauAPI\/SubmitOtp/',
        self::GET_BUREAU_REPORT_REGEX                     => '/rzp\.capital\.los\.d2c\.v1\.D2CBureauAPI\/GetBureauReport/',
        self::SCHEDULE_MERCHANT_VERIFICATION_REGEX        => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/ScheduleMerchantVerification/',
        self::SCHEDULE_ADMIN_VERIFICATION_REGEX           => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/ScheduleAdminVerification/',
        self::FETCH_LEGAL_AGREEMENT_REGEX                 => '/rzp\.capital\.los\.contracts\.v1\.DocSignAPI\/FetchLegalAgreement/',
        self::GET_SCHEDULED_DETAILS                       => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/GetScheduleDetail/',
        self::GET_OFFER_VERIFICATION_TASKS_REGEX          => '/rzp\.capital\.los\.admin\.v1\.OfferVerificationAPI\/GetOfferVerificationTasks/',
        self::GET_DISBURSAL_REGEX                         => '/rzp\.capital\.los\.admin\.v1\.DisbursalAPI\/GetDisbursal/',
        self::GET_LENDER_REGEX                            => '/rzp\.capital\.los\.admin\.v1\.LenderAPI\/GetLender/',
    ];

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $isMerchantAccessible = false;
        foreach (self::MERCHANT_ROUTES_REGEX as $route => $regex)
        {
            if (preg_match($regex, $path, $matches) === 1)
            {
                $isMerchantAccessible = true;
            }
        }

        if ($isMerchantAccessible === false) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $headers = [
            'X-Merchant-Id'    => $this->ba->getMerchant()->getId() ?? '',
            'X-Merchant-Email' => $this->ba->getMerchant()->getEmail() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url = $path;
        $body   = $request->all();

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        //foreach (self::WORKFLOW_REGEX_ROUTES as $route => $regex)
        //{
        //    if (preg_match($regex, $path, $matches) === 1)
        //    {
        //        switch ($route)
        //        {
        //            case self::DISBURSE_LOAN_REGEX:
        //                $this->startWorkflow($body);
        //                break;
        //            default:
        //                break;
        //        }
        //        break;
        //    }
        //}

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function handleLeegalityWebhook($path = null)
    {
        $request = Request::instance();
        $url = self::LEEGALITY_WEBHOOK_URL;
        $body   = $request->all();

        $headers = [
            'X-Service-Name'    => $this->ba->getInternalApp() ?? '',
            'X-Auth-Type'       => 'internal',
        ];

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $response = $this->sendRequestAndParseResponse($url, $body, $headers);
        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config = config('applications.loan_origination_system');
        $baseUrl = $config['url'];
        $username = $config['username'];
        $password = $config['secret'];
        $timeout = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id'] = $this->app['request']->getTaskId();

        $auth = [$username, $password];
        $defaultOptions = [
            'timeout' => $timeout,
            'auth'    => $auth,
        ];
        $method = self::POST;

        try
        {
            $response = RzpRequest::request(
                $baseUrl . $url,
                $headers,
                empty($body) ? "{}" : json_encode($body),
                $method,
                $defaultOptions
            );
        }
        catch (\Requests_Exception $e)
        {
            $errorCode = ($this->hasRequestTimedOut($e) === true) ?
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_TIMEOUT :
                ErrorCode::GATEWAY_ERROR_LOAN_ORIGINATION_SYSTEM_FAILURE;
            throw new Exception\IntegrationException(
                $e->getMessage(),
                $errorCode,
                null,
                $e
            );
        }
        return $this->parseResponse($response);
    }

    protected function hasRequestTimedOut(\Requests_Exception $e): bool
    {
        $message = $e->getMessage();
        return Str::contains($message, [
            'operation timed out',
            'network is unreachable',
            'name or service not known',
            'failed to connect',
            'could not resolve host',
            'resolving timed out',
            'name lookup timed out',
            'connection timed out',
            'aborted due to timeout',
        ]);
    }

    protected function parseResponse($response)
    {
        $statusCode = $response->status_code;
        $body = json_decode($response->body, true);

        $this->trace->info(TraceCode::LOAN_ORIGINATION_SYSTEM_PROXY_RESPONSE, [
            'status_code' => $statusCode,
        ]);

        if ($statusCode >= 400)
        {
            throw new Exception\TwirpException($body);
        }

        return ApiResponse::json($body, $statusCode);
    }

    protected function sendMail()
    {
        $request = Request::instance();
        $data   = $request->all();
        if ((isset($data['name']) === false) or
            (isset($data['email']) === false) or
            (isset($data['template']) === false) or
            (isset($data['subject']) === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE);
        }

        $mail = new Base($data);
        Mail::queue($mail);

        return ApiResponse::json(['success' => true]);
    }

    protected function startWorkflow($body)
    {
        $this->app['workflow']
            ->setEntityAndId('loan_origination_system', $body['disbursal']['application_id'])
            ->handle([], ['status' => 'loan_origination_system_workflow_started']);
    }
}

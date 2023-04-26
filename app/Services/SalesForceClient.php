<?php

namespace RZP\Services;

use Carbon\Carbon;
use \WpOrg\Requests\Exception as Requests_Exception;
use \WpOrg\Requests\Response;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Jobs\SalesforceRequestJob;
use RZP\Exception\BadRequestException;

class SalesForceClient
{
    const SALESFORCE_ACCESS_TOKEN_CACHE_KEY = "SALESFORCE_ACCESS_TOKEN_CACHE_KEY";
    const CACHE_TTL_SECONDS = 1 * 60 * 60; // 1 hour in seconds

    protected $baseUrl;

    protected $username;

    protected $password;

    protected $client_id;

    protected $client_secret;

    protected $grant_type;

    protected $config;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * BasicAuth entity
     * @var BasicAuth
     */
    protected $auth;

    // Constants

    const ACCESS_TOKEN    = 'access_token';

    const DATE_FORMAT     = 'Y-m-d';

    const JSON_METHOD     = [self::POST, self::PUT, self::PATCH];

    // Request Constants
    const HEADERS               = 'headers';
    const CONTENT               = 'content';
    const OPTIONS               = 'options';
    const STATUS_CODE           = 'status_code';
    const URL                   = 'url';
    const METHOD                = 'method';
    const APPLICATION_JSON      = 'application/json';
    const TIMEOUT               = 'timeout';
    const POST                  = 'POST';
    const PUT                   = 'PUT';
    const PATCH                 = 'PATCH';
    const GET                   = 'GET';
    const REQUIRED_FIELD_MISSING = 'REQUIRED_FIELD_MISSING';
    const INVALID_FIELD          = 'INVALID_FIELD';
    const INVALID_EMAIL_ADDRESS  = 'INVALID_EMAIL_ADDRESS';

    const DASHBOARD_UPSERT_URL  = '/services/apexrest/DashboardOpportunityUpsert';

    const VALIDATION_ERROR_CODES   = [self::REQUIRED_FIELD_MISSING, self::INVALID_FIELD, self::INVALID_EMAIL_ADDRESS];

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->auth = $app['basicauth'];

        $this->config = $app['config']->get('applications.salesforce');

        $this->baseUrl = $this->config['url'];

        $this->username = $this->config['username'];

        $this->password = $this->config['password'];

        $this->client_id = $this->config['client_id'];

        $this->client_secret = $this->config['client_secret'];

        $this->grant_type = 'password';
    }

    protected function getAccessTokenRequest()
    {
        $url = $this->generateUrl();

        $request = [
            'url'     => $url,
            'method'  => 'POST',
            'content' => [],
            'options' => [],
            'headers' => [
                RequestHeader::CONTENT_TYPE => 'application/json',
            ]
        ];

        return $request;
    }

    public function fetchAccessToken(bool $skipCache = false)
    {
        $accessToken = $this->getCachedAccessToken($skipCache);

        if (empty($accessToken) === false)
        {
            return $accessToken;
        }

        $request = $this->getAccessTokenRequest();

        $response = $this->makeRequestAndGetResponse($request);

        $accessToken = $this->parseAccessToken($response);

        if ($accessToken === null)
        {
            $this->trace->error(TraceCode::SALESFORCE_ACCESS_TOKEN_ERROR, $response);

            throw new Exception\IntegrationException('Unable to parse and fetch Access Token');
        }

        $this->setCachedAccessToken($accessToken);

        return $accessToken;
    }

    private function getCachedAccessToken(bool $skipCache)
    {
        $accessToken = null;

        if ($skipCache)
        {
            return null;
        }

        try
        {
            $accessToken = app('cache')->store($this->getDriver())->get(self::SALESFORCE_ACCESS_TOKEN_CACHE_KEY);

            if (empty($accessToken) === false)
            {
                $accessToken  = app('encrypter')->decrypt($accessToken);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SALESFORCE_TOKEN_CACHE_FETCH_ERROR);
        }

        return $accessToken;
    }

    private function setCachedAccessToken(string $accessToken)
    {
        $accessToken  = app('encrypter')->encrypt($accessToken);

        app('cache')->store($this->getDriver())
                            ->set(self::SALESFORCE_ACCESS_TOKEN_CACHE_KEY, $accessToken, self::CACHE_TTL_SECONDS);
    }

    protected function getDriver()
    {
        return app('config')->get('cache.secure_default');
    }

    /**
     * Send Lead `status` and `sub_status` update from LMS to Salesforce Current Application Dashboard
     *
     * $payload = [
     *       'merchant_id'      => $bankingAccount->getMerchantId(),
     *       'ca_id'            => 'bacc_'.$bankingAccount->getId(),
     *       'ca_type'          => 'RBL',
     *       'ca_status'        => $status,  // valid status
     *       'ca_substatus'     => $subStatus,   // valid sub-status
     * ]
     *
     * @param array $payload
     * @param string $process `RBL` or `ICICI`
     */
    public function sendLeadStatusUpdate(array $payload, string $process)
    {
        $url = $this->generateUrlForLeadStatusupdate();

        $payloadJson = json_encode($payload);

        $data = [
            'CX_Source__c' => 'LMS',
            'CX_Process__c' => $process,
            'CX_Payload__c' => $payloadJson,
        ];

        $this->trace->info(TraceCode::SALESFORCE_STATUS_UPDATE_REQUEST, $data);

        $this->dispatchRequestJob($url,
                                  $data,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_REQUEST,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_RESPONSE,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_EXCEPTION
        );

    }

    public function sendPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $data = $this->payloadGenerationForPreSignupDetails($input, $merchant);

        $this->trace->info(TraceCode::SALESFORCE_PRE_SIGNUP_REQUEST, $data);

        $this->dispatchRequestJob($url,
                                  $data,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_REQUEST,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_RESPONSE,
                                  TraceCode::SALESFORCE_PRE_SIGNUP_EXCEPTION
        );
    }

    public function sendProductSwitchDetails(array $input, Merchant\Entity $merchant)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $input['contact_mobile'] = $merchant->merchantDetail->getContactMobile() ?? '';

        // Using the same function to generate payload as that of pre-signup
        // since call to the same salesforce API is being made
        $data = $this->payloadGenerationForPreSignupDetails($input, $merchant);

        $this->trace->info(TraceCode::SALESFORCE_PRODUCT_SWITCH_REQUEST, $data);

        $this->dispatchRequestJob($url,
            $data,
            TraceCode::SALESFORCE_PRODUCT_SWITCH_REQUEST,
            TraceCode::SALESFORCE_PRODUCT_SWITCH_RESPONSE,
            TraceCode::SALESFORCE_PRODUCT_SWITCH_EXCEPTION
        );
    }

    public function sendXOnboardingToSalesforce($data)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_X_ONBOARDING_CATEGORY_UPDATE_REQUEST,
                                  TraceCode::SALESFORCE_X_ONBOARDING_CATEGORY_UPDATE_RESPONSE,
                                  TraceCode::SALESFORCE_X_ONBOARDING_CATEGORY_UPDATE_ERROR
        );
    }

    public function sendCaOnboardingToSalesforce($data)
    {
        $url = $this->generateUrlForMerchantUpsert();

        // Convert business_type string to int, since SF stores it as an int
        if (empty($data['Business_Type']) === false)
        {
            $data['Business_Type'] = $this->getCaBusinessTypeIndex($data['Business_Type']);
        }

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_CA_ONBOARDING_FLOW_UPDATE_REQUEST,
            TraceCode::SALESFORCE_CA_ONBOARDING_FLOW_UPDATE_RESPONSE,
            TraceCode::SALESFORCE_CA_ONBOARDING_FLOW_UPDATE_ERROR);

        return $data;
    }

    public function sendUserDetailsToSalesforce($data)
    {
        $accessToken = $this->fetchAccessToken();

        $url = $this->generateUrlForCreateUserDetails();

        $request  = [
            'url'     => $url,
            'method'  => self::POST,
            'content' => $data,
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => sprintf('%s %s',RequestHeader::BEARER, $accessToken)
            ]
        ];

        $response = $this->makeRequest($request);

        return $response;
    }

    public function sendLeadUpsertEventsToSalesforce(array $data)
    {
        $url = $this->generateUrlForWebsiteLeadUpsert();

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_EVENT_REQUEST,
            TraceCode::SALESFORCE_EVENT_RESPONSE,
            TraceCode::SALESFORCE_EVENT_ERROR
        );
    }

    public function sendPartnerInfo(Merchant\Entity $partner)
    {
        if ($partner->isPartner() === false)
        {
            return;
        }

        $url = $this->generateUrlForMerchantUpsert();

        $data = [
            Merchant\Entity::MERCHANT_ID  => $partner->getId(),
            Merchant\Entity::PARTNER_TYPE => $partner->getPartnerType()
        ];

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_PARTNER_TYPE_REQUEST,
                                  TraceCode::SALESFORCE_PARTNER_TYPE_RESPONSE,
                                  TraceCode::SALESFORCE_PARTNER_TYPE_EXCEPTION
        );
    }

    public function sendCouponInfo(Merchant\Entity $merchant, array $input)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $data = [
            Merchant\Entity::MERCHANT_ID => $merchant->getId(),
            'promotion_code'             => $input['coupon_code'],
        ];

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_COUPON_REQUEST,
                                  TraceCode::SALESFORCE_COUPON_RESPONSE,
                                  TraceCode::SALESFORCE_COUPON_EXCEPTION
        );
    }

    public function sendPartnerLeadInfo(string $merchantId, string $partnerId, string $product, array $extraData = [])
    {
        $url = $this->generateUrlForMerchantUpsert();

        $leadData = [
            Merchant\Entity::MERCHANT_ID  => $merchantId,
            Merchant\Entity::PARTNER_ID   => $partnerId,
            'source_detail'               => $product
        ];

        $leadData = array_merge($leadData, array_filter($extraData));

        $this->dispatchRequestJob(
            $url,
            $leadData,
            TraceCode::SALESFORCE_PARTNERSHIP_LEAD_REQUEST,
            TraceCode::SALESFORCE_PARTNERSHIP_LEAD_RESPONSE,
            TraceCode::SALESFORCE_PARTNERSHIP_LEAD_EXCEPTION
        );
    }

    public function payloadGenerationForPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        $data = [
            'merchant_id'      => $merchant->getId(),
            'email'            => $merchant->getEmail(),
            'name'             => $merchant->getName(),
            'business_banking' => (int)$merchant->isBusinessBankingEnabled()
        ];

        $keyMap = [
            'business_name'         => 'business_name',
            'business_type'         => 'business_type',
            'contact_mobile'        => 'contact_mobile',
            'transaction_volume'    => 'transaction_volume',
            'website'               => 'Ref_Website',
            'first_utm_campaign'    => 'Traffic_Campaign',
            'first_utm_medium'      => 'Traffic_Medium',
            'first_utm_source'      => 'Traffic_Source',
            'first_page'            => 'Traffic_Page',
            'first_utm_term'        => 'final_click_attribution_term',
            'final_utm_medium'      => 'Last_Click_Medium',
            'final_utm_source'      => 'Last_Click_Source',
            'final_utm_term'        => 'Last_Click_Term',
            'final_utm_campaign'    => 'Last_Click_Campaign',
            'final_page'            => 'Last_Click_Page',
            'x_onboarding_category' => 'x_onboarding_category',
            'ca_onboarding_flow'    => 'ca_onboarding_flow',
            'x_channel'             => 'X_Channel',
            'x_subchannel'          => 'X_Subchannel',
            'lead_progress'         => 'lead_progress'
        ];

        foreach ($keyMap as $key => $value)
        {
            $this->checkAndInsert($input, $key, $data, $value);
        }

        return $data;
    }


    public function payloadGenerationForStatusUpdate(BankingAccount\Entity $bankingAccount)
    {
        $payload = [
            'merchant_id'      => $bankingAccount->getMerchantId(),
            'ca_id'            => $bankingAccount->getId(),
            'ca_type'          => $bankingAccount->getChannel(),
            'ca_status'        => $bankingAccount->getStatus(),
            'ca_substatus'     => $bankingAccount->getSubStatus(),
        ];

        return $payload;
    }

    public function checkAndInsert($input,$key, & $output, $outputKey)
    {
        if ($input != null && isset($input[$key]))
        {
            $output[$outputKey] = $input[$key];
        }
    }

    public function fetchAccountDetails($nextUrl = '', $timeStamp = 0, $timeBased = false)
    {
        $accessToken = $this->fetchAccessToken();

        if ($timeStamp > 0 or $timeBased)
        {
            $url = $this->generateUrlForAccountWithTimeStampFetch($timeStamp);
        }
        else
        {
            $url = $this->generateUrlForAccountFetch($nextUrl);
        }

        $request = [
            'url'     => $url,
            'method'  => 'GET',
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => RequestHeader::BEARER . ' ' . $accessToken,
            ]
        ];

        $response = $this->makeRequestAndGetResponse($request);

        return $response;
    }

    public function payloadGenerationForInterestOfPrimaryMerchantInBanking(Merchant\Entity $merchant)
    {
        $payLoad = [
            [
                "merchant_id"            => $merchant->getId(),
                "name"                   => $merchant->getName(),
                "email"                  => $merchant->getEmail(),
                "activated"              => (int)$merchant->isActivated(),
                "signup_date"            => epoch_format($merchant->getCreatedAt(), self::DATE_FORMAT),
                "business_name"          => $merchant->merchantDetail->getBusinessName(),
                "contact_name"           => $merchant->merchantDetail->getContactName(),
                "business_banking"       => (int)$merchant->isBusinessBankingEnabled(),
                "submission_date"        => date(self::DATE_FORMAT),
                "submitted"              => 1,
            ]
        ];

        $contact = $merchant->merchantDetail->getContactMobile();

        if (empty($contact) === false)
        {
            $payLoad[0]['contact_mobile'] = $contact;
        }

        return $payLoad;
    }

    public function captureInterestOfPrimaryMerchantInBanking(Merchant\Entity $merchant)
    {
        $url = $this->generateUrlForMerchantUpsert();

        $payload = $this->payloadGenerationForInterestOfPrimaryMerchantInBanking($merchant);

        $this->dispatchRequestJob($url,
                                  $payload,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_REQUEST,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_RESPONSE,
                                  TraceCode::SALESFORCE_INTEREST_IN_X_ERROR);
    }

    public function sendEventToSalesForce(array $eventPayload) {
        $this->dispatchRequestJob(
            $this->generateUrlForOpportunityUpsert(),
            $eventPayload,
            TraceCode::SALESFORCE_EVENT_REQUEST,
            TraceCode::SALESFORCE_EVENT_RESPONSE,
            TraceCode::SALESFORCE_EVENT_ERROR);
    }

    public function getMerchantDetailsOnOpportunity(string $merchantId, array $opportunities): array {

        $accessToken = $this->fetchAccessToken();

        $opportunityInClause = implode("','", $opportunities);
        $merchantDetailQuery = "select Account.Merchant_ID__c,
                                    Opportunity.Type,
                                    Opportunity.StageName,
                                    Opportunity.Loss_Reason__c,
                                    Opportunity.LastModifiedDate,
                                    Opportunity.Owner.name,
                                    Opportunity.Owner_Role__c
                                from Opportunity
                                where Account.Merchant_ID__c = '{$merchantId}'
                                and Opportunity.Type in ('$opportunityInClause')";

        $queryURL = $this->baseUrl . '/services/data/v34.0/query?q=' . $merchantDetailQuery;

        $request = [
            'url'     => $queryURL,
            'method'  => self::GET,
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => RequestHeader::BEARER . ' ' . $accessToken
            ]
        ];

        return $this->makeRequestAndGetResponse($request);
    }


    public function getSalesForceTeamNameForMerchantID(array $merchantIds)
    {
        $teamNameArray = array();

        foreach ($merchantIds as $merchantId )
        {
            $teamNameArray[$merchantId] = null;
        }

        $accessToken = $this->fetchAccessToken();

        $merchantIdsInClause = implode("','", $merchantIds);

        $merchantDetailQuery = "select Merchant_ID__c,
                                Name,
                                Owner_Role__c,
                                Owner.name
                                from Account
                                where Merchant_ID__c != null
                                and Owner_Role__c != null
                                and Transacting__c = true
                                and Merchant_ID__c in ('$merchantIdsInClause')";

         $queryURL = $this->baseUrl . '/services/data/v34.0/query?q='. $merchantDetailQuery;
         $request = [
              'url'     => $queryURL,
              'method'  => self::GET,
              'content' => [],
              'options' => ['timeout' => 120],
              'headers' => [
                  RequestHeader::CONTENT_TYPE  => 'application/json',
                  RequestHeader::AUTHORIZATION => RequestHeader::BEARER . ' ' . $accessToken
              ]
          ];

         $response = $this->makeRequestAndGetResponse($request);

        if (empty($response["records"]) === false)
        {
            foreach ($response["records"] as $entity)
            {
                $teamNameArray[$entity["Merchant_ID__c"]] = $entity["Owner"]["Name"];
            }

        }

        return $teamNameArray;
    }

    /*
     * This function takes merchantId as input and return salesPOC or "salesforce owner email" for that merchant
     */
    public function getSalesPOCForMerchantID($merchantId)
    {
        $accessToken = $this->fetchAccessToken();

        $salesPOCQuery = "select Owner.Email from Account where Merchant_ID__c = '$merchantId'";

        $queryURL = $this->baseUrl . '/services/data/v34.0/query?q=' . $salesPOCQuery;
        $request  = [
            'url'     => $queryURL,
            'method'  => self::GET,
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => sprintf('%s %s',RequestHeader::BEARER, $accessToken)
            ]
        ];

        $response = $this->makeRequestAndGetResponse($request);

        if (empty($response['records']) ==true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, $response, 'Merchant Sales POC Data Not Found');
        }

        return $response['records'][0]['Owner']['Email'];
    }

    public function getPartnershipSalesPOCForMerchantId($merchantId)
    {
        $accessToken = $this->fetchAccessToken();

        $salesPOCQuery = "select
                          Enabler_POC__r.Name,
                          Enabler_POC__r.Email,
                          Enabler_POC__r.Phone,
                          Enabler_POC__r.Title
                          from
                          Account
                          where
                          Merchant_ID__c = '$merchantId'";

        $queryURL = $this->baseUrl . '/services/data/v34.0/query?q=' . $salesPOCQuery;
        $request  = [
            'url'     => $queryURL,
            'method'  => self::GET,
            'content' => [],
            'options' => ['timeout' => 120],
            'headers' => [
                RequestHeader::CONTENT_TYPE  => 'application/json',
                RequestHeader::AUTHORIZATION => sprintf('%s %s',RequestHeader::BEARER, $accessToken)
            ]
        ];

        $response = $this->makeRequestAndGetResponse($request);

        if (empty($response['records']) ==true)
        {
           return [];
        }

        return $response['records'][0];
    }

    public function getSalesforceDetailsForMerchantIDs(array $merchantIds) : array
    {
        $details = [];

        foreach ($merchantIds as $merchantId )
        {
            $details[$merchantId] = null;
        }

        $merchantIdsInClause = implode("','", $merchantIds);

        $merchantDetailQuery = "select Merchant_ID__c,
                                Name,
                                Owner_Role__c,
                                Owner.name
                                from Account
                                where Merchant_ID__c != null
                                and Owner_Role__c != null
                                and Transacting__c = true
                                and Merchant_ID__c in ('$merchantIdsInClause')";

        $response = $this->fetchAccountDetails($merchantDetailQuery);

        if (empty($response["records"]) === false)
        {
            foreach ($response["records"] as $entity)
            {
                $details[$entity["Merchant_ID__c"]] = ["owner_role" => $entity["Owner_Role__c"]];
            }
        }

        return $details;
    }

    protected function parseAccessToken($response)
    {
        if (isset($response[self::ACCESS_TOKEN]) === true)
        {
            return $response[self::ACCESS_TOKEN];
        }

        return null;
    }

    protected function generateUrlForMerchantUpsert()
    {
        return $this->baseUrl . '/services/apexrest/MerchantUpsert';
    }

    protected function generateUrlForLeadStatusupdate()
    {
        return $this->baseUrl . '/services/data/v53.0/sobjects/CX_CurrentAccount_Event__e';
    }

    protected function generateUrlForOpportunityUpsert()
    {
        return $this->baseUrl . self::DASHBOARD_UPSERT_URL;
    }

    protected function generateUrlForCreateUserDetails()
    {
        return $this->baseUrl . '/services/data/v55.0/sobjects/Lead';
    }

    protected function generateUrlForWebsiteLeadUpsert()
    {
        return $this->baseUrl . '/services/apexrest/leadupsertprd';
    }

    protected function generateUrlForAccountFetch(string $nextUrl)
    {
        if (empty($nextUrl) === false)
        {
            return $this->baseUrl . $nextUrl;
        }

        return $this->baseUrl . '/services/data/v34.0/query?q=select Account.Merchant_ID__c, Account.Owner.Email, Owner_Role__c, Managers_in_role_hierarchy__c from Account where Owner_Role__c != null AND Merchant_ID__c != null AND ((NOT Website like \'%25mswipe%25\') OR (Transacting__c = true))';
    }

    protected function generateUrlForAccountWithTimeStampFetch(int $timeStamp = 0)
    {
        if ($timeStamp <= 0)
        {
            $timeStamp = Carbon::now('Asia/Kolkata')->timestamp ;
        }

        $timeStamp = $timeStamp - 7200;

        $dateTime = Carbon::createFromTimestamp($timeStamp)->format('Y-m-d\Th:i:s\Z');

        return $this->baseUrl . '/services/data/v34.0/query?q=select Account.Merchant_ID__c, Account.Owner.Email,Owner_Role__c, Managers_in_role_hierarchy__c, MRH_Date__c from Account where Owner_Role__c != null AND Merchant_ID__c != null AND ((NOT Website like \'%25mswipe%25\') OR (Transacting__c = true)) and MRH_Date__c >' . $dateTime;
    }

    protected function generateUrl()
    {
        $queryParams = [
            'grant_type'    => $this->grant_type,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'username'      => $this->username,
            'password'      => $this->password,
        ];

        $url = $this->baseUrl . '/services/oauth2/token' . '?';

        $url = $url . http_build_query($queryParams);

        return $url;
    }

    protected function makeRequestAndGetResponse(array $request)
    {
        $response = $this->sendRequest($request);
        if ($response->status_code != 200 and $response->status_code != 201){
            throw new Exception\IntegrationException("Salesforce Returned non 200 Response");
        }
        return json_decode($response->body, true);
    }

    protected function makeRequest(array $request)
    {
        $response = $this->sendRequest($request);

        $response_body = json_decode($response->body, true);

        if ($response->status_code != 200 and $response->status_code != 201)
        {
            if(sizeof($response_body) > 0 &&
                empty($response_body[0]['errorCode']) === false)
            {
                if($response_body[0]['errorCode'] === 'DUPLICATES_DETECTED')
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SALESFORCE_DUPLICATES_RECORD_DETECTED);
                }

                if(in_array($response_body[0]['errorCode'], self::VALIDATION_ERROR_CODES, true) === true)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_SALESFORCE_FIELD_VALIDATION_ERROR);
                }
            }

            throw new Exception\IntegrationException("Salesforce Returned non 200 Response");
        }

        return $response_body;
    }

    public function sendRequest(array $request): \WpOrg\Requests\Response
    {
        try
        {
            $this->trace->info(TraceCode::SALESFORCE_INTEGRATION_API_REQUEST, $this->getTraceableRequest($request));

            $response = $this->getResponse($request);

            $this->traceResponse($response);
        }
        catch (Requests_Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SALESFORCE_INTEGRATION_ERROR,
                $this->getTraceableRequest($request));

            throw new Exception\IntegrationException('Fail to fetch Salesforce Data');
        }

        return $response;
    }

    protected function traceResponse($response)
    {
        $responseArr = json_decode($response->body, true);

        $responseBody = array_except($responseArr, self::ACCESS_TOKEN);

        $payload = [
            'status_code' => $response->status_code,
            'success'     => $response->success,
            'response'    => $responseBody,
        ];

        $this->trace->info(TraceCode::SALESFORCE_INTEGRATION_API_RESPONSE, $payload);
    }

    /**
     * Filters request array and returns only traceable data
     *
     * @param array $request
     *
     * @return array
     */
    public function getTraceableRequest(array $request): array
    {
        $request = $this->removeQueryParamsFromUrl($request);

        return array_only($request, ['url', 'method', 'content']);
    }

    public function getTraceableRequestWithoutPayload(array $request): array
    {
        $request = $this->removeQueryParamsFromUrl($request);

        return array_only($request, ['url', 'method']);
    }

    /**
     * Removing Sensitive Information from Request URL
     *
     * @param array $input
     *
     * @return array
     */
    protected function removeQueryParamsFromUrl(array $input): array
    {
        $input['url'] = strtok($input['url'], '?');

        return $input;
    }

    protected function getResponse(array $request)
    {
        $content = $request['content'];

        if (in_array($request['method'], self::JSON_METHOD) and (is_array($content) === true))
        {
            $content = json_encode($content);
        }

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $content,
            $request['method'],
            $request['options']);

        return $response;
    }

    /**
     * Dispatches a request job to queue
     *
     * @param $url
     * @param $payload
     * @param $traceCodeRequest
     * @param $traceCodeResponse
     * @param $traceCodeError
     */
    protected function dispatchRequestJob($url, $payload, $traceCodeRequest, $traceCodeResponse, $traceCodeError)
    {
        $request = [
            self::URL     => $url,
            self::METHOD  => self::POST,
            self::CONTENT => json_encode($payload),
            self::OPTIONS => [
                self::TIMEOUT => 20
            ],
            self::HEADERS => [
                RequestHeader::CONTENT_TYPE  => self::APPLICATION_JSON,
            ],
        ];

        SalesforceRequestJob::dispatch($request,
                                       $traceCodeRequest,
                                       $traceCodeResponse,
                                       $traceCodeError);
    }

    public function sendCaLeadDetails($data)
    {
        $url = $this->generateUrlForOpportunityUpsert();

        $this->dispatchRequestJob($url, $data, TraceCode::SALESFORCE_CA_EVENT_REQUEST,
                                  TraceCode::SALESFORCE_CA_EVENT_RESPONSE,
                                  TraceCode::SALESFORCE_CA_EVENT_ERROR
        );
    }

    public function getCaBusinessTypeIndex($businessType)
    {
        $businessTypeMapping = [
            'PUBLIC_LIMITED'        => 5,
            'PRIVATE_LIMITED'       => 4,
            'LLP'                   => 6,
            'ONE_PERSON_COMPANY'    => 12,
            'PROPRIETORSHIP'        => 1,
            'PARTNERSHIP'           => 3,
            'INDIVIDUAL'            => 2,
            'TRUST'                 => 9,
            'NGO'                   => 7,
            'SOCIETY'               => 10,
            'UNREGISTERED'          => 11,
        ];

        return $businessTypeMapping[$businessType];
    }
}

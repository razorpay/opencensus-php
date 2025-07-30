<?php

namespace RZP\Services;

use Request;
use RZP\Error;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\FundAccount\Type;
use RZP\Exception\BadRequestException;
use \WpOrg\Requests\Hooks as Requests_Hooks;

use Symfony\Component\HttpFoundation\Response;
use RZP\Models\Contact\Entity as ContactEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

class CFAService
{
    const X_RAZORPAY_TASKID = 'grpc-metadata-X-Task-Id';

    const X_NAMESPACE = 'grpc-metadata-x-namespace';

    const X_RAZORPAY_MERCHANT_ID = 'x-merchant-id';

    const SUCCESS = 'success';

    const MAX_RETRY_COUNT = 1;

    const ERROR = 'error';

    const ENABLED_ACCOUNT_TYPES = [
        FundAccountEntity::BANK_ACCOUNT,
        FundAccountEntity::VPA,
        FundAccountEntity::WALLET_ACCOUNT
    ];

    protected $baseUrl;

    protected $config;

    protected $trace;

    protected $app;

    protected $request;

    protected $mode;

    public function __construct($app = null)
    {
        if (empty($app) === true)
        {
            $app = app();
        }

        $this->mode = $app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.cfa_service');

        $this->request = $app['request'];

        $this->app = $app;

        $this->setBaseUrl();
    }

    /**
     * Set the base URL based on mode
     */
    protected function setBaseUrl()
    {
        if (isset($this->config['url'][$this->mode]))
        {
            $this->baseUrl = $this->config['url'][$this->mode];
        }
        else
        {
            $this->baseUrl = $this->config['url'] ?? 'https://cfa.dev.razorpay.in';
        }
    }

    /**
     * Get authentication credentials based on mode
     * 
     * @return array Array containing key and secret
     */
    protected function getAuthCredentials()
    {
        $credentials = [
            'key'    => null,
            'secret' => null,
        ];

        $credentials['key'] = $this->config['username'] ?? null;
        $credentials['secret'] = $this->config['password'] ?? null;

        return $credentials;
    }

    /**
     * Send a request to the CFA service
     *
     * @param string $url The endpoint URL (will be appended to base URL)
     * @param string $method HTTP method (GET, POST, PUT, etc.)
     * @param array|null $data Request data
     * @param string|null $namespace Namespace for the request
     * @param string|null $action Action identifier for tracing
     * @return array|null Response data as array, or null on error
     */
    public function sendRequest($url, $merchantId, $method, $data = null, $namespace = null, $action = null)
    {
        try
        {
            $url = $this->baseUrl . $url;

            if ($data === null)
            {
                $data = '';
            }
            else if (is_array($data))
            {
                $data = json_encode($data);
            }

            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
            $headers[self::X_RAZORPAY_TASKID] = $this->request->getTaskId();
            $headers[self::X_RAZORPAY_MERCHANT_ID] = $merchantId;
            
            if ($namespace !== null)
            {
                $headers[self::X_NAMESPACE] = $namespace;
            }
            
            $headers['X-Razorpay-Mode'] = $this->mode;
            
            // Get authentication credentials
            $credentials = $this->getAuthCredentials();
            
            if ($credentials['key'] !== null && $credentials['secret'] !== null)
            {
                $headers['Authorization'] = 'Basic ' . base64_encode($credentials['key'] . ':' . $credentials['secret']);
            }

            if(!empty(Request::header(RequestHeader::DEV_SERVE_USER)))
            {
                $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
            }

            $request = [
                'url'       => $url,
                'method'    => $method,
                'headers'   => $headers,
                'content'   => $data
            ];

            $this->trace->info(TraceCode::TRACE_CFA_SERVICE_REQUEST, [
                'url'       => $request['url'],
                'action'    => $action,
                'content'   => $request['content'],
                'namespace' => $headers[self::X_NAMESPACE] ?? null
            ]);

            $response = $this->sendCFAServiceRequest($request);

            $this->checkErrors($response);
            
            $responseData = json_decode($response->body, true);
            
            // Add trace for response
            $this->trace->info(TraceCode::TRACE_CFA_SERVICE_RESPONSE, [
                'url'           => $request['url'],
                'action'        => $action,
                'status_code'   => $response->status_code,
                'response_body' => $response->body,
                'response_data' => $responseData
            ]);

            return $responseData;
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::TRACE_CFA_SERVICE_ERROR,
                [
                    'error_message' => $e->getMessage(),
                    'url'           => $url ?? '',
                    'action'        => $action ?? '',
                ]
            );

            // Log the error and continue with the flow.
            throw $e;
        }
    }

    protected function getRequestHooks()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOptions']);

        return $hooks;
    }

    public function setCurlOptions($curl)
    {
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    }

    protected function sendCFAServiceRequest($request)
    {
        $method = $request['method'];

        $retryCount = 0;

        while (true)
        {
            try
            {
                switch($method)
                {
                    case Requests::GET:
                        $response = Requests::$method(
                                    $request['url'],
                                    $request['headers'],
                                    ['hooks' => $this->getRequestHooks()]);
                        break;
                    case Requests::PUT:
                    case Requests::POST:
                    case Requests::PATCH:
                        $response = Requests::$method(
                                    $request['url'],
                                    $request['headers'],
                                    $request['content'],
                                    ['hooks' => $this->getRequestHooks()]);
                        break;
                    default:
                        throw new \Exception('Invalid HTTP method: ' . $method);
                }

                return $response;
            }
            catch (\Throwable $e)
            {
                $retryCount++;

                if ($retryCount > self::MAX_RETRY_COUNT)
                {
                    throw $e;
                }

                $this->trace->info(
                    TraceCode::TRACE_CFA_SERVICE_RETRY,
                    [
                        'retry_count'   => $retryCount,
                        'error_message' => $e->getMessage(),
                    ]
                );

                // Retry after a short delay
                usleep(100000); // 100ms
            }
        }
    }

    protected function checkErrors($response)
    {
        if ($response->status_code >= 400)
        {
            $responseBody = json_decode($response->body, true);
            
            if (isset($responseBody['details'][0]['@type']) && $responseBody['details'][0]['@type'] === 'type.googleapis.com/rzp.common.error.v1.Error') {
                $errorMessage = $responseBody['details'][0]['description'] ?? 'Unknown error';
                $errorCode = $responseBody['details'][0]['code'] ?? 'unknown_error';
            } else {
                $errorMessage = $responseBody['error']['description'] ?? 'Unknown error';
                $errorCode = $responseBody['error']['code'] ?? 'unknown_error';
            }
            
            $this->trace->error(
                TraceCode::TRACE_CFA_SERVICE_API_ERROR,
                [
                    'status_code'   => $response->status_code,
                    'error_message' => $errorMessage,
                    'error_code'    => $errorCode,
                    'response_body' => $response->body,
                ]
            );
            
            if ($response->status_code == 400) {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_QUERY,
                null,
                []);
            } else {
                throw new \Exception("CFA Service error: {$errorMessage} (Code: {$errorCode})");
            }
        }
    }

    // Public Functions for CFA Actions
    /**
     * Get contact details from CFA service
     *
     * @param string $contactId The contact ID to fetch
     * @param string $merchantId The merchant ID
     * @return ContactEntity Contact Entity
     */
    public function getContact(string $contactId, MerchantEntity $merchant)
    {
        try {
            $cfaResponse = $this->sendRequest(
                '/v1/contacts/' . $this->trimPrefixForEntity($contactId, 'cont_'),
                $merchant->getId(),
                Requests::GET,
                null,
                null,
                'get_contact'
            );
    
            if($cfaResponse === null) {
                $data = [
                    'attributes' => $contactId,
                    'operation' => 'find'
                ];
    
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
            }
    
            return $this->convertCFAResponseToContactEntity($cfaResponse, $merchant);
        } catch (\Throwable $ex) {
            return null;
        }
    }

    public function getFundAccount(string $fundAccountId, string $merchantId)
    {
        try {
            $cfaResponse = $this->sendRequest(
                '/v1/fund_accounts/' . $this->trimPrefixForEntity($fundAccountId, 'fa_'),
                $merchantId,
                Requests::GET,
                null,
                null,
                'get_fund_account'
            );
    
            if ($cfaResponse['id']) {
                $cfaResponse['id'] = 'fa_' . $cfaResponse['id'];
            }
    
            if ($cfaResponse['contact_id']) {
                $cfaResponse['contact_id'] = 'cont_' . $cfaResponse['contact_id'];
            }
    
            return $cfaResponse;
        } catch (\Throwable $ex) {
            return null;
        }
    }

    public function createContact(array $input, MerchantEntity $merchant)
    {
        $cfaResponse = $this->sendRequest(
            '/v1/contacts',
            $merchant->getId(),
            Requests::POST,
            $input,
            null,
            'create_contact'
        );

        if($cfaResponse === null) {
            return null;
        }

        return $this->convertCFAResponseToContactEntity($cfaResponse, $merchant);
    }

    public function createFundAccount(array $input, MerchantEntity $merchant)
    {
        $cfaResponse = $this->sendRequest(
            '/v1/fund_accounts',
            $merchant->getId(),
            Requests::POST,
            $input,
            null,
            'create_fund_account'
        );

        if($cfaResponse === null) {
            return null;
        }

        $fundAccount = $this->convertCFAResponseToFundAccountEntity($cfaResponse, $merchant);

        return $fundAccount;
    }

    // Private helper functions
    /**
     * Trim 'cont_' prefix from contact ID if it exists
     *
     * @param string $contactId Contact ID (may or may not have 'cont_' prefix)
     * @return string Contact ID without 'cont_' prefix
     */
    private function trimPrefixForEntity(string $entityId, string $prefix): string
    {
        if (strpos($entityId, $prefix) === 0) {
            return substr($entityId, strlen($prefix));
        }
        
        return $entityId;
    }

    // Public helper Functions

     /**
     * Convert CFA service response array to Contact Entity
     *
     * @param array|null $cfaResponse Response from CFA service
     * @param string $merchantId Merchant ID
     * @return ContactEntity Contact entity
     */
    public function convertCFAResponseToContactEntity($cfaResponse, MerchantEntity $merchant): ContactEntity
    {
        // Extract only the fields that build() allows (user-editable fields)
        $allowedFields = [
            'name',
            'contact', 
            'email',
            'type',
            'reference_id',
            'notes',
            'gstin'
        ];
    
        $buildData = array_intersect_key($cfaResponse, array_flip($allowedFields));
    
        // Create entity using build() with filtered data
        $contact = (new ContactEntity)->build($buildData);
    
        // Now manually set the system fields we need to preserve
        $contact->setId($cfaResponse['id']);
    
        if (isset($cfaResponse['active'])) {
            $contact->setAttribute(ContactEntity::ACTIVE, $cfaResponse['active']);
        }
    
        if (isset($cfaResponse['created_at'])) {
            $contact->setAttribute(ContactEntity::CREATED_AT, $cfaResponse['created_at']);
        }
    
        // Associate with merchant
        $contact->merchant()->associate($merchant);
    
        return $contact;
    }

    public function convertCFAResponseToFundAccountEntity($cfaResponse, $merchant): FundAccountEntity
    {
        try {
            // Extract only the fields that build() allows for FundAccount (user-editable fields)
            $allowedFundAccountFields = [
                'idempotency_key',
                'linked_number',
                'customer_name',
                'bank_ifsc'
            ];

            $buildData = array_intersect_key($cfaResponse, array_flip($allowedFundAccountFields));
            
            // Add account_type which is required
            if (isset($cfaResponse['account_type'])) {
                $buildData['account_type'] = $cfaResponse['account_type'];
                
                // Add the account details field that matches the account_type
                // This is required by the Fund Account validator
                if (isset($cfaResponse[$cfaResponse['account_type']])) {
                    $buildData[$cfaResponse['account_type']] = $cfaResponse[$cfaResponse['account_type']];
                }
            }

            // Create fund account entity using build() with filtered data
            $fundAccount = (new FundAccountEntity)->build($buildData);

            // Associate with merchant
            $fundAccount->merchant()->associate($merchant);

            // Now manually set the system fields we need to preserve
            if (str_starts_with($cfaResponse['id'], 'fa_'))
            {
                $cfaResponse['id'] = substr($cfaResponse['id'], 3); // Remove 'fa_' prefix
            }
            $fundAccount->setId($cfaResponse['id']);

            if (isset($cfaResponse['created_at'])) {
                $fundAccount->setAttribute(FundAccountEntity::CREATED_AT, $cfaResponse['created_at']);
            }

            if (isset($cfaResponse['active'])) {
                $fundAccount->setAttribute(FundAccountEntity::ACTIVE, $cfaResponse['active']);
            }

            // Create and associate the account based on account_type
            if (isset($cfaResponse['account_type']) && isset($cfaResponse[$cfaResponse['account_type']])) {
                $accountType = $cfaResponse['account_type'];
                $accountData = $cfaResponse[$accountType];
                
                $account = $this->createAccountFromCFAResponse($accountType, $accountData, $merchant);
                
                if ($account !== null) {
                    $fundAccount->account()->associate($account);
                }
            }

            // Set source if contact_id is present
            if (isset($cfaResponse['contact_id'])) {
                // For now, we'll set the source_type and source_id manually
                // In a real scenario, you might want to fetch the actual contact entity
                $fundAccount->setAttribute(FundAccountEntity::SOURCE_TYPE, 'contact');
                if (str_starts_with($cfaResponse['contact_id'], 'cont_'))
                {
                    $cfaResponse['contact_id'] = substr($cfaResponse['contact_id'], 5); // Remove 'cont_' prefix
                }
                $fundAccount->setAttribute(FundAccountEntity::SOURCE_ID, $cfaResponse['contact_id']);

                //Call CFA to get the contact entity
                $contact = $this->getContact($cfaResponse['contact_id'], $merchant);
                $fundAccount->source()->associate($contact);
            }

            $fundAccount->wasRecentlyCreated = $cfaResponse['is_created'];

            return $fundAccount;
        } catch (\Throwable $ex) {
            return null;
        }
    }

    /**
     * Check if CFA service is enabled for the given input
     * 
     * @param array $input Input data
     * @param bool $isCFAExperimentEnabled Whether CFA experiment is enabled
     * @return bool True if CFA service is enabled, false otherwise
     */
    public function isCfaServiceEnabled(array $input, $isCFAExperimentEnabled): bool {
        return $isCFAExperimentEnabled && 
               isset($input['vendor_id']) === false && 
               in_array($input['account_type'], self::ENABLED_ACCOUNT_TYPES, true) && 
               isset($input[FundAccountEntity::CONTACT_ID]) === true;
    }

    /**
     * Create account entity from CFA response based on account type
     */
    private function createAccountFromCFAResponse(string $accountType, array $accountData, $merchant)
    {
        try {
            switch ($accountType) {
                case Type::BANK_ACCOUNT:
                    return $this->createBankAccountFromCFAResponse($accountData, $merchant);
                    
                case Type::VPA:
                    return $this->createVpaFromCFAResponse($accountData, $merchant);
                    
                case Type::WALLET_ACCOUNT:
                    return $this->createWalletAccountFromCFAResponse($accountData, $merchant);
                    
                default:
                    $this->trace->warning(TraceCode::TRACE_CFA_SERVICE_UNSUPPORTED_ACCOUNT_TYPE, [
                        'account_type' => $accountType
                    ]);
                    return null;
            }
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::TRACE_CFA_SERVICE_ACCOUNT_CREATION_ERROR, [
                'account_type' => $accountType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create bank account entity from CFA response
     */
    private function createBankAccountFromCFAResponse(array $accountData, $merchant)
    {   
        // Use build method to create bank account without saving
        $bankAccount = new \RZP\Models\BankAccount\Entity();
        $bankAccount->merchant()->associate($merchant);
        
        // Extract allowed fields for bank account build
        $allowedBankAccountFields = [
            'account_number',
            'ifsc',
            'name'
        ];
        
        $buildData = array_intersect_key($accountData, array_flip($allowedBankAccountFields));
        $bankAccount = $bankAccount->build($buildData, 'add_fund_account_bank_account');

        if ($accountData['id'] != null) {
            $bankAccount->setId($accountData['id']);
        }
        
        return $bankAccount;
    }

    /**
     * Create VPA entity from CFA response
     */
    private function createVpaFromCFAResponse(array $accountData, $merchant)
    {
        $vpa = new \RZP\Models\Vpa\Entity();
        $vpa->merchant()->associate($merchant);
        
        // Extract allowed fields for VPA build
        $allowedVpaFields = [
            'address'
        ];
        
        $buildData = array_intersect_key($accountData, array_flip($allowedVpaFields));
        $vpa = $vpa->build($buildData);

        if ($accountData['id'] != null) {
            $vpa->setId($accountData['id']);
        }
        
        return $vpa;
    }

    /**
     * Create Wallet Account entity from CFA response
     */
    private function createWalletAccountFromCFAResponse(array $accountData, $merchant)
    {
        $walletAccount = new \RZP\Models\WalletAccount\Entity();
        $walletAccount->merchant()->associate($merchant);
        
        // Extract allowed fields for Wallet Account build
        $allowedWalletFields = [
            'phone',
            'provider',
            'name',
            'email'
        ];
        
        $buildData = array_intersect_key($accountData, array_flip($allowedWalletFields));
        $walletAccount = $walletAccount->build($buildData);

        if ($accountData['id'] != null) {
            $walletAccount->setId($accountData['id']);
        }
        
        return $walletAccount;
    }
}

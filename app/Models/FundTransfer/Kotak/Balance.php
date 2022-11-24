<?php

namespace RZP\Models\FundTransfer\Kotak;

use App;
use Config;
use RZP\Http\Request\Requests;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\UniqueIdEntity;

class Balance
{
    protected $app;

    protected $mode;

    protected $trace;

    protected $config;

    protected $referenceNumber;

    /**
     * Holds Account numbers for which balance has to be fetched
     *
     * @var array
     */
    protected $accountNumbers;

    /**
     * Success code is checked against the nodal account balance response.
     */
    const SUCCESS_CODE      = '00';

    /**
     * Associative array of error code and its abstract reason
     */
    const ERROR_CODE_MAP    = [
        '01'        => 'Business Failure',
        'T-001'     => 'Unauthorized request',
        'T-002'     => 'Technical error while processing',
        'T-003'     => 'Connectivity errors'
    ];

    const DEFAULT_ERROR_TYPE    = 'UNKNOWN';

    const DEFAULT_ERROR_MESSAGE = 'UNKNOWN';

    /**
     * XML namespace for the balance API
     */
    const XML_NAMESPACE     = 'http://www.kotak.com/schemas/CMSBalanceEnquiry/CMSBalanceEnquiry.xsd';

    /**
     * Class whose object will be returned by simplexml_load_string method
     */
    const XML_CLASS         = 'SimpleXMLElement';

    const ACCOUNT_NUMBER    = 'AccountNoValue';

    const ACCOUNT_BALANCE   = 'ClearBalanceValue';

    const BALANCE_LIST      = 'ClearBalanceList';

    const BALANCE           = 'ClearBalance';

    const RESPONSE_CODE     = 'ResCode';

    const RESPONSE_MESSAGE  = 'ResMsg';

    const REFERENCE_NO      = 'RefNo';

    public function __construct()
    {
        $this->app      = App::getFacadeRoot();

        $this->mode     = $this->app['rzp.mode'];

        $this->trace    = $this->app['trace'];

        $this->config   = Config::get('nodal.kotak');
    }

    /**
     * gets Account balance for all kodak nodal accounts
     *
     * @return array
     * [
     *  account_number => account_balance,
     * ]
     *
     * * @throws Exception\RuntimeException
     */
    public function getAccountBalance(): array
    {
        if ($this->mode === Mode::TEST)
        {
            return [
                986825162 => 1231222.25
            ];
        }

        try
        {
            $this->accountNumbers = (array) $this->config['account_number'];

            $this->referenceNumber = UniqueIdEntity::generateUniqueId();

            $request = $this->createBalanceRequestArray();

            return $this->getBalanceResponse($request);
        }
        catch (\Exception $e)
        {
            throw new Exception\RuntimeException($e->getMessage(), [], $e);
        }
    }

    protected function createBalanceRequestArray(): array
    {
        $requestBody    =   $this->getBalanceRequestBody();

        $request = [
            'body'      => $requestBody,
            'headers'   => $this->getBalanceRequestHeader(),
            'options'   => $this->getBalanceRequestOptions(),
            'method'    => 'POST'
        ];

        $this->trace->info(
            TraceCode::KOTAK_NODAL_BALANCE_REQUEST,
            [
                'request_body' => $requestBody
            ]);

        return $request;
    }

    protected function getBalanceRequestOptions(): array
    {
        return [
            'auth' => $this->getCredentials()
        ];
    }

    protected function getCredentials(): array
    {
        return [
            $this->config['username'],
            $this->config['password'],
        ];
    }

    protected function getBalanceRequestHeader(): array
    {
        return [
            'Content-Type' => 'application/xml'
        ];
    }

    protected function getBalanceRequestBody(): string
    {
        $srcAppCd = $this->config['src_app_cd'];

        $crn = $this->config['crn'];

        return '<?xml version="1.0" encoding="utf-8"?>'
               . '<Request xmlns="' . self::XML_NAMESPACE . '">'

               . '<SrcAppCd>'
               . $srcAppCd
               . '</SrcAppCd>'

               . '<RefNo>'
               . $this->referenceNumber
               . '</RefNo>'

               . '<UserCRN>'
               . $crn
               . '</UserCRN>'

               . '<AccountCRN>'
               . $crn
               . '</AccountCRN>'

               . '<AccountNumberList>'
               . $this->getAccountList()
               . '</AccountNumberList>'

               . '</Request>';
    }

    protected function getAccountList(): string
    {
        $accountList = '';

        foreach($this->accountNumbers as $accountNumber)
        {
            $accountList .= '<AccountNo>'
                            . '<AccountNoValue>'
                            . $accountNumber
                            . '</AccountNoValue>'
                            . '</AccountNo>';
        }

        return $accountList;
    }

    protected function getBalanceResponse(array $request): array
    {
        $response = $this->sendRequest($request);

        $data     = $this->convertXmlResponseToArray($response);

        $this->validateBalanceResponse($response, $data);

        return $this->formatBalanceResponse($data);
    }

    protected function sendRequest(array $request)
    {
        $url = $this->config['url'];

        $response =  Requests::request(
                        $url,
                        $request['headers'],
                        $request['body'],
                        $request['method'],
                        $request['options']);

        $this->trace->info(
            TraceCode::KOTAK_NODAL_BALANCE_RESPONSE,
            [
                'response' => $response,
            ]);

        return $response;
    }

    /**
     * Validates the response of balance API
     * Here 2 things will be checked
     *  1. Response status code should be 200
     *  2. RefNo in the response should match the RefNo sent in Request
     *  3. Check for any technical errors
     *  4. Account detail response code has to be 00
     */
    protected function validateBalanceResponse(
            \WpOrg\Requests\Response $response,
            array $body)
    {
        if ($response->status_code !== 200)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_INVALID_RESPONSE);
        }

        $this->hasValidResponse($body);

        $this->hasValidAccountDetails($body);
    }

    /**
     * Validates if the received reference number is same as the requested one
     */
    protected function hasValidResponse(array $body)
    {
        $actualReferenceNumber = $body[self::REFERENCE_NO];

        if ((isset($actualReferenceNumber) === false) or
            ($actualReferenceNumber !== $this->referenceNumber))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_INVALID_RESPONSE,
                null,
                null,
                [
                    'reason'    => 'Invalid Reference Number',
                    'expected'  => $this->referenceNumber,
                    'found'     => $actualReferenceNumber
                ]);
        }
        else if (isset($body['TechnicalError']) === true)
        {
            $technicalError  = $body['TechnicalError'];

            $responseCode    = isset($technicalError[self::RESPONSE_CODE]) ?
                $technicalError[self::RESPONSE_CODE] : self::DEFAULT_ERROR_TYPE;

            $responseMessage = isset($technicalError[self::RESPONSE_MESSAGE]) ?
                $technicalError[self::RESPONSE_MESSAGE] : self::DEFAULT_ERROR_MESSAGE;

            throw new Exception\GatewayErrorException(
                ErrorCode::SERVER_ERROR_INVALID_RESPONSE,
                null,
                null,
                [
                    'code'      => $responseCode,
                    'message'   => $responseMessage
                ]);
        }
    }

    /**
     * Validates each account details data
     * For a successful response every accounts response code should match with SUCCESS_CODE
     */
    protected function hasValidAccountDetails(array $body)
    {
        $accounts = $this->getBalanceSectionFromResponse($body);

        foreach ($accounts as $account)
        {
            $responseCode = $account[self::RESPONSE_CODE];

            if ($responseCode !== self::SUCCESS_CODE)
            {
                throw new Exception\RuntimeException(
                        'Failed to get Balance detail for one or more accounts.',
                    [
                        'type'       => self::ERROR_CODE_MAP[$responseCode] ?? self::DEFAULT_ERROR_TYPE,
                        'res_code'   => $responseCode,
                        'reason'     => $account[self::RESPONSE_MESSAGE],
                        'account_no' => $account[SELF::ACCOUNT_NUMBER]
                    ]);
            }
        }
    }

    /**
     * Converts the XML response to array format for the further processing
     */
    protected function convertXmlResponseToArray($response): array
    {
        $xmlObj = simplexml_load_string(
                $response->body,
                self::XML_CLASS,
                0,
                self::XML_NAMESPACE);

        return json_decode(
                json_encode($xmlObj),
                true);
    }

    /**
     * It will remove all the data which are not required for the further process
     * And only send Account number and Account balance as key value pair respectively
     *
     * @return array
     * [
     *  account_number => account_balance,
     * ]
     */
    protected function formatBalanceResponse(array $balanceDetails): array
    {
        $result = [];

        $accounts = $this->getBalanceSectionFromResponse($balanceDetails);

        foreach($accounts as $account)
        {
            list($_, $amount) = explode(' ', $account[self::ACCOUNT_BALANCE]);

            $result[$account[self::ACCOUNT_NUMBER]] = (float) trim($amount);
        }

        return $result;
    }

    /**
     * This method will always ensure that response is array of account details
     *
     * The response changes based on number of account numbers passed in request.
     *  - For only one account `ClearBalance` will have the account details
     *  - For Multiple accounts `ClearBalance` will be array of account details
    */
    protected function getBalanceSectionFromResponse(array $balanceDetails): array
    {
        $balances = $balanceDetails[self::BALANCE_LIST];

        return is_associative_array($balances[self::BALANCE]) === false ?
                $balances[self::BALANCE] : $balances;
    }
}

<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use GuzzleHttp\Client;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\PaperMandate;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Models\PaperMandate\PaperMandateUpload;

class HyperVerge
{
    protected $config;

    protected $app;

    protected $baseUrl;

    protected $appId;

    protected $appKey;

    protected $client;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    const REQUEST_TIMEOUT = 10;

    const GENERATE_NACH = 'populateNACH';
    const EXTRACT_NACH  = 'extractNach';

    const RESULT = 'result';
    const DETAILS = 'details';

    const URLS = [
        self::GENERATE_NACH => 'populateNACH',
        self::EXTRACT_NACH  => 'readNACH',
    ];

    const UMRN                        = 'UMRN';
    const NACH_DATE                   = 'nachDate';
    const SPONSOR_CODE                = 'sponsorCode';
    const UTILITY_CODE                = 'utilityCode';
    const BANK_NAME                   = 'bankName';
    const ACCOUNT_TYPE                = 'accountType';
    const ACCOUNT_NUMBER              = 'accountNumber';
    const IFSCCode                    = 'IFSCCode';
    const MICR                        = 'MICR';
    const COMPANY_NAME                = 'companyName';
    const FREQUENCY                   = 'frequency';
    const AMOUNT_IN_NUMBER            = 'amountInNumber';
    const AMOUNT_IN_WORDS             = 'amountInWords';
    const DEBIT_TYPE                  = 'debitType';
    const START_DATE                  = 'startDate';
    const END_DATE                    = 'endDate';
    const UNTIL_CANCELLED             = 'untilCanceled';
    const NACH_TYPE                   = 'NACHType';
    const PHONE_NUMBER                = 'phoneNumber';
    const EMAIL_ID                    = 'emailId';
    const REFERENCE_1                 = 'reference1';
    const REFERENCE_2                 = 'reference2';
    const PRIMARY_ACCOUNT_HOLDER      = 'primaryAccountHolder';
    const SECONDARY_ACCOUNT_HOLDER    = 'secondaryAccountHolder';
    const TERTIARY_ACCOUNT_HOLDER     = 'tertiaryAccountHolder';
    const SIGNATURE_PRESENT_PRIMARY   = 'signaturePresentPrimary';
    const SIGNATURE_PRESENT_SECONDARY = 'signaturePresentSecondary';
    const SIGNATURE_PRESENT_TERTIARY  = 'signaturePresentTertiary';
    const ENHANCED_IMAGE              = 'base64AlignedJPEG';
    const VALUE                       = 'value';
    const FORM_CHECKSUM               = 'uid';
    const SB                          = 'SB';
    const CA                          = 'CA';

    // error codes
    const MISSING_OR_INVALID_CREDENTIALS = 'Missing/Invalid credentials';
    const INTERNAL_SERVER_ERROR          = 'Internal server error';
    const IMAGE_MISSING                  = 'Image input is missing';
    const IMAGE_FORMAT_INVALID           = 'Image not one of the supported types (jpg/tiff/png)';
    const MARKERS_NOT_DETECTED           = 'Reclick image - Unable to detect the markers';
    const FORM_NOT_DETECTED              = 'No NACH detected';
    const MARKERS_NOT_FOUND              = 'Markers not found';

    protected $errorCodes = [
        self::MISSING_OR_INVALID_CREDENTIALS,
        self::INTERNAL_SERVER_ERROR,
        self::IMAGE_MISSING,
        self::IMAGE_FORMAT_INVALID,
        self::MARKERS_NOT_DETECTED,
        self::FORM_NOT_DETECTED,
        self::MARKERS_NOT_FOUND,
    ];

    protected $errorCodes4XX = [
        self::FORM_NOT_DETECTED,
        self::MARKERS_NOT_DETECTED,
        self::MARKERS_NOT_FOUND,
    ];

    public function __construct($app, $client = null)
    {
        $this->app = $app;

        $this->config = $this->app['config']->get('applications.hyper_verge');
        $this->trace  = $this->app['trace'];

        $this->baseUrl = $this->config['url'];
        $this->appId   = $this->config['app_id'];
        $this->appKey  = $this->config['app_key'];

        if ($client === null)
        {
            $this->client = new Client([
                'base_uri' => $this->baseUrl,
                'connect_timeout' => self::REQUEST_TIMEOUT
            ]);
        }
        else {
            $this->client = $client;
        }
    }

    public function generateNACH(array $input, PaperMandate\Entity $paperMandate)
    {
        $logInput = $input;

        unset($logInput[self::ACCOUNT_NUMBER]);

        $this->trace->info(
            TraceCode::PAPER_MANDATE_CREATE_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'input'            => $logInput
            ]);

        $headers = $this->getHeaders($paperMandate);

        $headers['Content-Type'] = 'application/json';

        $timeStarted = microtime(true);

        try
        {
            $response = $this->client->request(
                Requests::POST,
                self::URLS[self::GENERATE_NACH],
                [
                    'body'    => json_encode($input, JSON_UNESCAPED_SLASHES),
                    'headers' => $headers,
                ]
            );
        }
        catch (\Exception $e)
        {
            throw new ServerErrorException(
                'HyperVerge error',
                ErrorCode::SERVER_ERROR_UNABLE_TO_CREATE_NACH_FORM,
                [
                    'input'            => $input,
                    'paper_mandate_id' => $paperMandate->getId(),
                ],
                $e
            );
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::TIME_TAKEN_BY_HYPERVERGE_TO_GENERATE_FORM,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'time_taken'       => $timeTaken,
            ]);

        $data = $this->readHypervergeResponse($response);

        return $data[self::RESULT];
    }

    protected function readHypervergeResponse($response) {
        return json_decode($response->getBody()->getContents(), true);
    }

    public function extractNACHWithOutputImage(array $input, PaperMandate\Entity $paperMandate)
    {
        $this->trace->info(
            TraceCode::PAPER_MANDATE_EXTRACT_FORM_REQUEST_TO_HYPERVERGE,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
            ]);

        $headers = $this->getHeaders($paperMandate);

        $response = null;

        $timeStarted = microtime(true);

        try
        {
            $response = $this->client->request(Requests::POST, self::URLS[self::EXTRACT_NACH], [
                'multipart' => [
                    [
                        'name'     => 'image',
                        'contents' => fopen($input[PaperMandate\Entity::FORM_UPLOADED], 'r')
                    ],
                    [
                        'name'     => 'enableOutputJPEG',
                        'contents' => 'yes'
                    ],
                    [
                        'name'     => PaperMandate\HyperVerge::DETECT_FORM_CHECKSUM,
                        'contents' => 'yes'
                    ]
                ],
                'headers'   => $headers,
            ]);
        }
        catch (\Exception $e)
        {
            $errorMessage = $this->getErrorMessage($e);

            if ($this->is4xxException($e, $errorMessage) === true)
            {
                $this->trace->traceException($e);

                throw (new BadRequestException(
                    ErrorCode::BAD_REQUEST_UNABLE_TO_READ_NACH_FORM,
                    null,
                    [
                        'input'            => $input,
                        'paper_mandate_id' => $paperMandate->getId()
                    ],
                    $errorMessage
                ));
            }
            else
            {
                throw new ServerErrorException(
                    'HyperVerge error',
                    ErrorCode::SERVER_ERROR_NACH_EXTRACTION_FAILED,
                    [
                        'input'            => $input,
                        'paper_mandate_id' => $paperMandate->getId(),
                    ],
                    $e
                );
            }
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->info(
            TraceCode::TIME_TAKEN_BY_HYPERVERGE_TO_EXTRACT_FORM,
            [
                'paper_mandate_id' => $paperMandate->getPublicId(),
                'time_taken'       => $timeTaken,
            ]);

        $data = $this->readHypervergeResponse($response);

        return $this->mapExtractedData($data[self::RESULT][self::DETAILS]);
    }

    private function is4xxException(\Exception $e, $errorMessage): bool
    {
        $errorCode = $e->getCode();

        if (($errorCode < 400) or
            ($errorCode >= 500))
        {
            return false;
        }

        // check if any of the hyperverge error message is of these category
        // if not return false
        $matches = array ();

        foreach ($this->errorCodes4XX as $str)
        {
            if (strpos($errorMessage, $str) !== false)
            {
                $matches[] = $errorMessage;
                break;
            }
        }

        if (empty($matches) === true)
        {
            return false;
        }

        return true;
    }

    private function getErrorMessage(\Exception $e)
    {
        $responseBody = $e->getResponse()->getBody()->getContents();

        $response = json_decode($responseBody, true);

        return $response['error'];
    }

    private function getHeaders(PaperMandate\Entity $paperMandate)
    {
        return [
            'transactionId' => $paperMandate->getPublicId(),
            'appKey'        => $this->appKey,
            'appId'         => $this->appId,
        ];
    }

    protected function mapExtractedData(array $data): array
    {
        $extractedRawData = $data;

        unset($extractedRawData[self::ENHANCED_IMAGE]);

        $extractedRawData = json_encode($extractedRawData);

        return [
            PaperMandateUpload\Entity::EMAIL_ID                    => $data[self::EMAIL_ID][self::VALUE] ?? '',
            PaperMandateUpload\Entity::AMOUNT_IN_WORDS             => $data[self::AMOUNT_IN_WORDS][self::VALUE] ?? '',
            PaperMandateUpload\Entity::UTILITY_CODE                => $data[self::UTILITY_CODE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::REFERENCE_1                 => $data[self::REFERENCE_1][self::VALUE] ?? '',
            PaperMandateUpload\Entity::BANK_NAME                   => $data[self::BANK_NAME][self::VALUE] ?? '',
            PaperMandateUpload\Entity::DEBIT_TYPE                  => $this->getFormattedDebitType($data[self::DEBIT_TYPE][self::VALUE] ?? ''),
            PaperMandateUpload\Entity::MICR                        => $data[self::MICR][self::VALUE] ?? '',
            PaperMandateUpload\Entity::FREQUENCY                   => $this->getFormattedFrequency($data[self::FREQUENCY][self::VALUE] ?? ''),
            PaperMandateUpload\Entity::SIGNATURE_PRESENT_TERTIARY  => $data[self::SIGNATURE_PRESENT_TERTIARY][self::VALUE] ?? '',
            PaperMandateUpload\Entity::UNTIL_CANCELLED             => $data[self::UNTIL_CANCELLED][self::VALUE] ?? '',
            PaperMandateUpload\Entity::SIGNATURE_PRESENT_SECONDARY => $data[self::SIGNATURE_PRESENT_SECONDARY][self::VALUE] ?? '',
            PaperMandateUpload\Entity::NACH_TYPE                   => $data[self::NACH_TYPE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::ACCOUNT_NUMBER              => $data[self::ACCOUNT_NUMBER][self::VALUE] ?? '',
            PaperMandateUpload\Entity::NACH_DATE                   => $data[self::NACH_DATE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::PHONE_NUMBER                => $data[self::PHONE_NUMBER][self::VALUE] ?? '',
            PaperMandateUpload\Entity::TERTIARY_ACCOUNT_HOLDER     => $data[self::TERTIARY_ACCOUNT_HOLDER][self::VALUE] ?? '',
            PaperMandateUpload\Entity::UMRN                        => $data[self::UMRN][self::VALUE] ?? '',
            PaperMandateUpload\Entity::COMPANY_NAME                => $data[self::COMPANY_NAME][self::VALUE] ?? '',
            PaperMandateUpload\Entity::IFSC_CODE                   => $data[self::IFSCCode][self::VALUE] ?? '',
            PaperMandateUpload\Entity::REFERENCE_2                 => $data[self::REFERENCE_2][self::VALUE] ?? '',
            PaperMandateUpload\Entity::ACCOUNT_TYPE                => $this->getFormattedAccountType($data[self::ACCOUNT_TYPE][self::VALUE] ?? ''),
            PaperMandateUpload\Entity::AMOUNT_IN_NUMBER            => $this->getFormattedAmountFromExtracted($data[self::AMOUNT_IN_NUMBER][self::VALUE]) ?? '',
            PaperMandateUpload\Entity::END_DATE                    => $data[self::END_DATE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::SPONSOR_CODE                => $data[self::SPONSOR_CODE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::SIGNATURE_PRESENT_PRIMARY   => $data[self::SIGNATURE_PRESENT_PRIMARY][self::VALUE] ?? '',
            PaperMandateUpload\Entity::SECONDARY_ACCOUNT_HOLDER    => $data[self::SECONDARY_ACCOUNT_HOLDER][self::VALUE] ?? '',
            PaperMandateUpload\Entity::START_DATE                  => $data[self::START_DATE][self::VALUE] ?? '',
            PaperMandateUpload\Entity::PRIMARY_ACCOUNT_HOLDER      => $data[self::PRIMARY_ACCOUNT_HOLDER][self::VALUE] ?? '',
            PaperMandateUpload\Entity::FORM_CHECKSUM               => $data[self::FORM_CHECKSUM][self::VALUE] ?? '',
            PaperMandateUpload\Entity::ENHANCED_IMAGE              => $data[self::ENHANCED_IMAGE] ?? '',
            PaperMandateUpload\Entity::EXTRACTED_RAW_DATA          => $extractedRawData,
        ];
    }

    private function getFormattedAmountFromExtracted($amount = 0): int
    {
        return $amount * 100;
    }

    private function getFormattedAccountType($accountType): string
    {
        switch ($accountType)
        {
            case self::SB:
                return BankAccount\AccountType::SAVINGS;
            case self::CA:
                return BankAccount\AccountType::CURRENT;
            default:
                return '';
        }
    }

    private function getFormattedFrequency($frequency): string
    {
        switch ($frequency)
        {
            case 'whenPresented':
                return PaperMandate\Frequency::AS_AND_WHEN_PRESENTED;
            case 'yearly':
                return PaperMandate\Frequency::YEARLY;
            default:
                return '';
        }
    }

    protected function getFormattedDebitType(string $debitType): string
    {
        switch ($debitType)
        {
            case 'fixedAmount':
                return PaperMandate\DebitType::FIXED_AMOUNT;
            case 'maximumAmount':
                return PaperMandate\DebitType::MAXIMUM_AMOUNT;
            default:
                return '';
        }
    }
}

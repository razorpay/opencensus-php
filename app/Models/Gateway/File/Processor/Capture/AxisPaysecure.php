<?php

namespace RZP\Models\Gateway\File\Processor\Capture;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Mail\Emi as EmiMail;
use RZP\Constants\Environment;
use RZP\Services\Beam\Service;
use RZP\Models\Merchant\Detail;
use RZP\Exception\LogicException;
use RZP\Models\Gateway\File\Type;
use RZP\Models\Currency\Currency;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Gateway\File\Constants as GatewayFileConstants;
use RZP\Services\Scrooge;
use RZP\Trace\TraceCode;
use RZP\Mail\Gateway\CaptureFile\Base as CaptureMail;

class AxisPaysecure extends Base
{
    const EXTENSION         = FileStore\Format::TXT;
    const FILE_NAME         = 'AXISNPCIPSR';
    const FILE_TYPE         = FileStore\Type::AXIS_PAYSECURE;
    const BEAM_FILE_TYPE    = 'capture';

    const CPS_BULK_LIMIT    = 500;

    const TEST_ENCRYPTION_KEY = 'T8DIATjuwST8DIATjuwST8DIATjuwS22';

    const TEST_ENCRYPTION_IV = '123456789012';

    const S3_PATH = 'axis_capture/';

    const CPS_AUTHORIZATION_RRN     = 'rrn';

    const CPS_AUTHORIZATION_CODE    = 'auth_code';

    const CPS_PARAMS = [
        self::CPS_AUTHORIZATION_RRN,
        self::CPS_AUTHORIZATION_CODE,
    ];

    /**
     * @var $file FileStore\Entity
     */
    protected $file;

    protected $iv;

    protected $scroogeRefundsData = [];

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::fetchEntities().
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $data = new PublicCollection();

        $data['captured'] = $this->repo->payment->getAxisPaysecureCapturedTransactionsBetween($begin, $end);

        $scroogeRefundsData = $this->fetchScroogeRefundsData($begin, $end);

        $this->scroogeRefundsData = $scroogeRefundsData;

        $this->scroogeRefundPaymentIds = array_unique(array_column($this->scroogeRefundsData, RefundConstants::PAYMENT_ID));

        $shouldFetchPayments = true;

        $start = 0;

        $payments = new PublicCollection();

        while ($shouldFetchPayments === true)
        {
            $paymentIds = array_slice($this->scroogeRefundPaymentIds, $start, GatewayFileConstants::QUERY_LIMIT);

            $fetchedPayments = $this->repo->payment->fetchPaymentsGivenIds($paymentIds, GatewayFileConstants::QUERY_LIMIT);

            $payments = $payments->merge($fetchedPayments);

            if (count($fetchedPayments) < GatewayFileConstants::QUERY_LIMIT)
            {
                $shouldFetchPayments = false;
            }

            $start += GatewayFileConstants::QUERY_LIMIT;
        }

        $data['refunded'] = $payments;

        return $data;
    }

    public function generateData(PublicCollection $payments): array
    {
        $data['captured'] = $payments['captured']->all();

        $data['refunded'] = $payments['refunded']->all();

        $data['password'] = $this->generateCaptureFilePassword();

        $captured = [];

        $refunded = [];

        $capturedPaymentIds = array_map(function ($payment) {
            return $payment['id'];
        }, $data['captured']);

        $refundPaymentIds = array_map(function ($payment) {
            return $payment['id'];
        }, $data['refunded']);

        /**
         * @var $record Payment\Entity
         */
        foreach ($data['captured'] as $record)
        {
            if ($record->getBaseAmount() === $record->getBaseAmountRefunded() and
                in_array($record->getId(), $refundPaymentIds) === true)
            {
                continue;
            }
            $captured[] = $record;
        }

        /**
         * @var $record Payment\Entity
         */
        foreach ($data['refunded'] as $record)
        {
            if ($record->getBaseAmount() === $record->getBaseAmountRefunded() and
                in_array($record->getId(), $capturedPaymentIds) === true)
            {
                continue;
            }
            $refunded[] = $record;
        }

        $data['captured'] = $captured;

        $data['refunded'] = $refunded;

        $capturedPaymentIds = array_map(function ($payment) {
            return $payment['id'];
        }, $data['captured']);

        $refundPaymentIds = array_map(function ($payment) {
            return $payment['id'];
        }, $data['refunded']);

        $paymentIds = array_unique(array_merge($capturedPaymentIds, $refundPaymentIds));

        $data['cps_auth'] = $this->fetchDataFromCps('authorization', $paymentIds);

        $scroogeData = [];

        foreach ($this->scroogeRefundsData as $refund)
        {
            $scroogeData[stringify($refund[RefundConstants::PAYMENT_ID])] = [
                'time' => $refund[RefundConstants::SCROOGE_CREATED_AT],
                'amount' => $refund['amount']
            ];
        }

        $data['scrooge'] = $scroogeData;

        return $data;
    }

    public function generateCaptureFilePassword()
    {
        if ($this->app->environment(Environment::TESTING) === true)
        {
            return self::TEST_ENCRYPTION_KEY;
        }

        return openssl_random_pseudo_bytes(32);
    }

    /**
     * Implements \RZP\Models\Gateway\File\Processor\Base::createFile($data).
     * @param $data
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = self::S3_PATH . $this->getFileToWriteName();

            $metadata = $this->getH2HMetadata();

            $creator = new FileStore\Creator;

            $this->iv = openssl_random_pseudo_bytes(12);

            if ($this->app->environment(Environment::TESTING) === true)
            {
                $this->iv = self::TEST_ENCRYPTION_IV;
            }

            $encryptionParams = [
                Encryption\AesGcmEncryption::SECRET => $data['password'],
                Encryption\AesGcmEncryption::IV     => $this->iv,
            ];

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->encrypt(
                        Encryption\Type::AES_GCM_ENCRYPTION,
                        $encryptionParams
                    )
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->metadata($metadata);

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
            ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'      => $this->gatewayFile->getId(),
                    'message' => $e->getMessage(),
                ],
                $e
            );
        }
    }

    protected function formatDataForFile($data)
    {
        $cpsData = $data['cps_auth'];

        $saleProcessedRecords = $this->formatRecordsForFile($data['captured'],
            $cpsData, 0, false);

        $totalSaleAmount = $saleProcessedRecords['totalSaleAmount'];

        $totalSaleTransactions = $saleProcessedRecords['totalRecords'];

        $refundProcessedRecords = $this->formatRecordsForFile($data['refunded'],
            $cpsData, $totalSaleTransactions, true, $data['scrooge']);

        $totalRefundAmount = $refundProcessedRecords['totalRefundAmount'];

        $totalRefundTransactions = $refundProcessedRecords['totalRecords'];

        $body = array_merge($saleProcessedRecords['body'], $refundProcessedRecords['body']);

        // TODO: get correct header format, file name etc and DATE/TIME
        $header = [
            '0001' .
            'NPCI PAYSECURE Draft File' .
            Carbon::createFromTimestamp($this->gatewayFile->getBegin())
                ->setTimezone(Timezone::IST)->format('ymdHis')
        ];

        //Todo: check the decimal for total sale and refund amounts and DATE/TIME
        $trailer = [
            '0003' .
            Carbon::createFromTimestamp($this->gatewayFile->getEnd())
                ->setTimezone(Timezone::IST)->format('ymdHis') .
            $this->numpadLeft($totalSaleTransactions, 6) .
            $this->numpadLeft($totalSaleAmount, 16) .
            $this->numpadLeft($totalRefundTransactions, 6) .
            $this->numpadLeft($totalRefundAmount, 16)
        ];

        $textRows = array_merge($header, $body, $trailer);

        return implode("\n", $textRows);
    }

    protected function formatRecordsForFile($data, $cpsData, $recordNumber=0, $isRefundedTxn=false, $scroogeData = [])
    {
        $body = [];

        $totalSaleAmount = 0;

        $totalRefundAmount = 0;

        $totalTransactions = 0;

        /**
         * @var $record Payment\Entity
         */
        foreach ($data as $record)
        {
            try
            {
                $partialRefundStatus = ' ';

                if ($isRefundedTxn === true)
                {
                    $partialRefundStatus = 'F';
                    if ($record->isPartiallyRefunded())
                    {
                        $partialRefundStatus = 'P';
                    }
                }

                $recordType = '0002';

                $messageTypeIdentifier = '0120';

                $processingCode = $isRefundedTxn === true ? '203000' : '000000';

                $posTransactionStatus = $isRefundedTxn == true ? '1' : '0';

                $cpsAuthData = $cpsData[$record->getId()];

                $rrn =  $this->numpadLeft($cpsAuthData['rrn'], 12);

                $refundRrn = '            '; //12 places spaces

                if ($isRefundedTxn)
                {
                    $refundRrn = $rrn;
                }

                $authCode = $this->strpadRight($cpsAuthData['auth_code'], 6);

                $authorizedTimestamp = $record->getAuthorizeTimestamp();

                $mcc = $this->numpadLeft($record->merchant->getCategory(), 4);

                $saleAmount = $record->getBaseAmount();

                $refundAmount = $record->getBaseAmountRefunded();

                $txnAmount = $isRefundedTxn === true? $refundAmount : $saleAmount;

                $txnAmtFormatted = $this->numpadLeft($txnAmount, 12);

                $settlementAmt = '000000000000'; //12 zeroes

                $cardHolderBillingAmt = '000000000000'; //12 zeroes

                $pgTxnId = substr($record->getId(), 0, 20);

                $networkReferenceNumber = ' ';

                $tid = $record->terminal->getGatewayTerminalId();

                if ($tid === null)
                {
                    throw new LogicException(
                        'TID can not be null',
                        null,
                        [
                            'gateway'     => 'axis_paysecure',
                            'payment_id'  => $record['id'],
                            'terminal_id' => $record->terminal->getId(),
                        ]);
                }

                $currencyCode = Currency::getIsoCode($record->getCurrency());

                $totalTransactions++;

                $recordNumber++;

                $recordSeq = $this->numpadLeft($recordNumber, 6);

                $cardNumber = $this->strpadRight($this->getCardNumber($record->card), 20);

                $localTxnDate = Carbon::createFromTimestamp($authorizedTimestamp)
                    ->setTimezone(Timezone::IST)->format('md');

                $localTxnTime = Carbon::createFromTimestamp($authorizedTimestamp)
                    ->setTimezone(Timezone::IST)->format('His');

                if ($isRefundedTxn === true)
                {
                    $localTxnDate = Carbon::createFromTimestamp($scroogeData[$pgTxnId]['time'])
                        ->setTimezone(Timezone::IST)->format('md');

                    $localTxnTime = Carbon::createFromTimestamp($scroogeData[$pgTxnId]['time'])
                        ->setTimezone(Timezone::IST)->format('His');
                }

                $body[] =
                    $recordType . // Record type: '0002' for payment, '0003' for refund
                    $messageTypeIdentifier . //Message type identifier '0120' always
                    $cardNumber . // Card number: 20 place, left justified, space filled
                    $processingCode . // Record type: '000000' for payment, '203000' for refund
                    $txnAmtFormatted . //Transaction amount, 12 places
                    $settlementAmt . //Settlement amount: all zeroes, 12 places
                    $cardHolderBillingAmt . //Cardholder billing amount: all zeroes, 12 places
                    '          ' . //Transmission date and time: all spaces, 10 places
                    $recordSeq . // Transaction sequence number: 6 places
                    $localTxnDate . //date, local transaction: MMDD
                    $localTxnTime . //time, local transaction: HHMMSS
                    '    ' . //filler: 4 places
                    '    ' . //Date settlement: all spaces, 4 places
                    $mcc . //Merchant category code: 4 places
                    '111' . //POS Entry mode
                    '59' . //POS condition code
                    $rrn . //Sale retrieval reference number
                    $authCode . //Authorization code
                    '00' . //Response code
                    $this->strpadRight($tid, 8) . //Terminal ID 8 digit
                    $this->strpadRight($tid, 15) . //Terminal ID 15 digit
                    '050' . //E-comm security level indicator
                    ' ' .
                    ' ' .
                    ' ' .
                    $currencyCode .
                    '000' .
                    $currencyCode .
                    '   ' .
                    $partialRefundStatus .
                    '5' .
                    $posTransactionStatus .
                    '6' .
                    $this->strpadRight($networkReferenceNumber, 9) .
                    '000000' .
                    '    ' .
                    $this->strpadRight('', 24) .
                    $this->strpadRight($pgTxnId, 20) .
                    $refundRrn .
                    '   ';

                $rowLength = strlen(end($body));

                if ($rowLength !== 256)
                {
                    throw new LogicException(
                        'Row not formatted properly',
                        null,
                        [
                            'gateway'       => 'axis_paysecure',
                            'length'        => $rowLength,
                            'payment_id'    => $record['id'],
                        ]);
                }

                if($isRefundedTxn)
                {
                    $totalRefundAmount = $totalRefundAmount + $refundAmount;
                }
                else
                {
                    $totalSaleAmount = $totalSaleAmount + $saleAmount;
                }

            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);
                throw $e;
            }
        }

        return [
            'body' => $body,
            'totalSaleAmount' => $totalSaleAmount,
            'totalRefundAmount' => $totalRefundAmount,
            'totalRecords' => $totalTransactions
        ];
    }

    /**
     * @param $data
     * @throws GatewayErrorException
     */
    protected function sendCaptureFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data =  [
            Service::BEAM_PUSH_FILES         => $fileInfo,
            Service::BEAM_PUSH_JOBNAME       => BeamConstants::AXIS_RUPAY_CAPTURE_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            Service::BEAM_PUSH_DECRYPTION    => [
                Service::BEAM_PUSH_DECRYPTION_TYPE => Service::BEAM_PUSH_DECRYPTION_TYPE_AES256,
                Service::BEAM_PUSH_DECRYPTION_MODE => Service::BEAM_PUSH_DECRYPTION_MODE_GCM,
                Service::BEAM_PUSH_DECRYPTION_KEY  => bin2hex($data['password']),
                Service::BEAM_PUSH_DECRYPTION_IV   => bin2hex($this->iv),
            ]
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts', //Todo: correct this. find the correct channel
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'Axis-Paysecure Rupay Capture - File Send failure',
            // Todo: get correct mail address
            'recipient' => Constants::MAIL_ADDRESSES[Constants::GATEWAY_POD]
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'gateway'       => 'axis_paysecure',
                ]
            );
        }

        try
        {
            $this->sendConfirmationMail();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::AXIS_RUPAY_CAPTURE_FILE_CONFIRMATION_MAIL_FAILED,
                [
                    'file_name'     => $fullFileName,
                    'error_code'    => $e->getCode(),
                    'error'         => $e->getMessage(),
                ]);
        }
    }

    //TODO: complete this
    protected function sendConfirmationMail()
    {
        $recipients = $this->gatewayFile->getRecipients();

        $date = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d-M-y');

        $data = [
            'body' => "Hi,\n\nThe transaction file for " . $date . " has been shared over SFTP. Please check and confirm."
        ];

        $captureMail = new CaptureMail(
            $data,
            Payment\Gateway::PAYSECURE,
            Payment\Gateway::ACQUIRER_AXIS,
            $recipients,
            []
        );

        Mail::queue($captureMail);
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }

    protected function getFileToWriteName()
    {
        // This assumes we won't be sending more than 9 files after retry.
        $start = Carbon::now()->setTimezone(Timezone::IST)->startOfDay()->getTimestamp();

        $count = $this->repo->gateway_file->fetchFileSentCountFromStart(Type::CAPTURE, GatewayFileConstants::AXIS_PAYSECURE, $start);

        return static::FILE_NAME . Carbon::now()->setTimezone(Timezone::IST)->format('Ymd') . $this->numpadLeft((string)($count + 1), 6);
    }

    protected function getH2HMetadata()
    {
        return array(
            'gid'   => '10000',
            'uid'   => '10001',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        );
    }

    protected function fetchDataFromCps($entity, $paymentIds)
    {

        $responseItems = [];

        foreach (array_chunk($paymentIds, self::CPS_BULK_LIMIT) as $chunk)
        {
            $request = [
                'fields'        => self::CPS_PARAMS,
                'payment_ids'   => $chunk,
            ];

            try
            {
                $response = $this->app['card.payments']->fetchAuthorizationData($request);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::CARD_PAYMENT_SERVICE_ERROR,
                    [
                        'request'   => $request,
                    ]);

                throw $ex;
            }

            foreach ($response as $id => $item)
            {
                $responseItems[$id] = $item;
            }
        }

        // If for any payment, data is not fetched, throw an error
        if (sizeof($responseItems) != sizeof($paymentIds))
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'gateway_file_id'    => $this->gatewayFile->getId(),
                    'entity'             => $entity,
                    'api_payments_count' => sizeof($paymentIds),
                    'cps_payments_count' => $response['count'],
                    'message'            => 'Entity count mismatch between api and cps',
                ]
            );
        }

        return $responseItems;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $gatewayCode = Payment\Gateway::PAYSECURE;
        $acquirerCode = Payment\Gateway::ACQUIRER_AXIS;

        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => $gatewayCode,
                    RefundConstants::SCROOGE_GATEWAY_ACQUIRER   => $acquirerCode,
                    RefundConstants::SCROOGE_PAYMENT_GATEWAY_CAPTURED => true,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => GatewayFileConstants::FETCH_FROM_SCROOGE_COUNT,
        ];

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function fetchScroogeRefundsData(int $from, int $to, $refundIds = []): array
    {
        $input = $this->getScroogeQuery($from, $to, $refundIds);

        $refunds = [];
        $fetchSuccess = false;

        $scroogeMaxAttempts = GatewayFileConstants::SCROOGE_MAX_ATTEMPTS;

        for ($i = 0; $i < $scroogeMaxAttempts; $i++)
        {
            list($data, $success) = $this->getRefundsFromScrooge($input);

            // If data fetch is successful not retrying
            if ($success === true)
            {
                $refunds = $data;
                $fetchSuccess = true;

                break;
            }
        }

        // Throwing an error in case of scrooge fetch failure
        if ($fetchSuccess === false)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_FETCHING_FROM_SCROOGE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]
            );
        }

        return $refunds;
    }

    // Returns data, success - if scrooge calls fail - success is false
    protected function getRefundsFromScrooge(array $input): array
    {
        $returnData = [];

        $fetchFromScrooge = true;

        $skip = 0;

        do
        {
            $input[RefundConstants::SCROOGE_SKIP] = $skip;

            try
            {
                $response = $this->app['scrooge']->getFileBasedRefunds($input);

                $code = $response[RefundConstants::RESPONSE_CODE];

                if (in_array($code, Scrooge::RESPONSE_SUCCESS_CODES, true) === true)
                {
                    $data = $response[RefundConstants::RESPONSE_BODY][RefundConstants::RESPONSE_DATA];

                    if (empty($data) === false)
                    {
                        foreach ($data as $value)
                        {
                            $returnData[] = $value;
                        }

                        if (count($data) < GatewayFileConstants::FETCH_FROM_SCROOGE_COUNT)
                        {
                            // Data is complete
                            $fetchFromScrooge = false;
                        }
                        else
                        {
                            $skip += GatewayFileConstants::FETCH_FROM_SCROOGE_COUNT;
                        }
                    }
                    else
                    {
                        // Data is complete
                        $fetchFromScrooge = false;
                    }
                }
                else
                {
                    return [[], false];
                }
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SCROOGE_FETCH_FILE_BASED_REFUNDS_FAILED,
                    [
                        'input' => $input,
                        'id'    => $this->gatewayFile->getId(),
                    ]
                );

                return [[], false];
            }
        }
        while ($fetchFromScrooge === true);

        return [$returnData, true];
    }

    //-------------------------- Helpers ------------------------------------//

    private function numpadLeft($num, $count)
    {
        return str_pad($num, $count, '0', STR_PAD_LEFT);
    }

    private function numpadRight($num, $count)
    {
        return str_pad($num, $count, '0', STR_PAD_RIGHT);
    }

    private function strpadLeft($str, $length)
    {
        return str_pad($str, $length, ' ', STR_PAD_LEFT);
    }

    private function strpadRight($str, $length)
    {
        return str_pad($str, $length, ' ', STR_PAD_RIGHT);
    }
}

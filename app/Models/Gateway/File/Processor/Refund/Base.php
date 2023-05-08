<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Services\Scrooge;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Base\ConnectionType;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    /**
     * Being used to paginate the fetch from scrooge refunds API
     * Number of refunds to be fetched in each call
     *
     * @var int
     */
    protected $fetchFromScroogeCount = Constants::FETCH_FROM_SCROOGE_COUNT;

    /**
     * For gateways onboarded on scrooge - we will fetch the refunds from scrooge, these will be populated here
     *
     * @var array
     */
    protected array $scroogeRefundsData = [];

    /**
     * Being used to populate the scrooge refunds' payment_ids
     *
     * @var array
     */
    protected $scroogeRefundPaymentIds = [];

    /**
     * Being used to store number of max attempts in case of scrooge call failures
     *
     * @var int
     */
    protected $scroogeMaxAttempts = Constants::SCROOGE_MAX_ATTEMPTS;

    /**
     * Being used to store the number of elements that can be passed in the fetch query
     *
     * @var int
     */
    protected $queryLimit = Constants::QUERY_LIMIT;

    /**
     * @var bool
     * For gateways onboarded on scrooge - we will fetch the refunds from scrooge.
     */
    protected $fetchRefundsFromScrooge = false;

    const FETCH_REFUNDS_DATA_FROM_SCROOGE = 'fetch_refunds_data_from_scrooge';

    /**
     * Resetting all the global variables before use -
     * since this is being used as a singleton class
     *
     * @return BaseProcessor|void
     */
    public function resetFileProcessorAttributes()
    {
        $this->queryLimit              = Constants::QUERY_LIMIT;
        $this->scroogeRefundsData      = [];
        $this->scroogeMaxAttempts      = Constants::SCROOGE_MAX_ATTEMPTS;
        $this->fetchFromScroogeCount   = Constants::FETCH_FROM_SCROOGE_COUNT;
        $this->scroogeRefundPaymentIds = [];
        $this->fetchRefundsFromScrooge = false;

        parent::resetFileProcessorAttributes();
    }

    public function fetchEntities(): PublicCollection
    {
        $this->fetchRefundsFromScrooge = $this->shouldRefundsBeFetchedFromScrooge();

        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $this->updateBeginAndEndIfRequired($begin, $end);

        // If Scrooge needs to be called to fetch refunds data
        if ($this->fetchRefundsFromScrooge === true)
        {
            // Populating scrooge refunds data
            $scroogeRefundsData = $this->fetchScroogeRefundsData($begin, $end);

            $this->scroogeRefundsData = $scroogeRefundsData;

            if (sizeof($this->scroogeRefundsData) > 1)
            {
                $this->scroogeRefundsData = array_sort($this->scroogeRefundsData, function (array $refund) {
                    return $refund['created_at'];
                });
            }

            $this->scroogeRefundPaymentIds = array_unique(array_column($this->scroogeRefundsData, RefundConstants::PAYMENT_ID));

            $shouldFetchPayments = true;
            $start = 0;

            $payments = new PublicCollection();

            while ($shouldFetchPayments === true)
            {
                $paymentIds = array_slice($this->scroogeRefundPaymentIds, $start, $this->queryLimit);

                $fetchedPayments = $this->repo->payment->fetchPaymentsGivenIdsFromTidb(
                    $paymentIds, $this->queryLimit, ConnectionType::DATA_WAREHOUSE_ADMIN);

                $payments = $payments->merge($fetchedPayments);

                if (count($fetchedPayments) < $this->queryLimit)
                {
                    $shouldFetchPayments = false;
                }

                $start += $this->queryLimit;
            }

            $paymentsDiff = array_diff($this->scroogeRefundPaymentIds, $payments->pluck(Payment\Entity::ID)->toArray());

            if (count($paymentsDiff) > 0)
            {
                $this->trace->error(TraceCode::DATA_WAREHOUSE_PAYMENT_FETCH_ERROR,[
                    'payments_diff' => $paymentsDiff,
                    'type'          => $this->gatewayFile->getType(),
                    'target'        => $this->gatewayFile->getTarget(),
                ]);

                throw new GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA);
            }
            //
            // Returning payments for the relevant,
            // refunds have been populated in $scroogeRefunds
            //
            return $payments;
        }
        else
        {
            //
            // Regular flow - fetching refunds from API DB
            //

            return $this->fetchRefundsFromAPI($begin, $end);
        }
    }

    // $entities - since it can either be payments or refunds based on whether we fetch from scrooge or not
    public function checkIfValidDataAvailable(PublicCollection $entities)
    {
        if ($entities->isEmpty() === true)
        {
            throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    /**
     * Fetches all necessary refund related data required for generating the file
     * $entities - since it can either be payments or refunds based on whether we fetch from scrooge or not
     *
     * @param  PublicCollection $entities
     *
     * @return array
     * @throws GatewayFileException
     */
    public function generateData(PublicCollection $entities)
    {
        $data = [];

        $nbplusPaymentIds = [];

        $upiPaymentIds = [];

        // Refunds were fetched from scrooge
        if ($this->fetchRefundsFromScrooge === true)
        {
            foreach ($this->scroogeRefundsData as $refund)
            {
                $payment = $entities->where(Payment\Entity::ID, '=', $refund[RefundConstants::PAYMENT_ID])->first();

                $col = $this->collectPaymentData($payment);

                $col['refund'] = $refund;

                $data[] = $col;

                if (($payment->getCpsRoute() === PaymentEntity::NB_PLUS_SERVICE) or
                    ($payment->getCpsRoute() === PaymentEntity::NB_PLUS_SERVICE_PAYMENTS))
                {
                    $nbplusPaymentIds[] = $payment->getId();
                }
                else if (($this->isUpsRefundGateway() === true) and
                    (($payment->getCpsRoute() === PaymentEntity::UPI_PAYMENT_SERVICE ) or
                        ( $payment->getCpsRoute() === PaymentEntity::REARCH_UPI_PAYMENT_SERVICE)))
                {
                    $upiPaymentIds[] = $payment->getId();
                }
            }

            $data = $this->addGatewayEntitiesToDataWithPaymentIds($data, $this->scroogeRefundPaymentIds);
        }
        else
        {
            $scroogeRefundIds = [];

            // Is file based refund gateway for which refunds data need to be fetched from Scrooge
            $fileBasedRefundGateway = in_array(
                static::GATEWAY,
                array_keys(Payment\Gateway::$scroogeFileBasedRefundGatewaysWithTimestamps), true
            );

            if ($fileBasedRefundGateway === true)
            {
                $scroogeRefundIds = $entities->where(Payment\Refund\Entity::IS_SCROOGE, '=', 1)->getIds();

                if (count($scroogeRefundIds) > 0)
                {
                    $this->populateScroogeRefundsGivenIds($scroogeRefundIds);
                }

                $scroogeRefundIds = array_unique(array_column($this->scroogeRefundsData, RefundConstants::SCROOGE_ID));
            }

            // regular API flow
            foreach ($entities as $refund)
            {
                //
                // The following checks are being made to ensure these conditions
                // If a refund belongs to scrooge - Scrooge is the single source of truth -
                // whether the refund is to be sent in the file or not, there are various flows in which Scrooge
                // could process these refunds - Instant Refunds, FTAs, TPV, etc.
                // Hence, if a refund belongs to scrooge and it is of a file based gateway -
                // whose refunds data is fetched from Scrooge - we need to ensure that the refund must be present
                // in the response from Scrooge.
                //
                // Therefore, the only case where the following conditions don't evaluate to true is the following:
                // The refund was processed on scrooge, belonging to a file based refunds gateway via Scrooge,
                // by tpv or instant refunds so it should not be included in the file
                //
                if (($refund->isScrooge() === false) or
                    ($fileBasedRefundGateway === false) or
                    (in_array($refund->getId(), $scroogeRefundIds, true) === true))
                {
                    $payment = $refund->payment;

                    $col = $this->collectPaymentData($payment);

                    $col['refund'] = $refund->toArray();

                    $data[] = $col;

                    if ($payment->getCpsRoute() === PaymentEntity::NB_PLUS_SERVICE)
                    {
                        $nbplusPaymentIds[] = $payment->getId();
                    }
                }
            }

            $data = $this->addGatewayEntitiesToData($data, $entities);
        }

        $data = $this->addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds($data, $nbplusPaymentIds, $payment->getMethod());

        $data = $this->addUpiGatewayEntitiesToDataWithUpiPaymentIds($data, $upiPaymentIds);

        $this->checkIfRefundsAreInValidDateRange($data);

        return $data;
    }

    /**
     * We create the required file and associate it with the gateway_file entity
     * Any exception during file generation etc is caught and handled accordingly
     *
     * @param  $data
     *
     * @throws GatewayFileException
     */
    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    public function sendFile($data)
    {
        try
        {
            $recipients = $this->gatewayFile->getRecipients();

            $mailData = $this->formatDataForMail($data);

            $refundFileMail = new RefundFileMail($mailData, static::GATEWAY, $recipients);

            Mail::queue($refundFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($data);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function addGatewayEntitiesToData(array $data, PublicCollection $refunds)
    {
        $gateway = static::GATEWAY;

        $paymentIds = $refunds->pluck('payment_id')->toArray();

        $gatewayEntities = $this->repo->$gateway->fetchByPaymentIdsAndAction(
            $paymentIds, Action::AUTHORIZE);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $data = array_map(function ($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        $gateway = static::GATEWAY;

        $gatewayEntities = $this->repo->$gateway->fetchByPaymentIdsAndAction(
            $paymentIds, Action::AUTHORIZE);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $data = array_map(function ($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]))
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    protected function addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds(array $data, array $nbplusPaymentIds, string $entity): array
    {
        // Fetching NBPlus Payments Gateway Data from NBPlus
        if (empty($nbplusPaymentIds) === false)
        {
            list($nbPlusGatewayEntities, $fetchSuccess) = $this->fetchNbPlusGatewayEntities($nbplusPaymentIds, $entity);

            // Throwing an error in case of NBPlus fetch failure
            if ($fetchSuccess === false)
            {
                throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'id' => $this->gatewayFile->getId(),
                    ]
                );
            }

            $data = array_map(function($row) use ($nbPlusGatewayEntities)
            {
                $paymentId = $row['payment']['id'];

                if (isset($nbPlusGatewayEntities[$paymentId]) === true)
                {
                    $row['gateway'] = $nbPlusGatewayEntities[$paymentId];
                }

                return $row;
            }, $data);
        }

        return $data;
    }

    /**
     * Returns true if the gateway is onboarded on UPI Payment Service, i.e. UPS
     * else returns false
     *
     * @return bool
     */
    protected function isUpsRefundGateway()
    {
        return false;
    }

    protected function addUpiGatewayEntitiesToDataWithUpiPaymentIds(array $data, array $upiPaymentIds)
    {
        if (empty($upiPaymentIds) === true) {
            return $data;
        }

        list($upiGatewayEntities, $fetchSuccess) = $this->fetchUpiGatewayEntities($upiPaymentIds);

        // Throwing an error in case of UPS fetch failure
        if ($fetchSuccess === false)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_DATA,
                [
                    'id' => $this->gatewayFile->getId(),
                ]
            );
        }

        $data = array_map(function($row) use ($upiGatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($upiGatewayEntities[$paymentId]) === true)
            {
                $row['gateway'] = $upiGatewayEntities[$paymentId];
            }

            return $row;
        }, $data);

        return $data;
    }

    protected function fetchUpiGatewayEntities(array $paymentIds)
    {
        $shouldFetchEntities = true;

        $start = 0;

        $fetchLimit = self::UPS_FETCH_ENTITY_COUNT;

        $gatewayData = [];

        $fetchSuccess = true;

        while ($shouldFetchEntities === true)
        {
            $requestPaymentIds = array_slice($paymentIds, $start, $fetchLimit);

            if ((count($requestPaymentIds) === 0) or ($fetchSuccess === false))
            {
                $shouldFetchEntities = false;
            }
            else
            {
                try
                {
                    $entities = $this->fetchMultipleUpsGatewayEntities($requestPaymentIds);

                    $start += $fetchLimit;

                    $gatewayData = array_merge($gatewayData, $entities);

                    $fetchSuccess = true;
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                        [
                            'input' => $requestPaymentIds,
                            'id'    => $this->gatewayFile->getId(),
                        ]
                    );

                    $fetchSuccess = false;
                }
            }
        }

        $paymentIdToGatewayDataMap = [];

        foreach ($gatewayData as $data)
        {
            if (isset($data[Constants::PAYMENT_ID]) === true)
            {
                // check if gateway data is not empty to avoid null pointer exceptions
                // and unnecessary call to json_decode function
                if (empty($data[Constants::GATEWAY_DATA]) === false)
                {
                    $data[Constants::GATEWAY_DATA] = json_decode($data[Constants::GATEWAY_DATA], true);
                }

                // set gateway data as empty array, if it is empty
                // gateway data can be empty if the above if condition does not evaluate to true
                // or, if the gateway data was an invalid json string and json_decode returned NULL
                if (empty($data[Constants::GATEWAY_DATA]) === true)
                {
                    $data[Constants::GATEWAY_DATA] = [];
                }

                $paymentIdToGatewayDataMap[$data[Constants::PAYMENT_ID]] = $data;
            }
        }

        return [$paymentIdToGatewayDataMap, $fetchSuccess];
    }

    protected function fetchMultipleUpsGatewayEntities(array $paymentIds)
    {
        $action = Constants::MULTIPLE_ENTITY_FETCH;

        $gateway = static::GATEWAY;

        $input = [
            Constants::MODEL            => Constants::AUTHORIZE,
            Constants::REQUIRED_FIELDS  => [
                Constants::CUSTOMER_REFERENCE,
                Constants::MERCHANT_REFERENCE,
                Constants::GATEWAY_MERCHANT_ID,
                Constants::GATEWAY_DATA,
                Constants::PAYMENT_ID,
            ],
            Constants::COLUMN_NAME      => Constants::PAYMENT_ID,
            Constants::VALUES           => $paymentIds,
        ];

        $response = $this->app['upi.payments']->action($action, $input, $gateway);

        return $response;
    }

    /**
     * @param string $fileType
     *
     * @return array|mixed Bucket Config containing file name and bucket region
     */
    protected function getBucketConfig(string $fileType)
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName($fileType, $this->env);

        return $config[$bucketType];
    }

    /**
     * Checks if the given gateway file can be retried or not. Currently
     * we consider that if the refund gateway_file entity is in acknowledged state
     * then it cannot be retried further.
     * Also if a gateway_file entity was marked as failed earlier as there was no data
     * to process during that interval then also it cannot be retried again.
     *
     * @return bool Whether gateway_file entity can be processed again or not
     */
    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }

    /**
     * It has to be a scrooge gateway.
     * Both the begin and end timestamps should be post scrooge onboarding timestamp -
     * else all refunds will be fetched from API, using the existing flow.
     *
     * @return bool
     */
    protected function shouldRefundsBeFetchedFromScrooge(): bool
    {
        if (in_array(static::GATEWAY, array_keys(Payment\Gateway::$scroogeFileBasedRefundGatewaysWithTimestamps), true) === true)
        {
            $begin = $this->gatewayFile->getBegin();
            $end   = $this->gatewayFile->getEnd();

            $goLiveTimestamp = Payment\Gateway::$scroogeFileBasedRefundGatewaysWithTimestamps[static::GATEWAY];

            if (($end >= $goLiveTimestamp) and
                ($begin >= $goLiveTimestamp))
            {
                return true;
            }
        }

        $variant = $this->app->razorx->getTreatment(static::GATEWAY, self::FETCH_REFUNDS_DATA_FROM_SCROOGE, $this->mode);

        $this->trace->info(TraceCode::RAZORX_EXPERIMENT_RESULT, [
            'gateway'    => static::GATEWAY,
            'variant'    => $variant,
            'experiment' => self::FETCH_REFUNDS_DATA_FROM_SCROOGE,
            'mode'       => $this->mode,
        ]);

        return $variant === 'on';
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     * @throws GatewayFileException
     */
    protected function fetchScroogeRefundsData(int $from, int $to, $refundIds = []): array
    {
        $input = $this->getScroogeQuery($from, $to, $refundIds);

        $refunds = [];
        $fetchSuccess = false;

        for ($i = 0; $i < $this->scroogeMaxAttempts; $i++)
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

    /**
     * @param $listOfRefunds
     * @throws GatewayFileException
     */
    protected function populateScroogeRefundsGivenIds($listOfRefunds)
    {
        $shouldFetchScroogeRefunds = true;
        $start = 0;

        $fetchLimit = $this->fetchFromScroogeCount;

        $scroogeRefundsData = [];

        while ($shouldFetchScroogeRefunds === true)
        {
            $refundIds = array_slice($listOfRefunds, $start, $fetchLimit);

            if (count($refundIds) === 0)
            {
                $shouldFetchScroogeRefunds = false;
            }
            else
            {
                $scroogeRefundsData = array_merge(
                    $scroogeRefundsData,
                    $this->fetchScroogeRefundsData($this->gatewayFile->getBegin(), $this->gatewayFile->getEnd(), $refundIds)
                );

                $start += $fetchLimit;
            }
        }

        $this->scroogeRefundsData = $scroogeRefundsData;

        if (sizeof($this->scroogeRefundsData) > 1)
        {
            $this->scroogeRefundsData = array_sort($this->scroogeRefundsData, function (array $refund) {
                return $refund['created_at'];
            });
        }
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

                        if (count($data) < $this->fetchFromScroogeCount)
                        {
                            // Data is complete
                            $fetchFromScrooge = false;
                        }
                        else
                        {
                            $skip += $this->fetchFromScroogeCount;
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

    protected function collectPaymentData(Payment\Entity $payment): array
    {
        $terminal = $payment->terminal;

        $merchant = $payment->merchant;

        $col['payment'] = $payment->toArray();

        $col['terminal'] = $terminal->toArray();

        $col['merchant'] = $merchant->toArray();

        if ($payment->hasCard() === true)
        {
            $col['card'] = $payment->card->toArray();
        }

        return $col;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => static::GATEWAY,
                    RefundConstants::SCROOGE_BANK       => static::GATEWAY_CODE,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    /**
     * @param int $begin
     * @param int $end
     * @return PublicCollection
     */
    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        //
        // Regular flow - fetching refunds from API DB
        //

        $refunds = $this->repo->refund->fetchRefundsForGatewaysBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            static::GATEWAY_CODE,
            $begin,
            $end,
            static::GATEWAY
        );

        return $refunds;
    }

    /**
     * Refunds should be in expected date ranges
     *
     * @param array $data
     * @throws GatewayFileException
     */
    protected function checkIfRefundsAreInValidDateRange(array $data)
    {
        // This check is applicable only for Scrooge refunds
        // SBI - is handling older failed refunds as well - so this check won't be applicable for this gateway
        if (empty($this->scroogeRefundsData) === false)
        {
            //
            // Adding checks to ensure refunds are in expected date range - if not throwing exception
            //

            $refundData = array_column($data, RefundConstants::REFUND);

            if (empty($refundData) === false)
            {
                $refundCreatedAtRange = array_column($refundData, RefundConstants::SCROOGE_CREATED_AT);

                // For some gateways, refunds fetched are supposed to be in different time range than
                // the time range of file..
                // Ex- For NB sbi, its 8-8 when the file time is 12-12
                $endTime   = $this->gatewayFile->getEnd();
                $beginTime = $this->gatewayFile->getBegin();

                $this->updateBeginAndEndIfRequired($beginTime, $endTime);

                if ((max($refundCreatedAtRange) > $endTime) or
                    (min($refundCreatedAtRange) < $beginTime))
                {
                    throw new GatewayFileException(
                        ErrorCode::SERVER_ERROR_GATEWAY_FILE_LOGICAL_ERROR_REFUNDS_OUT_OF_RANGE,
                        [
                            'id'                   => $this->gatewayFile->getId(),
                            'max_time'             => max($refundCreatedAtRange),
                            'min_time'             => min($refundCreatedAtRange),
                            'updatedEndTime'       => $endTime,
                            'updatedBeginTime'     => $beginTime
                        ]
                    );
                }
            }
        }
    }

    // function to update start and end times.
    // for example: for nb_sbi, refund file is for 8 P.M - 8 P.M., so 4 hours are deducted from
    // both the begin and end times.
    public function updateBeginAndEndIfRequired(& $begin, & $end)
    {
    }
}

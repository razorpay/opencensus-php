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
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
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
    protected $fetchFromScroogeCount = 500;

    /**
     * For gateways onboarded on scrooge - we will fetch the refunds from scrooge, these will be populated here
     *
     * @var array
     */
    protected $scroogeRefunds = [];

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
    protected $scroogeMaxAttempts = 1;

    /**
     * Being used to store the number of elements that can be passed in the fetch query
     *
     * @var int
     */
    protected $queryLimit = 50000;

    /**
     * @var bool
     * For gateways onboarded on scrooge - we will fetch the refunds from scrooge.
     */
    protected $fetchRefundsFromScrooge;

    public function fetchEntities(): PublicCollection
    {
        $this->fetchRefundsFromScrooge = $this->shouldRefundsBeFetchedFromScrooge();

        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        // If Scrooge needs to be called to fetch refunds data
        if ($this->fetchRefundsFromScrooge === true)
        {
            // Populating scrooge refunds data
            $this->populateScroogeRefunds($begin, $end);

            $this->scroogeRefundPaymentIds = array_unique(array_column($this->scroogeRefunds, RefundConstants::PAYMENT_ID));

            $shouldFetchPayments = true;
            $start = 0;

            $payments = new PublicCollection();

            while ($shouldFetchPayments === true)
            {
                $paymentIds = array_slice($this->scroogeRefundPaymentIds, $start, $this->queryLimit);

                $fetchedPayments = $this->repo->payment->fetchPaymentsGivenIds($paymentIds, $this->queryLimit);

                $payments = $payments->merge($fetchedPayments);

                if (count($fetchedPayments) < $this->queryLimit)
                {
                    $shouldFetchPayments = false;
                }

                $start += $this->queryLimit;
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
     */
    public function generateData(PublicCollection $entities)
    {
        $data = [];

        // Refunds were fetched from scrooge
        if ($this->fetchRefundsFromScrooge === true)
        {
            foreach ($this->scroogeRefunds as $refund)
            {
                $payment = $entities->where(Payment\Entity::ID, '=', $refund[RefundConstants::PAYMENT_ID])->first();

                $col = $this->collectPaymentData($payment);

                $col['refund'] = $refund;

                $data[] = $col;
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

                $scroogeRefundIds = array_unique(array_column($this->scroogeRefunds, RefundConstants::SCROOGE_ID));
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
                }
            }

            $data = $this->addGatewayEntitiesToData($data, $entities);
        }

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
    protected function shouldRefundsBeFetchedFromScrooge()
    {
        if ((Payment\Gateway::isScroogeGatewayAndMerchant(static::GATEWAY) === true) and
            (in_array(static::GATEWAY, array_keys(Payment\Gateway::$scroogeFileBasedRefundGatewaysWithTimestamps), true) === true))
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

        return false;
    }

    /**
     * @param $from
     * @param $to
     * @throws GatewayFileException
     */
    protected function populateScroogeRefunds(int $from, int $to, $refundIds = [])
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
                ]);
        }

        $this->scroogeRefunds = array_merge($this->scroogeRefunds, $refunds);

        $this->scroogeRefunds = array_sort($this->scroogeRefunds, function ($refund1, $refund2) {
            return $refund1['created_at'] <=> $refund2['created_at'];
        });
    }

    /**
     * @param $listOfRefunds
     * @throws GatewayFileException
     */
    protected function populateScroogeRefundsGivenIds($listOfRefunds)
    {
        $shouldFetchScroogeRefunds = true;
        $start = 0;

        $fetchLimit = $this->fetchFromScroogeCount - 1;

        while ($shouldFetchScroogeRefunds === true)
        {
            $refundIds = array_slice($listOfRefunds, $start, $fetchLimit);

            if (count($refundIds) === 0)
            {
                $shouldFetchScroogeRefunds = false;
            }
            else
            {
                $this->populateScroogeRefunds($this->gatewayFile->getBegin(), $this->gatewayFile->getEnd(), $refundIds);

                $start += $this->fetchFromScroogeCount-1;
            }
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
}

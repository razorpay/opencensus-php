<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Batch\Entity as BatchEntity;
use RZP\Models\Batch\Header as BatchContants;
use RZP\Models\Card\IIN\Import\XLSFileHandler;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Http\RequestHeader;
use RZP\Models\Batch\Header;
use RZP\Models\Payment\Fraud;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    public function notify(array $input, $source): array
    {
        (new Validator())->validateInput('notify', $input);

        $bulkFraudNotificationEntity = (new Entity())->generateId();

        $this->trace->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_STARTED, [
            'input'     => $input,
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ]);

        $file = $input[Constants::FILE];

        (new File())->saveLocalFile($file, $bulkFraudNotificationEntity);

        $data = (new File())->getFileData($file);

        $headers = array_shift($data);

        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_DATA, [
            'data'      => $data,
            'header'    => $headers,
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ]);

        $outputUrl = (new Processor($bulkFraudNotificationEntity))->process($data, $headers, $source);

        return [
            'link'      => $outputUrl,
            'entity_id' => $bulkFraudNotificationEntity->getId(),
        ];

    }

    public function notifyPostBatch(array $input): array
    {
        $this->trace->info(TraceCode::BATCH_REQUEST_NOTIFY_PAYMENT_FRAUD, $input);

        $batchId = $input[Batch\Entity::BATCH][Batch\Entity::ID];

        $fraudEntities = $this->repo->payment_fraud->fetch([
            Fraud\Entity::BATCH_ID  =>  $batchId,
        ]);

        $aggregatedData = [];

        $output = [];

        foreach ($fraudEntities as $fraudEntity)
        {
            $paymentID = $fraudEntity->getPaymentId();

            $payment = $this->repo->payment->findOrFailPublic($paymentID);

            $fraudRowResult = Processor::getFraudNotificationRowData($payment, $fraudEntity);

            $aggregatedData[$payment->getMerchantId()] []= $fraudRowResult;

            $output[$payment->getMerchantId()] [] = [
                Constants::OUTPUT_KEY_PAYMENT_ID   => $paymentID,
                Constants::OUTPUT_KEY_FD_TICKET_ID => '',
                Constants::OUTPUT_KEY_ERROR        => '',
            ];
        }

         (new Freshdesk(null, $batchId))->notify($aggregatedData, $output);

        $paymentIDDataMap = $this->getPaymentIDDataMapFromOutput($output);

        // Notify Risk Team
        return $this->sendMailPostCSVUpdate($input, $paymentIDDataMap);
    }

    public function fetchArnAndRrnFromBatch($input): array
    {
        $arnArr = [];

        $arnVsRrn = [];

        foreach ($input as $row)
        {
            $arn = $row[Constants::BATCH_KEY_ARN];

            $rrn = $row[Constants::BATCH_KEY_RRN];

            if (strlen($arn) > 0)
            {
                $arnArr []= $arn;

                $arnVsRrn[$arn] = $rrn;
            }
        }

        return [$arnArr, $arnVsRrn];
    }

    protected function getUnixTimestampFromExcelTimestamp($excelTimestamp)
    {
        // corresponds to start of 2021
        /// this is used as a proxy to check that the timestamp is is a valid unixtimestamp
        if ($excelTimestamp <= time() and $excelTimestamp >= 1609459200)
        {
            return $excelTimestamp;
        }
        return ($excelTimestamp - Constants::JAN_1_1970_TIMESTAMP) * Constants::DAYS_TO_SECONDS_MULTIPLIER;
    }

    protected function saveFraudEntityFromBatchInputRow($row, $batchId): array
    {
        $row[Fraud\Entity::BATCH_ID] = $batchId;

        $row[Fraud\Entity::AMOUNT] = $row[Constants::BATCH_KEY_AMOUNT];

        if (isset($row[Fraud\Entity::AMOUNT]) === false or strlen($row[Fraud\Entity::AMOUNT]) === 0)
        {
            $payment = $this->repo->payment->findOrFail($row[Fraud\Entity::PAYMENT_ID]);

            $row[Fraud\Entity::AMOUNT] = $payment->getAmount();

            $row[Fraud\Entity::BASE_AMOUNT] = $payment->getBaseAmount();

            $row[Fraud\Entity::CURRENCY] = $payment->getCurrency();
        }

        unset($row[Constants::BATCH_KEY_ERROR_REASON]);

        unset($row[Constants::BATCH_KEY_RRN]);

        unset($row[Constants::BATCH_KEY_AMOUNT]);

        // Mastercard Fraud data contains both code and a short description. This is for separating out the code from the description.
        if ($row[Constants::BATCH_KEY_REPORTED_BY] === Constants::REPORTED_BY_MASTERCARD)
        {
            $row[Constants::BATCH_KEY_TYPE] = explode(' ', $row[Constants::BATCH_KEY_TYPE])[0];

            $row[Constants::BATCH_KEY_SUB_TYPE] = explode(' ', $row[Constants::BATCH_KEY_SUB_TYPE])[0];

            $row[Fraud\Entity::SOURCE] = Constants::MASTERCARD_FRAUD_FILE_SOURCE;
        }
        else
        {
            $row[Fraud\Entity::SOURCE] = Constants::VISA_FRAUD_FILE_SOURCE;
            // this is not a mandatory field and only takes integer value
        }

        if (empty($row[Constants::BATCH_KEY_REPORTED_TO_ISSUER_AT]) == false &&
            intval($row[Constants::BATCH_KEY_REPORTED_TO_ISSUER_AT] !== 0))
        {
            $row[Fraud\Entity::REPORTED_TO_ISSUER_AT] = $this->getUnixTimestampFromExcelTimestamp(intval($row[Fraud\Entity::REPORTED_TO_ISSUER_AT]));
        }
        else
        {
            $row[Fraud\Entity::REPORTED_TO_ISSUER_AT] = null;
        }

        if (empty($row[Constants::BATCH_KEY_REPORTED_TO_RAZORPAY_AT]) == false &&
            intval($row[Constants::BATCH_KEY_REPORTED_TO_RAZORPAY_AT] !== 0))
        {
            $row[Fraud\Entity::REPORTED_TO_RAZORPAY_AT] = $this->getUnixTimestampFromExcelTimestamp(intval($row[Fraud\Entity::REPORTED_TO_RAZORPAY_AT]));
        }
        else
        {
            $row[Fraud\Entity::REPORTED_TO_RAZORPAY_AT] = null;
        }

        return (new Fraud\Core())->createOrUpdateFraudEntity($row);
    }

    protected function setPaymentId(&$row, &$rowOutput, $paymentId)
    {
        $row[Constants::INPUT_KEY_PAYMENT_ID] = $rowOutput[Header::FRAUD_OUTPUT_HEADER_PAYMENT_ID] = $paymentId;
    }

    protected function setFraudIdAndStatus(&$rowOutput, $fraudId, $isEntityCreated)
    {
        $rowOutput[Header::FRAUD_OUTPUT_HEADER_FRAUD_ID] = $fraudId;

        $rowOutput[Header::FRAUD_OUTPUT_HEADER_STATUS] = ($isEntityCreated === true)
            ? Constants::BATCH_STATUS_CREATED : Constants::BATCH_STATUS_UPDATED;
    }

    protected function getDefaultValuesForBatchOutputRow($row, $fetchFromDataLakeSuccessful, $idempotencyKey): array
    {
        $rowOutput = [
            Header::FRAUD_OUTPUT_HEADER_ARN           =>  $row[Constants::BATCH_KEY_ARN],
            Header::FRAUD_OUTPUT_HEADER_PAYMENT_ID    =>  '',
            Header::FRAUD_OUTPUT_HEADER_FRAUD_ID      =>  '',
            Header::FRAUD_OUTPUT_HEADER_STATUS        =>  Constants::BATCH_STATUS_FAILED,
            Header::FRAUD_OUTPUT_HEADER_ERROR_REASON  =>  $row[Constants::BATCH_KEY_ERROR_REASON],
            Constants::IDEMPOTENCY_KEY                =>  $idempotencyKey,
            Constants::SUCCESS                        =>  'true',
        ];

        if ($fetchFromDataLakeSuccessful === false)
        {
            $rowOutput[Header::FRAUD_OUTPUT_HEADER_ERROR_REASON] = Constants::FRAUD_ERROR_REASON_ARN_TO_PAYMENT_ID;
        }

        return $rowOutput;
    }

    public function createFraudBatch(array $input)
    {
        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $this->trace->info(TraceCode::PAYMENT_FRAUD_BATCH_START, [
            'batchId'      => $batchId,
        ]);


        [$arnArr, $arnVsRrn] = $this->fetchArnAndRrnFromBatch($input);

        $fetchFromDataLakeSuccessful = true;

       try
        {
            $arnVsPaymentDetail = (new Payment\Service())->getPaymentIdFromARNorRRN($arnArr, $arnVsRrn);
        }
        catch (\Throwable $e)
        {
            $fetchFromDataLakeSuccessful = false;

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FRAUD_AUTOMATION_DATA_LAKE_QUERY_FAILED);
        }

        $output = new Base\PublicCollection;

        $notificationDisabledMidSet = [];

        foreach ($input as $row)
        {
            $sendMailKey = $row[Constants::BATCH_KEY_SEND_MAIL] ?? 'Y'; // 'Y' -> Yes 'N' -> No

            $shouldDisableNotification = $sendMailKey === 'N';

            unset($row[Constants::BATCH_KEY_SEND_MAIL]);

            $rowOutput = $this->getDefaultValuesForBatchOutputRow($row, $fetchFromDataLakeSuccessful, $row[Batch\Constants::IDEMPOTENCY_KEY]);

            unset($row[Batch\Constants::IDEMPOTENCY_KEY]);

            $arn = $row[Constants::BATCH_KEY_ARN];

            if (strlen($rowOutput[Header::FRAUD_OUTPUT_HEADER_ERROR_REASON]) > 0)
            {
                $output->push($rowOutput);

                continue;
            }

            if (isset($arnVsPaymentDetail) === false or
                isset($arnVsPaymentDetail[$arn][Constants::INPUT_KEY_PAYMENT_ID]) === false)
            {
                $rowOutput[Header::FRAUD_OUTPUT_HEADER_ERROR_REASON] = Constants::FRAUD_ERROR_REASON_ARN_TO_PAYMENT_ID;

                $output->push($rowOutput);

                continue;
            }

            try
            {
                $arn = $row[Constants::BATCH_KEY_ARN];

                $this->setPaymentId($row, $rowOutput, $arnVsPaymentDetail[$arn][Constants::INPUT_KEY_PAYMENT_ID]);

                [$isEntityCreated, $fraudEntity] = $this->saveFraudEntityFromBatchInputRow($row, $batchId);

                if (($shouldDisableNotification === true) and
                    ($fraudEntity !== null))
                {
                    $paymentEntity = $this->repo->payment->findByPublicId(Payment\Entity::getSign() . '_' . $fraudEntity->getPaymentId());

                    $notificationDisabledMidSet[] = $paymentEntity->getMerchantId();
               }

                $this->setFraudIdAndStatus($rowOutput, $fraudEntity->getId(), $isEntityCreated);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Logger::ERROR, TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ERROR);

                $rowOutput[Header::FRAUD_OUTPUT_HEADER_ERROR_REASON] = $e->getMessage();
            }

            $output->push($rowOutput);
        }

        $this->setNotificationDisabledMidSet($batchId, $notificationDisabledMidSet);

        $outputArray = $output->toArrayWithItems();

        $this->trace->info(TraceCode::PAYMENT_FRAUD_BATCH_OUTPUT,  $outputArray);

        return $outputArray;
    }

    protected function setNotificationDisabledMidSet($batchId, array $notificationDisabledMidSet)
    {
        if (empty($notificationDisabledMidSet) === true)
        {
            return;
        }

        $redisKey = (new Freshdesk(null, $batchId))->getSkipNotificationForBatchRediskKey();

        $this->app['redis']->sadd($redisKey, $notificationDisabledMidSet);

        $this->app['redis']->expire($redisKey, 86400);

        $this->app['trace']->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_DISABLED_MID_SET, [
            'batch_id' => $batchId,
            'mid_set'  => $notificationDisabledMidSet,
        ]);
    }


    public function getPaymentIDDataMapFromOutput(array $output): array
    {
        $paymentIDDataMap = [];

        foreach ($output as $merchantId => $merchantData)
        {
            foreach ($merchantData as $data)
            {
                $paymentIDDataMap[$data[Constants::OUTPUT_KEY_PAYMENT_ID]] = [
                    Constants::OUTPUT_KEY_MERCHANT_ID => $merchantId,
                    Constants::OUTPUT_KEY_FD_TICKET_ID => $data[Constants::OUTPUT_KEY_FD_TICKET_ID]
                ];
            }
        }

        return $paymentIDDataMap;
    }

    public function addFieldsToCSVFile(string $filePath, array $paymentIDDataMap)
    {
        $csvRows = (new XLSFileHandler)->getCsvData($filePath)['data'];

        $totalRows = count($csvRows);

        if ($totalRows > 0)
        {
            array_push($csvRows[0], BatchContants::FRAUD_OUTPUT_HEADER_MERCHANT_ID,
                BatchContants::FRAUD_OUTPUT_HEADER_FRESHDESK_ID);
            $paymentIDCol = null;

            for ($col = 0; $col < count($csvRows); $col++)
            {
                if ($csvRows[0][$col] === BatchContants::FRAUD_OUTPUT_HEADER_PAYMENT_ID) {
                    $paymentIDCol = $col;
                }
            }

            for ($row = 1; $row < $totalRows; $row++)
            {
                $paymentID = $csvRows[$row][$paymentIDCol];
                if (array_key_exists($paymentID, $paymentIDDataMap))
                {
                    array_push($csvRows[$row], $paymentIDDataMap[$paymentID][Constants::OUTPUT_KEY_MERCHANT_ID],
                        $paymentIDDataMap[$paymentID][Constants::OUTPUT_KEY_FD_TICKET_ID]);
                }
            }

            $reWriteFile = fopen($filePath, 'w');

            foreach ($csvRows as $rowToWrite)
            {
                fputcsv($reWriteFile, $rowToWrite);
            }

            fclose($reWriteFile);
        }

    }

    /**
     * @param array $input
     * @param array $paymentIDDataMap
     *
     * @return array
     * @throws \Exception
     */
    public function sendMailPostCSVUpdate(array $input, array $paymentIDDataMap): array
    {
        $batch = $input[BatchEntity::BATCH];

        $bucketType = $input[BatchEntity::BUCKET_TYPE];

        $outputFilePath = $input[BatchEntity::OUTPUT_FILE_PATH];

        $downloadFile = $input[BatchEntity::DOWNLOAD_FILE];

        $settings = $input[BatchEntity::SETTINGS];

        $type = $batch[BatchEntity::TYPE];
        $type = studly_case($type);

        if ($settings == null)
        {
            $settings = [];
        }

        $merchantId = $batch[Constants::OUTPUT_KEY_MERCHANT_ID];
        $merchant   = $this->repo->merchant->findOrFailPublic($merchantId)->toArray();

        $filePath = (new Batch\Core())->downloadAndGetFilePath($outputFilePath, $bucketType, $downloadFile);

        if (isset($filePath) === true)
        {
            $this->addFieldsToCSVFile($filePath, $paymentIDDataMap);
        }

        $mailerClass = "\\RZP\\Mail\\Batch\\$type";

        $mail = new $mailerClass(
            $batch,
            $merchant,
            $filePath,
            $settings);


        Mail::send($mail);

        return ['success' => true];
    }
}

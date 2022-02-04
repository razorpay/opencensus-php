<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Batch;
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

        foreach ($fraudEntities as $fraudEntity)
        {
            $payment = $this->repo->payment->findOrFailPublic($fraudEntity->getPaymentId());

            $fraudRowResult = Processor::getFraudNotificationRowData($payment, $fraudEntity);

            $aggregatedData[$payment->getMerchantId()] []= $fraudRowResult;
        }

         (new Freshdesk(null, $batchId))->notify($aggregatedData);

        // Notify Risk Team
        return (new Batch\Core())->sendMail($input);
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

            $row[Constants::BATCH_KEY_REPORTED_TO_ISSUER_AT] =
                $this->getUnixTimestampFromExcelTimestamp((int)$row[Constants::BATCH_KEY_REPORTED_TO_ISSUER_AT]);
        }
        else
        {
            $row[Fraud\Entity::SOURCE] = Constants::VISA_FRAUD_FILE_SOURCE;
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

        foreach ($input as $row)
        {
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

                $this->setFraudIdAndStatus($rowOutput, $fraudEntity->getId(), $isEntityCreated);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Logger::ERROR, TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ERROR);

                $rowOutput[Header::FRAUD_OUTPUT_HEADER_ERROR_REASON] = $e->getMessage();
            }

            $output->push($rowOutput);
        }

        $outputArray = $output->toArrayWithItems();

        $this->trace->info(TraceCode::PAYMENT_FRAUD_BATCH_OUTPUT,  $outputArray);

        return $outputArray;
    }
}

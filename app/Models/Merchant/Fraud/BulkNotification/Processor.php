<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Gateway\Hitachi;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Gateway\Paysecure;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Fraud;
use RZP\Gateway\Upi\Base as Upi;
use RZP\Models\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Netbanking\Base as Netbanking;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property Entity entity
 */

class Processor extends Base\Core
{
    use FileHandlerTrait;

    public function __construct(Entity $entity)
    {
        parent::__construct();

        $this->entity = $entity;
    }

    protected function processForVisaAndMastercard($data, $headers, $fileSource)
    {
        $batchCsvInput = [];

        foreach ($data as $row)
        {
            $batchCsvInput []= $this->processRowForVisaOrMastercard($row, $headers, $fileSource);
        }

        $url = $this->createCsvFile($batchCsvInput, 'fraud_report', null, 'files/batch');

        $uploadedFile = new UploadedFile(
            $url,
            'fraud_report.csv',
            'text/csv',
            null,
            true);

        $params = [
            'file'  => $uploadedFile,
            'type'  => Constants::BATCH_TYPE_CREATE_PAYMENT_FRAUD,
        ];

        $batchResult = (new Batch\Core)->create($params, (new Merchant\Core())->get('100000Razorpay'));

        return sprintf(Constants::BATCH_URL_TPL, $batchResult['id']);
    }

    protected function processForBuyerRisk($data, $headers)
    {
        [$aggregatedData, $output] = $this->aggregateData($data, $headers);

        // send outbound emails with fd
        (new Freshdesk($this->entity))->notify($aggregatedData, $output);

        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_OUTPUT, [
            'output' => $output,
        ]);

        // save output file
        $outputTable = $this->getOutputTableFromOutputMap($output);

        return (new File())->saveFile($outputTable, $this->entity->getId() . '_output', $this->entity)->getSignedUrl()['url'];
    }

    public function process(array $data, array $headers, $fileSource)
    {
        switch ($fileSource)
        {
            case Constants::FILE_SOURCE_BUYER_RISK:
                return $this->processForBuyerRisk($data, $headers);
            case Constants::FILE_SOURCE_VISA:
            case Constants::FILE_SOURCE_MASTERCARD:
                return $this->processForVisaAndMastercard($data, $headers, $fileSource);
            default:
                throw new \Exception(sprintf('Unexpected file source %s.', $fileSource));
        }
    }

    public function aggregateData(array $data, array $headers): array
    {
        $this->trace->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_AGGREGATION_STARTED, [
            'entity_id' => $this->entity->getId(),
        ]);

        $aggregatedData = [];

        $output = [];

        foreach ($data as $row)
        {
            $this->processRow($row, $headers, $aggregatedData, $output);
        }

        $this->trace->info(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_AGGREGATION_ENDED, [
            'entity_id'       => $this->entity->getId(),
            'output_data'     => $output,
            'aggregated_data' => $aggregatedData,
        ]);

        return [$aggregatedData, $output];
    }

    protected function saveFraudEntityForBuyerRisk($rowMap, $payment): array
    {
        return (new Fraud\Core())->createOrUpdateFraudEntity([
            Fraud\Entity::ARN                           => $rowMap[Constants::INPUT_KEY_ARN],
            Fraud\Entity::PAYMENT_ID                    => $payment->getId(),
            Fraud\Entity::AMOUNT                        => $payment->getAmount(),
            Fraud\Entity::BASE_AMOUNT                   => $payment->getBaseAmount(),
            Fraud\Entity::CURRENCY                      => $payment->getCurrency(),
            Fraud\Entity::TYPE                          => $rowMap[Constants::INPUT_KEY_TYPE],
            Fraud\Entity::REPORTED_TO_RAZORPAY_AT       => Carbon::createFromFormat('d/m/Y', $rowMap[Constants::INPUT_KEY_REPORTED_TO_RAZORPAY_AT])->getTimestamp(),
            Fraud\Entity::REPORTED_BY                   => $rowMap[Constants::INPUT_KEY_REPORTED_BY],
        ]);
    }

    public static function getFraudNotificationRowData($payment, $fraud): array
    {
        $source = null;
        if (in_array($fraud->getReportedBy(), Constants::BANK_SOURCES, true) === true)
        {
            $source = Constants::SOURCE_BANK;
        }
        else if (in_array($fraud->getReportedBy(), Constants::CYBERCELL_SOURCES, true) === true)
        {
            $source = Constants::SOURCE_CYBERCELL;
        }

        $orderReceipt = '';
        if ($payment->hasOrder() === true)
        {
            $orderReceipt = $payment->order->getReceipt();
        }

        $respondBy = Carbon::createFromTimestamp($fraud->getReportedToRazorpayAt());

        $curTimestamp = Carbon::now(Timezone::IST);
        if ($curTimestamp->greaterThan($respondBy))
        {
            $respondBy = $curTimestamp;
        }

        $respondBy = $respondBy->addDay()->timezone(Timezone::IST)->format('d/m/Y');

        // use the original currency amount converted to the respective higher unit
        $currency = $payment->getCurrency();
        $originalAmount = (float) ($payment->getAmount() / Currency\Currency::getDenomination($currency));
        $formatAmount = number_format($originalAmount, 2, '.', '');

        return [
            Constants::MERCHANT_DATA_KEY_NOTES                  => json_encode($payment->getNotes()),
            Constants::MERCHANT_DATA_KEY_AMOUNT                 => $formatAmount,
            Constants::MERCHANT_DATA_KEY_RESPOND_BY             => $respondBy,
            Constants::MERCHANT_DATA_KEY_PAYMENT_ID             => $payment->getPublicId(),
            Constants::MERCHANT_DATA_KEY_ORDER_RECEIPT          => $orderReceipt,
            Constants::MERCHANT_DATA_KEY_CUSTOMER_CONTACT       => $payment->getContact(),
            Constants::MERCHANT_DATA_KEY_TRANSACTION_DATE       => $payment->getDateInFormatDMY(Payment\Entity::CREATED_AT),
            Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION => $source,
            Constants::MERCHANT_DATA_KEY_CURRENCY               => $payment->getCurrency(),
        ];
    }

    // aggregatedData = merchant_id => list([payment_id, transaction_date, amount, source_of_notification, respond_by, notes, customer_contact, order_receipt])
    // output => [arn, payment_id, merchant_id, fd_ticket_id, error]
    public function processRow(array $row, array $headers, array &$aggregatedData, array &$output)
    {
        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ROW_PROCESS_STARTED, ['row' => $row]);

        $rowMap = $this->getMapFromRow($row, $headers);

        $rowOutput = [
            Constants::OUTPUT_KEY_ARN          => $rowMap[Constants::INPUT_KEY_ARN],
            Constants::OUTPUT_KEY_PAYMENT_ID   => $rowMap[Constants::INPUT_KEY_PAYMENT_ID],
            Constants::OUTPUT_KEY_MERCHANT_ID  => '',
            Constants::OUTPUT_KEY_FD_TICKET_ID => '',
            Constants::OUTPUT_KEY_ERROR        => '',
        ];

        try
        {
            $sendMailKey = $rowMap[Constants::BATCH_KEY_SEND_MAIL] ?? 'Y'; // 'Y' -> Yes 'N' -> No

            $shouldDisableNotification = $sendMailKey === 'N';

            unset($rowMap[Constants::BATCH_KEY_SEND_MAIL]);

            (new Validator())->validateInput('row', $rowMap);

            $paymentId = $this->getPaymentId($rowMap);

            /** @var Payment\Entity $payment */
            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchantId = $payment->getMerchantId();

            $rowOutput[Constants::OUTPUT_KEY_MERCHANT_ID] = $merchantId;

            [$isEntityCreated, $fraudEntity] = $this->saveFraudEntityForBuyerRisk($rowMap, $payment);

            $fraudRowResult = self::getFraudNotificationRowData($payment, $fraudEntity);

            $aggregatedData[$merchantId] [] = $fraudRowResult;

            if ($shouldDisableNotification === true)
            {
                $redisKey = (new Freshdesk($this->entity))->getSkipNotificationForBatchRediskKey();

                $this->app['redis']->sadd($redisKey, $merchantId);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ERROR);

            $rowOutput[Constants::OUTPUT_KEY_ERROR] = $e->getMessage();
        }
        finally
        {
            if (isset($merchantId) === true)
            {
                $output[$merchantId] [] = $rowOutput;
            }
            else
            {
                $output['-'] [] = $rowOutput;
            }
        }
    }

    protected function mapKeysFromFraudSourceMap(&$rowOutput, $sourceMap, $rowMap)
    {
        foreach ($sourceMap as $inputKey => $outputKey)
        {
            try
            {
                $rowOutput[$inputKey] = $rowMap[$outputKey] ?? '';

                if ($inputKey === Constants::BATCH_KEY_AMOUNT)
                {
                    $rowOutput[$inputKey] *= 100;
                }
            }
            catch (\Throwable $e)
            {
                $rowOutput[$inputKey] = '';
            }
        }
    }

    protected function fetchRrnFromArnIfApplicable(&$rowOutput, $fileSource)
    {
        if ($fileSource === Constants::FILE_SOURCE_MASTERCARD and strlen($rowOutput[Constants::BATCH_KEY_ERROR_REASON]) === 0)
        {
            try
            {
                $rowOutput[Constants::BATCH_KEY_RRN] = sprintf('00%s', substr($rowOutput[Constants::BATCH_KEY_ARN], 12, 10));
            }
            catch (\Throwable $e)
            {
                $rowOutput[Constants::BATCH_KEY_RRN] = '';

                $rowOutput[Constants::BATCH_KEY_ERROR_REASON] = Constants::FRAUD_ERROR_REASON_ARN_TO_RRN;
            }
        }
    }

    protected function getDefaultRowOutputValues($fileSource): array
    {
        return [
            Constants::BATCH_KEY_RRN                    =>  '',
            Constants::BATCH_KEY_ARN                    =>  '',
            Constants::BATCH_KEY_TYPE                   =>  '',
            Constants::BATCH_KEY_SUB_TYPE               =>  '',
            Constants::BATCH_KEY_AMOUNT                 =>  '',
            Constants::BATCH_KEY_BASE_AMOUNT            =>  '',
            Constants::BATCH_KEY_REPORTED_TO_ISSUER_AT  =>  '',
            Constants::BATCH_KEY_CHARGEBACK_CODE        =>  '',
            Constants::BATCH_KEY_ERROR_REASON           =>  '',
            Constants::BATCH_KEY_CURRENCY               =>  Currency\Currency::USD,
            Constants::BATCH_KEY_REPORTED_BY            =>  $fileSource == Constants::FILE_SOURCE_VISA
                ? Constants::REPORTED_BY_VISA
                : Constants::REPORTED_BY_MASTERCARD,
            Constants::BATCH_KEY_SEND_MAIL              => 'Y',
        ];
    }

    protected function getOutputRowForVisaOrMastercard($rowMap, $fileSource): array
    {
        $rowOutput = $this->getDefaultRowOutputValues($fileSource);

        $rowOutput = $this->transformReportedToFields($rowOutput, $rowMap, $fileSource);

        $sourceMap = ($fileSource === Constants::FILE_SOURCE_VISA) ? Constants::VISA_MAP : Constants::MASTERCARD_MAP;

        $this->mapKeysFromFraudSourceMap($rowOutput, $sourceMap, $rowMap);

        if (strlen($rowOutput[Constants::BATCH_KEY_ARN]) === 0)
        {
            $rowOutput[Constants::BATCH_KEY_ERROR_REASON] = Constants::FRAUD_ERROR_REASON_ARN_NOT_FOUND;

            return $rowOutput;
        }

        if (strlen($rowOutput[Constants::BATCH_KEY_AMOUNT]) > 0)
        {
            $rowOutput[Constants::BATCH_KEY_BASE_AMOUNT] = (new Currency\Core)->getBaseAmount(
                $rowOutput[Constants::BATCH_KEY_AMOUNT],
                $rowOutput[Constants::BATCH_KEY_CURRENCY]);
        }

        $this->fetchRrnFromArnIfApplicable($rowOutput, $fileSource);

        return $rowOutput;
    }

    public function processRowForVisaOrMastercard(array $row, array $headers, $fileSource): array
    {
        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ROW_PROCESS_STARTED, ['row' => $row]);

        $rowMap = $this->getMapFromRow($row, $headers);

        $rowOutput = [];

        try
        {
            $rowOutput = $this->getOutputRowForVisaOrMastercard($rowMap, $fileSource);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Logger::ERROR, TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_ERROR);

            $rowOutput[Constants::BATCH_KEY_ERROR_REASON] = $e->getMessage();
        }

        return $rowOutput;
    }

    public function getMapFromRow(array $row, array $headers): array
    {
        $rowMap = [];

        foreach ($headers as $index => $headerName)
        {
            $rowMap[$headerName] = $row[$index];
        }

        return $rowMap;
    }

    /**
     * @throws \Exception
     */
    public function getPaymentId(array $row): string
    {
        // Logic: https://docs.google.com/spreadsheets/d/1WaBeh1Ov8pxHhORccszf6u3FRYM5bKUhN0LrKUQ-GJ0/edit#gid=0
        if (isset($row[Constants::INPUT_KEY_ARN]))
        {
            $arn = $row[Constants::INPUT_KEY_ARN];

            switch ($row[Constants::INPUT_KEY_PAYMENT_METHOD])
            {
                case Method::CARD:
                    /** @var Hitachi\Entity $hitachiEntity */
                    $hitachiEntity = $this->repo->hitachi->getByRrn($arn);
                    if (isset($hitachiEntity) === true)
                    {
                        return $hitachiEntity->getPaymentId();
                    }

                    /** @var Paysecure\Entity $paysecureEntity */
                    $paysecureEntity = $this->repo->paysecure->getByRrn($arn);
                    if (isset($paysecureEntity) === true)
                    {
                        return $paysecureEntity->getPaymentId();
                    }
                    break;

                case Method::UPI:
                    /** @var Upi\Entity $upiEntity */
                    $upiEntity = $this->repo->upi->fetchByNpciReferenceIdOrGatewayPaymentId($arn);
                    if (isset($upiEntity) === true)
                    {
                        return $upiEntity->getPaymentId();
                    }
                    break;

                case Method::BANK_TRANSFER:
                    /** @var BankTransfer\Entity $bankTransferEntity */
                    $bankTransferEntity = $this->repo->bank_transfer->findByUtr($arn);
                    if (isset($bankTransferEntity) === true)
                    {
                        return $bankTransferEntity->getPaymentId();
                    }
                    break;

                case Method::NETBANKING:
                    /** @var Netbanking\Entity $netbankingEntity */
                    $netbankingEntity = $this->repo->netbanking->findByGatewayPaymentId($arn);
                    if (isset($netbankingEntity) === true)
                    {
                        return $netbankingEntity->getPaymentId();
                    }
                    break;
            }
        }

        if (isset($row[Constants::INPUT_KEY_PAYMENT_ID]))
        {
            $publicId = $row[Constants::INPUT_KEY_PAYMENT_ID];

            return Payment\Entity::silentlyStripSign($publicId);
        }

        $message = 'Could not resolve payment_id';

        throw new \Exception($message);
    }

    public function getOutputTableFromOutputMap(array $outputMap): array
    {
        $outputTable = [];

        foreach (array_values($outputMap) as $merchantOutputMap)
        {
            array_push($outputTable, ...$merchantOutputMap);
        }

        return $outputTable;
    }

    protected function transformReportedToFields($rowOutput, $rowMap, $fileSource)
    {
        $reportedToIssuerAt = false;

        if ($fileSource === Constants::FILE_SOURCE_VISA)
        {
            $fraudPostDate= $rowMap['Fraud Post Date'];

            $reportedToIssuerAt = $fraudPostDate;

            if (is_string($fraudPostDate) === true)
            {
                $reportedToIssuerAt  = strtotime($fraudPostDate);
            }
        }
        else if ($fileSource === Constants::FILE_SOURCE_MASTERCARD)
        {
            $enteredDate = $rowMap['Date (Entered Date)'];

            $reportedToIssuerAt = $enteredDate;

            if (is_string($enteredDate) === true)
            {
                $reportedToIssuerAt  = strtotime($enteredDate);
            }
        }

        if ($reportedToIssuerAt !==  false)
        {
            $rowOutput[Fraud\Entity::REPORTED_TO_ISSUER_AT] = $reportedToIssuerAt;
        }

        if (isset($rowMap[Fraud\Entity::REPORTED_TO_RAZORPAY_AT]) === true)
        {
            if (is_string($rowMap[Fraud\Entity::REPORTED_TO_RAZORPAY_AT]) === true)
            {
                $rowOutput[Fraud\Entity::REPORTED_TO_RAZORPAY_AT] = strtotime($rowMap[Fraud\Entity::REPORTED_TO_RAZORPAY_AT]);
            }
            else
            {
                $rowOutput[Fraud\Entity::REPORTED_TO_RAZORPAY_AT] = $rowMap[Fraud\Entity::REPORTED_TO_RAZORPAY_AT];
            }
        }
        else
        {
            $rowOutput[Fraud\Entity::REPORTED_TO_RAZORPAY_AT] = '';
        }


        return $rowOutput;
    }
}

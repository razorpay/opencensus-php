<?php

namespace RZP\Models\Merchant\Fraud\BulkNotification;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Gateway\Hitachi;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Gateway\Paysecure;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer;
use RZP\Models\Payment\Method;
use RZP\Gateway\Upi\Base as Upi;
use RZP\Models\FileStore\Creator;
use RZP\Gateway\Netbanking\Base as Netbanking;

/**
 * @property Entity entity
 */

class Processor extends Base\Core
{
    public function __construct(Entity $entity)
    {
        parent::__construct();

        $this->entity = $entity;
    }

    public function process(array $data, array $headers): Creator
    {
        list($aggregatedData, $output) = $this->aggregateData($data, $headers);

        // send outbound emails with fd
        (new Freshdesk($this->entity))->notify($aggregatedData, $output);

        $this->trace->debug(TraceCode::MERCHANT_BULK_FRAUD_NOTIFICATION_OUTPUT, [
            'output' => $output,
        ]);

        // save output file
        $outputTable = $this->getOutputTableFromOutputMap($output);

        return (new File())->saveFile($outputTable, $this->entity->getId() . '_output', $this->entity);
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
            (new Validator())->validateInput('row', $rowMap);

            $paymentId = $this->getPaymentId($rowMap);

            /** @var Payment\Entity $payment */
            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchantId = $payment->getMerchantId();

            $rowOutput[Constants::OUTPUT_KEY_MERCHANT_ID] = $merchantId;

            $source = null;
            if (in_array($rowMap[Constants::INPUT_KEY_REPORTED_BY], Constants::BANK_SOURCES, true) === true)
            {
                $source = Constants::SOURCE_BANK;
            }
            else if (in_array($rowMap[Constants::INPUT_KEY_REPORTED_BY], Constants::CYBERCELL_SOURCES, true) === true)
            {
                $source = Constants::SOURCE_CYBERCELL;
            }

            $orderReceipt = '';
            if ($payment->hasOrder() === true)
            {
                $orderReceipt = $payment->order->getReceipt();
            }

            $respondBy = Carbon::createFromFormat('d/m/Y H:i:s', $rowMap[Constants::INPUT_KEY_REPORTED_TO_RAZORPAY_AT] . ' 00:00:00', Timezone::IST);

            $curTimestamp = Carbon::now(Timezone::IST);
            if ($curTimestamp->greaterThan($respondBy))
            {
                $respondBy = $curTimestamp;
            }

            $respondBy = $respondBy->addDay()->timezone(Timezone::IST)->format('d/m/Y');

            $result = [
                Constants::MERCHANT_DATA_KEY_NOTES                  => json_encode($payment->getNotes()),
                Constants::MERCHANT_DATA_KEY_AMOUNT                 => $payment->getAmount() / 100,
                Constants::MERCHANT_DATA_KEY_RESPOND_BY             => $respondBy,
                Constants::MERCHANT_DATA_KEY_PAYMENT_ID             => $payment->getPublicId(),
                Constants::MERCHANT_DATA_KEY_ORDER_RECEIPT          => $orderReceipt,
                Constants::MERCHANT_DATA_KEY_CUSTOMER_CONTACT       => $payment->getContact(),
                Constants::MERCHANT_DATA_KEY_TRANSACTION_DATE       => $payment->getDateInFormatDMY(Payment\Entity::CREATED_AT),
                Constants::MERCHANT_DATA_KEY_SOURCE_OF_NOTIFICATION => $source,
            ];

            $aggregatedData[$merchantId] [] = $result;
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
}

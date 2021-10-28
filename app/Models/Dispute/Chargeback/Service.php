<?php

namespace RZP\Models\Dispute\Chargeback;

use DateTime;
use Carbon\Carbon;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Http\RequestHeader;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Models\Dispute\Status as DisputeStatus;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use \RZP\Models\Payment\Service as PaymentService;
use \RZP\Models\Dispute\Service as DisputeService;
use RZP\Models\Dispute\Reason\Entity as ReasonEntity;
use RZP\Models\Dispute\Reason\Service as ReasonService;
use RZP\Models\Merchant\Detail\Service as MerchantService;

class Service extends Base\Service
{

    public function postBatchChargebackAutomation($input, $gateway)
    {
        $funcName = $gateway . 'Dispute';

        return $this->$funcName($input);
    }

    public function getDisputeInput($row, $payment, $isMswipe)
    {
        $reasonService = (new ReasonService());

        $reason = $reasonService->getReasonAndNetworkCode(empty($row[Constants::INPUT_COLUMN_HEADING_REASON_CODE]) === false ?
                                                              $row[Constants::INPUT_COLUMN_HEADING_REASON_CODE] : "",
                                                          empty($row[Constants::INPUT_COLUMN_HEADING_NETWORK]) === false ?
                                                              $row[Constants::INPUT_COLUMN_HEADING_NETWORK] : "");

        $fulfilment_tat = DateTime::createFromFormat('d/m/Y', $row[Constants::INPUT_COLUMN_HEADING_FULFILMENT_TAT]);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_EXCEPTION, [
            "fulfilment_tat" => strtotime($fulfilment_tat->format('Y-m-d')),
        ]);

        return [
            $payment->getPublicId(),
            $row[Constants::INPUT_COLUMN_HEADING_RRN],
            Constants::DISPUTE_STATUS_OPEN,
            $reason[ReasonEntity::NETWORK_CODE],
            $reason[ReasonEntity::REASON_CODE],
            Constants::DISPUTY_TYPE_EXPANSIONS[$row[Constants::INPUT_COLUMN_DISPUTE_TYPE]],
            Carbon::now(Timezone::IST)->format('d/m/Y'),
            $this->getExpiryDate($row[Constants::INPUT_COLUMN_DISPUTE_TYPE]),
            doubleval($row[Constants::INPUT_COLUMN_HEADING_AMT]) * 100,
            array_flip(Currency::ISO_NUMERIC_CODES)[$row[Constants::INPUT_COLUMN_HEADING_CURRENCY]],
            $isMswipe === true ? 'Y' : 'N',
            $row[Constants::INPUT_COLUMN_HEADING_FULFILMENT_TAT],
        ];
    }

    public function getExpiryDate($dispute_type)
    {
        $daysToAdd = 2;

        if ($dispute_type === Constants::DISPUTE_TYPE_CBK || $dispute_type === Constants::DISPUTE_TYPE_RR)
        {
            $daysToAdd = 3;
        }

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $timestamp = $this->addDaysToTimestamp($daysToAdd, $timestamp);

        return date('d/m/Y', $timestamp);
    }

    function isSunday($date)
    {
        $date = Carbon::createFromTimestamp($date, Timezone::IST);

        $weekDay = $date->dayOfWeek;

        return ($weekDay === 0);
    }

    private function convertRowsToArrayMap(array $inputRows)
    {
        $outputArrays = [];

        for ($i = 1; $i < sizeof($inputRows); $i++)
        {
            $fileInput = [];

            foreach ($inputRows[0] as $key => $value)
            {
                $fileInput[$value] = $inputRows[$i][$key];
            }

            array_push($outputArrays, $fileInput);
        }

        return $outputArrays;
    }

    public function getArnVsTeamNameOfMerchant(array $arnVsPaymentDetails)
    {
        $merchantIds = [];

        foreach ($arnVsPaymentDetails as $paymentDetail)
        {
            array_push($merchantIds, $paymentDetail['merchant_id']);
        }

        $merchantIdVsTeamName = $this->app['salesforce']->getSalesForceTeamNameForMerchantID($merchantIds);

        $addedTeamNameDetails = [];

        foreach ($arnVsPaymentDetails as $key => $value)
        {
            if (empty($arnVsPaymentDetails[$key]) === false &&
                empty($arnVsPaymentDetails[$key]['payment_id']) === false)
            {

                $addedTeamNameDetails[$key] = [
                    'merchant_id'        => $value['merchant_id'],
                    'payment_id'         => $value['payment_id'],
                    Constants::TEAM_NAME => $merchantIdVsTeamName[$value['merchant_id']],
                ];
            }
        }

        return $addedTeamNameDetails;
    }

    public function addDefaultValues(array $chargebackOutputArray, $row)
    {
        $defaultArray = [
            Constants::OUTPUT_COLUMN_HEADING_ARN                      => $row[Constants::INPUT_COLUMN_HEADING_ARN],
            Constants::OUTPUT_COLUMN_HEADING_TRANSACTION_DATE         => $row[Constants::INPUT_COLUMN_HEADING_TXN_DATE],
            Constants::OUTPUT_COLUMN_HEADING_RRN                      => $row[Constants::INPUT_COLUMN_HEADING_RRN],
            Constants::OUTPUT_COLUMN_HEADING_FULFILMENT_DATE          => $row[Constants::INPUT_COLUMN_HEADING_FULFILMENT_TAT],
            Constants::OUTPUT_COLUMN_HEADING_REASON_CODE              => $row[Constants::INPUT_COLUMN_HEADING_REASON_CODE],
            Constants::OUTPUT_COLUMN_HEADING_IDEMPOTENT_ID            => $row['idempotent_id'] ?? null,
            Constants::OUTPUT_COLUMN_HEADING_INITIATION_DATE          => Carbon::now(Timezone::IST)->format('d/m/Y'),
            Constants::OUTPUT_COLUMN_HEADING_INITIATION_STATUS        => "",
            Constants::OUTPUT_COLUMN_HEADING_UPFRONT_DEBIT            => '',
            Constants::OUTPUT_COLUMN_HEADING_STATUS                   => "",
            Constants::OUTPUT_COLUMN_HEADING_STATUS_DATE              => "",
            Constants::OUTPUT_COLUMN_HEADING_AGENT                    => "",
            Constants::OUTPUT_COLUMN_HEADING_TICKTE                   => "",
            Constants::OUTPUT_COLUMN_HEADING_CHARGEBACK_TYPE          => "",
            Constants::OUTPUT_COLUMN_HEADING_PREARB_APPROVAL_STATUS   => "",
            Constants::OUTPUT_COLUMN_HEADING_PREARB_APPROVAL_COMMENTS => "",
            Constants::OUTPUT_COLUMN_HEADING_COMMENTS                 => "",
            Constants::OUTPUT_COLUMN_HEADING_ME_DEADLINE              => "",
            Constants::OUTPUT_COLUMN_HEADING_INITIATION_DATE_2        => "",
            Constants::OUTPUT_COLUMN_HEADING_REASON_CATEGORY          => "",
            Constants::OUTPUT_COLUMN_HEADING_NO_DEBIT_LIST            => "",
        ];

        return array_merge($chargebackOutputArray, $defaultArray);
    }

    public function checkPaymentEligibleToCreateDispute($payment)
    {
        if ($payment->isCaptured())
        {
            return true;
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, 'payment not in captured state');
        }
    }

    protected function sanitizeInput($input)
    {
        $sanitizedInput = [];

        foreach ($input as $row)
        {

            $sanitizedRow = [];

            /* downloading the file in ios and openong it in libre office

            is appending "'" to some column values */
            foreach ($row as $key => $value)
            {
                $sanitizedRow[$key] = trim($value . "", '\'');
            }

            array_push($sanitizedInput, $sanitizedRow);
        }

        return $sanitizedInput;
    }

    protected function getPaymentIdAndTeamName(array $input): array
    {
        $arnVsRrn = [];

        $paymentService = new PaymentService();

        foreach ($input as $row)
        {
           $arnVsRrn [$row[Constants::INPUT_COLUMN_HEADING_ARN]] = $row[Constants::INPUT_COLUMN_HEADING_RRN];
        }

        $arnVsPaymentDetails = $paymentService->getPaymentIdFromARNorRRN(array_keys($arnVsRrn), $arnVsRrn);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_ARN_VS_PAYMENT_DETAILS, [
            "arnVsMidAndPaymentId" => $arnVsPaymentDetails
        ]);

        $arnVsPaymentDetails = $this->getArnVsTeamNameOfMerchant($arnVsPaymentDetails);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_ARN_VS_PAYMENT_DETAILS, [
            "arnVsMidPaymentIdAndTeamName" => $arnVsPaymentDetails
        ]);

        return $arnVsPaymentDetails;
    }

    protected function validateRow($row, $arn, array $arnVsPaymentDetails)
    {
        if ($row[Constants::INPUT_COLUMN_DISPUTE_TYPE] === Constants::DISPUTE_TYPE_GOODFAITH or
            $row[Constants::INPUT_COLUMN_DISPUTE_TYPE] === Constants::DISPUTE_TYPE_CBK_REVERSAL)
        {
            $this->trace->info(TraceCode::DISPUTE_AUTOMATION_EXCEPTION, [
                "arn"    => $arn,
                "reason" => "dispute type CBK reversal or GOODFAITH is not processed"
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "dispute type GOODFAITH is not processed");
        }

        $time = Carbon::now()->getTimestamp();

        $txnDate = DateTime::createFromFormat('d/m/Y', $row['txn_date']);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_TRANSACTION_DATE, [
            "txn_date"           => $txnDate,
            "formatted_txn_date" => strtotime($txnDate->format('Y-m-d')),
            "-120 date"          => strtotime('-120 days', $time)
        ]);

        if (strtotime($txnDate->format('Y-m-d')) < strtotime('-120 days', $time))
        {
            $this->trace->info(TraceCode::DISPUTE_AUTOMATION_EXCEPTION, [
                "arn"    => $arn,
                "reason" => "transaction older that 120 days"
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "transaction older that 120 days");
        }
        if (empty($arnVsPaymentDetails[$arn]) === true or
            empty($arnVsPaymentDetails[$arn]['payment_id']) === true)
        {
            $this->trace->info(TraceCode::DISPUTE_AUTOMATION_EXCEPTION, [
                "arn"        => $arn,
                "arnDetails" => $arnVsPaymentDetails,
                "reason"     => "payment Id not found"
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, "payment Id not found");
        }
    }

    protected function getDisputeInputRows($row, $payment, $isMswipe)
    {
        $disputeInputRows = [];

        array_push($disputeInputRows, [
            DisputeEntity::PAYMENT_ID,
            DisputeEntity::GATEWAY_DISPUTE_ID,
            DisputeEntity::GATEWAY_DISPUTE_STATUS,
            ReasonEntity::NETWORK_CODE,
            ReasonEntity::REASON_CODE,
            DisputeEntity::PHASE,
            DisputeEntity::RAISED_ON,
            DisputeEntity::EXPIRES_ON,
            DisputeEntity::GATEWAY_AMOUNT,
            DisputeEntity::GATEWAY_CURRENCY,
            DisputeEntity::SKIP_EMAIL,
            DisputeEntity::INTERNAL_RESPOND_BY,
        ]);

        array_push($disputeInputRows, $this->getDisputeInput($row, $payment, $isMswipe));

        return $disputeInputRows;
    }

    protected function validateDisputeOutputAndGetKeyValueArray($disputeOutputRows, $arn): array
    {
        $disputeOutputRowArray = $this->convertRowsToArrayMap($disputeOutputRows);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_DISPUTE_OUTPUT_ROWS, [
            "disputeOutputRows" => $disputeOutputRowArray
        ]);

        if (empty($disputeOutputRowArray[0]['errors']) === false)
        {
            $this->trace->info(TraceCode::DISPUTE_AUTOMATION_EXCEPTION, [
                "arn"    => $arn,
                "reason" => "dispute creation error",
                "error"  => $disputeOutputRowArray[0]['errors'],
            ]);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, null, $disputeOutputRowArray[0]['errors']);
        }

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_ARN_VS_DISPUTE_ID, [
            "arn"        => $arn,
            "dispute_id" => $disputeOutputRowArray[0]['rzp_dispute_id'],
        ]);

        return $disputeOutputRowArray;
    }

    /**
     * @param int $daysToAdd
     * @param int $timestamp
     *
     * @return false|int
     */
    protected function addDaysToTimestamp(int $daysToAdd, int $timestamp)
    {
        for ($i = 0; $i < $daysToAdd; $i++)
        {
            $timestamp = strtotime('+1 day', $timestamp);

            if ($this->isSunday($timestamp))
            {
                $timestamp = strtotime('+1 day', $timestamp);
            }
        }

        return $timestamp;
    }

    protected function hitachiDispute($input)
    {
        $chargebacks = new Base\PublicCollection();

        $batchId = null;

        if ($this->app['basicauth']->isBatchApp() === true)
        {
            $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id);
        }

        $input = $this->sanitizeInput($input);

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_BATCH_ID, [
            "input"    => $input,
            "batch_id" => $batchId
        ]);

        $merchantService = new MerchantService();

        $arnVsPaymentDetails = $this->getPaymentIdAndTeamName($input);

        $finalOutputArray = [];

        foreach ($input as $row)
        {
            $finalOutputArray = $this->addDefaultValues($finalOutputArray, $row);

            $arn = $row['arn'];

            try
            {
                $this->validateRow($row, $arn, $arnVsPaymentDetails);

                $payment = $this->repo->payment->findOrFailPublic($arnVsPaymentDetails[$arn]['payment_id']);

                $isMswipe = $merchantService->isMidBelongsToMswipe($payment[PaymentEntity::MERCHANT_ID]);

                $this->trace->info(TraceCode::DISPUTE_AUTOMATION_MERCHANT_IS_MSWIPE, [
                    "isMswipe"    => $isMswipe,
                    'merchant_id' => $payment[PaymentEntity::MERCHANT_ID],
                ]);

                $this->checkPaymentEligibleToCreateDispute($payment);

                $merchant = $this->repo->merchant->findOrFailPublic($payment[PaymentEntity::MERCHANT_ID]);

                $disputeService = new DisputeService();

                $disputeInputRows = $this->getDisputeInputRows($row, $payment, $isMswipe);

                $disputeInputRowArray = $this->convertRowsToArrayMap($disputeInputRows);

                $finalOutputArray = array_merge([
                                                    Constants::OUTPUT_COLUMN_HEADING_MERCHANT_DEADLINE         => $disputeInputRowArray[0]['expires_on'],
                                                    Constants::OUTPUT_COLUMN_HEADING_PAYMENT_ID                => $payment[PaymentEntity::ID],
                                                    Constants::OUTPUT_COLUMN_HEADING_REPRESENTMENT_AMOUNT      => $row[Constants::INPUT_COLUMN_HEADING_AMT],
                                                    Constants::OUTPUT_COLUMN_HEADING_CURRENCY                  => $disputeInputRowArray[0]['gateway_currency'],
                                                    Constants::OUTPUT_COLUMN_HEADING_MERCHANT_NAME             => $merchant->getName(),
                                                    Constants::OUTPUT_COLUMN_HEADING_KEY_ACCOUNT               => $arnVsPaymentDetails[$arn][Constants::TEAM_NAME],
                                                    Constants::OUTPUT_COLUMN_HEADING_MERCHANT_ID               => $merchant->getId(),
                                                    Constants::OUTPUT_COLUMN_HEADING_NETWORK                   => $row[Constants::INPUT_COLUMN_HEADING_NETWORK],
                                                    Constants::OUTPUT_COLUMN_HEADING_INTERNATIONAL_TRANSACTION => $payment[PaymentEntity::INTERNATIONAL],
                                                    Constants::OUTPUT_COLUMN_HEADING_TRANSACTION_STATUS        => $payment[PaymentEntity::STATUS],
                                                    Constants::OUTPUT_COLUMN_HEADING_BASE_AMOUNT               => $payment[PaymentEntity::BASE_AMOUNT],
                                                    Constants::OUTPUT_COLUMN_HEADING_WEBSITE                   => $merchant->merchantDetail->getWebsite(),
                                                ], $finalOutputArray);

                $this->trace->info(TraceCode::DISPUTE_AUTOMATION_DISPUTE_INPUT_ROWS, [
                    "disputeInputRows" => $disputeInputRowArray
                ]);

                $disputeOutputRows = $this->repo->transaction(function() use ($disputeService, $disputeInputRows) {

                    return $disputeService->createDisputes($disputeInputRows);
                });

                $disputeOutputRowArray = $this->validateDisputeOutputAndGetKeyValueArray($disputeOutputRows, $arn);

                $finalOutputArray['Dispute ID'] = $disputeOutputRowArray[0]['rzp_dispute_id'];

                $finalOutputArray['success'] = true;

                $this->trace->info(TraceCode::DISPUTE_AUTOMATION_FINAL_OUTPUT_ARRAY, [
                    "finalOutputArray" => $finalOutputArray
                ]);

                $chargebacks->push($finalOutputArray);
            }
            catch (\Exception $e)
            {
                $this->trace->error(TraceCode::DISPUTE_AUTOMATION_ERROR_ROW, [
                    Error::DESCRIPTION       => $e->getMessage(),
                    Error::PUBLIC_ERROR_CODE => $e->getCode(),
                    'arn'                    => $arn
                ]);

                $errorRow = array_merge([
                                            'success' => false,
                                            'error'   => [
                                                Error::DESCRIPTION       => $e->getMessage(),
                                                Error::PUBLIC_ERROR_CODE => $e->getCode(),
                                            ]], $finalOutputArray);

                $chargebacks->push($errorRow);
            }

            $finalOutputArray = [];
        }

        $this->trace->info(TraceCode::DISPUTE_AUTOMATION_FINAL_OUTPUT, [
            "charegebackFinalResult" => $chargebacks
        ]);

        return $chargebacks->toArrayWithItems();
    }
}
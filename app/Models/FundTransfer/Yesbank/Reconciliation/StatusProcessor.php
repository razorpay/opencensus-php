<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Yesbank\Mode;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Attempt\Status as FundTransferStatus;
use RZP\Models\FundTransfer\Yesbank\Request\Status as StatusRequest;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class StatusProcessor extends BaseRowProcessor
{
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const PAYMENT_DATE          = 'payment_date';
    const REMARK                = 'remark';
    const PAYMENT_REF_NO        = 'payment_ref_no';
    const RRN                   = 'rrn';
    const REFERENCE_NUMBER      = 'reference_number';
    const MODE                  = 'mode';
    const PUBLIC_FAILURE_REASON = 'public_failure_reason';

    /**
     * This will update the status based on the transfer API response
     *
     * @return null
     * @throws LogicException
     */
    public function updateTransferStatus()
    {
        $this->setParsedData($this->row);

        $this->fetchEntities();

        $this->updateEntities();

        return $this->reconEntity;
    }

    /**
     * for API based status check parse row will make status request and formats the response as required
     *
     * {@inheritdoc}
     */
    protected function processRow()
    {
        $gateway = ($this->row->hasVpa() === true);

        $banking = $this->row->isOfBanking();

        $makeRequest = $this->shouldMakeStatusRequestCall($gateway);

        $statusRequestProcessor = (new StatusRequest($banking))->init()
                                                               ->setEntity($this->row);

        if ($makeRequest === true)
        {
            $response = $statusRequestProcessor->makeRequest($gateway);
        }
        else
        {
            $response = $statusRequestProcessor->getResponseDataFromFta($gateway);
        }

        if (empty($response) === false)
        {
            $this->setParsedData($response);
        }
    }

    protected function shouldMakeStatusRequestCall(bool $gateway): bool
    {
        //
        // We should not make status call only for VPA payouts since
        // Yesbank's Status API call does not work correctly.
        //
        if ($gateway === false)
        {
            return true;
        }

        $fta = $this->row;

        $bankCode = $fta->getBankStatusCode();

        $successStatuses = GatewayStatus::getSuccessfulStatus();
        $failureStatuses = GatewayStatus::getFailureStatus();

        // TODO: Fix this later properly. Use status_code
        // to figure out whether to retry or not.

        return true;
    }

    /**
     * @param array $response
     * @throws LogicException
     */
    protected function setParsedData(array $response)
    {
        $this->reconEntityId = $response[self::PAYMENT_REF_NO];

        if ($this->reconEntityId === null)
        {
            throw new LogicException(
                "Recon entity id can not be null",
                ErrorCode::SERVER_ERROR_INVALID_ATTEMPT_ID,
                [
                    'response' => $response,
                ]);
        }

        // TODO: Use yesbank/transfer/request.php while reading from the response.

        $this->parsedData = [
            self::UTR                   => $response[self::UTR],
            self::BANK_STATUS_CODE      => $response[self::BANK_STATUS_CODE],
            self::REMARK                => $response[self::REMARK],
            self::PAYMENT_DATE          => $response[self::PAYMENT_DATE],
            self::REFERENCE_NUMBER      => $response[self::REFERENCE_NUMBER],
            self::MODE                  => Mode::getInternalModeFromExternalMode($response[self::MODE]),
            // Won't be present in case of a successful response
            self::PUBLIC_FAILURE_REASON => $response[self::PUBLIC_FAILURE_REASON] ?? null,
        ];

        $this->trace->info(
            TraceCode::FTA_RECON_PARSED_DATA,
            [
                'parsed_data' => $this->parsedData
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $currentStatus = $this->reconEntity->getBankStatusCode();

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[self::PAYMENT_DATE]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARK]);

        $this->reconEntity->setMode($this->parsedData[self::MODE]);

        if ($this->parsedData[self::BANK_STATUS_CODE] !== $currentStatus)
        {
            $this->reconEntity->setStatus(AttemptStatus::INITIATED);
        }

        //
        // Reference number is only available in transfer request's response.
        // It is null in status request's response.
        //
        if (empty($this->parsedData[self::REFERENCE_NUMBER]) === false)
        {
            $this->reconEntity->setCmsRefNo($this->parsedData[self::REFERENCE_NUMBER]);
        }

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[self::UTR];
    }

    protected function updateVerifyReconEntity()
    {
        $currentStatus = $this->reconEntity->getBankStatusCode();

        if ($this->parsedData[self::BANK_STATUS_CODE] === $currentStatus)
        {
           return;
        }

        $this->reconEntity->setStatus(FundTransferStatus::INITIATED);

        $this->updateReconEntity();
    }
}

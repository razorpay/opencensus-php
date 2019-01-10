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
    const STATUS_CODE           = 'status_code';
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
            // We are doing this only so that we keep the flow consistent
            // with when we actually make the status request.
            // Otherwise, ideally, doing this should not be required at all.
            $response = $statusRequestProcessor->getResponseDataFromFta($this->row);
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

        $statusCode = $fta->getBankResponseCode();

        //
        // Yesbank status call for VPA does not work properly.
        // Gives the wrong error codes and stuff, which are not documented.
        // Hence, if we already got a status saying it's success or failed
        // we don't want to make the status request and mess up the data
        // that we got from `initiate` call.
        //
        // We make the status call only if current state of the FTA is
        // either pending, timeout or we don't know (empty status_code)
        //
        if (($statusCode === GatewayStatus::STATUS_CODE_PENDING) or
            ($statusCode === GatewayStatus::STATUS_CODE_TIMEOUT) or
            (empty($statusCode) === true))
        {
            return true;
        }

        return false;
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
            // `status_code` will be present only for vpa ones. not the normal ones.
            self::STATUS_CODE           => $response[self::STATUS_CODE] ?? null,
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

        $this->reconEntity->setBankResponseCode($this->parsedData[self::STATUS_CODE]);

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

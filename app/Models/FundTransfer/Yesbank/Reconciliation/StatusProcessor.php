<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use Cache;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Yesbank\Mode;
use RZP\Models\FundTransfer\Base\Reconciliation\Constants;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Attempt\Status as FundTransferStatus;
use RZP\Models\FundTransfer\Yesbank\Request\Status as StatusRequest;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class StatusProcessor extends BaseRowProcessor
{
    const NOT_FOUND   = 'ns:E404';
    const TIME_OFFSET = 180;
    const FTA_INITIATED = '{fta}_status_initiated';

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
        $gateway = $this->row->shouldUseGateway($this->row->getMode());

        $type = $this->getRequestType($this->row);

        $makeRequest = $this->shouldMakeStatusRequestCall($gateway);

        $useCurrentAccount = $this->row->merchant->isFeatureEnabled(Feature\Constants::DUMMY);

        $statusRequestProcessor = (new StatusRequest($type, $useCurrentAccount))->init()
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
        $this->reconEntityId = $response[Constants::PAYMENT_REF_NO] ?? null;

        if ($this->reconEntityId === null)
        {
            throw new LogicException(
                'Recon entity id can not be null',
                ErrorCode::SERVER_ERROR_INVALID_ATTEMPT_ID,
                [
                    'response' => $response,
                ]);
        }

        // TODO: Use yesbank/transfer/request.php while reading from the response.

        $this->parsedData = [
            Constants::UTR                   => $response[Constants::UTR],
            // `status_code` will be present only for vpa ones. not the normal ones.
            Constants::STATUS_CODE           => $response[Constants::STATUS_CODE] ?? null,
            Constants::BANK_STATUS_CODE      => $response[Constants::BANK_STATUS_CODE],
            Constants::REMARKS               => $response[Constants::REMARKS],
            Constants::PAYMENT_DATE          => $response[Constants::PAYMENT_DATE],
            Constants::REFERENCE_NUMBER      => $response[Constants::REFERENCE_NUMBER],
            Constants::MODE                  => Mode::getInternalModeFromExternalMode($response[Constants::MODE]),
            Constants::BANK_RESPONSE_CODE    => $response[Constants::BANK_SUB_STATUS_CODE] ?? null,
            // Won't be present in case of a successful response
            Constants::PUBLIC_FAILURE_REASON => $response[Constants::PUBLIC_FAILURE_REASON] ?? null,
            Constants::NAME_WITH_BENE_BANK   => $this->row[Constants::NAME_WITH_BENE_BANK] ?? null,
        ];

        $this->trace->info(
            TraceCode::FTA_RECON_PARSED_DATA,
            [
                'parsed_data'   => $this->parsedData,
                'fta_id'        => $this->reconEntityId,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $currentStatusCode = $this->reconEntity->getBankStatusCode();

        $cacheKey = self::FTA_INITIATED . '_' . $this->reconEntity->getId();

        $this->reconEntity->setBankStatusCode($this->parsedData[Constants::BANK_STATUS_CODE]);

        // in case of bank response code ns:E404 and
        // if its initiated not more than 180sec ago then don't update the status
        // this is because bank might give the status bit later
        if (($this->parsedData[Constants::BANK_RESPONSE_CODE] === self::NOT_FOUND) and
            (Cache::has($cacheKey) === true))
        {
            $this->reconEntity->setBankStatusCode($currentStatusCode);
        }
        else
        {
            $this->reconEntity->setBankStatusCode($this->parsedData[Constants::BANK_STATUS_CODE]);
        }

        $this->reconEntity->setBankResponseCode($this->parsedData[Constants::BANK_RESPONSE_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[Constants::PAYMENT_DATE]);

        $this->reconEntity->setRemarks($this->parsedData[Constants::REMARKS]);

        // Set Mode only if Mode is present in response
        if (empty($this->parsedData[Constants::MODE]) === false)
        {
            $this->reconEntity->setMode($this->parsedData[Constants::MODE]);
        }

        if ($this->parsedData[Constants::BANK_STATUS_CODE] !== $currentStatusCode)
        {
            $this->reconEntity->setStatus(AttemptStatus::INITIATED);
        }

        //
        // Reference number is only available in transfer request's response.
        // It is null in status request's response.
        //
        if (empty($this->parsedData[Constants::REFERENCE_NUMBER]) === false)
        {
            $this->reconEntity->setCmsRefNo($this->parsedData[Constants::REFERENCE_NUMBER]);
        }

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[Constants::UTR];
    }

    protected function updateVerifyReconEntity()
    {
        $currentStatus = $this->reconEntity->getBankStatusCode();

        if ($this->parsedData[Constants::BANK_STATUS_CODE] === $currentStatus)
        {
           return;
        }

        $this->reconEntity->setStatus(FundTransferStatus::INITIATED);

        $this->updateReconEntity();
    }
}

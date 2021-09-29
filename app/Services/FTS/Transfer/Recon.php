<?php

namespace RZP\Services\FTS\Transfer;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Services\FTS\Constants;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Attempt\Entity as TransferAttempt;

trait Recon
{
    /**
     * @var TransferAttempt|null
     */
    protected $fta;

    protected function reconcileFTA(array $input)
    {
        if (array_key_exists(Constants::STATUS, $input) === true)
        {
            $input[Constants::STATUS] = strtolower($input[Constants::STATUS]);
        }

        if (array_key_exists(Constants::FUND_TRANSFER_ID, $input) === true)
        {
            $this->fta = $this->repo
                              ->fund_transfer_attempt
                              ->getAttemptByFTSTransferId($input[Constants::FUND_TRANSFER_ID]);
        }

        if (($this->fta === null) and
          (isset($input[Constants::SOURCE_ID]) === true) and
          (isset($input[Constants::SOURCE_TYPE]) === true))
        {
            $this->fta = $this->repo
                              ->fund_transfer_attempt
                              ->getFTSAttemptBySourceId(
                                $input[Constants::SOURCE_ID],
                                $input[Constants::SOURCE_TYPE],
                                true);
        }

        if ($this->fta === null)
        {
            $this->trace->info(
              TraceCode::FTS_UPDATE_FUND_TRANSFER_ATTEMPT_NOT_FOUND,
              [
                'response' => $input,
              ]);

            return;
        }

        $this->updateFtaWithInput($input);

        return [
          'message' => 'FTA updated successfully',
        ];
    }

    protected function updateFtaWithInput(array $input)
    {
        if (AttemptStatus::isValidStateTransition(
            $this->fta->getStatus(),
            $input[TransferAttempt::STATUS]) === false)
        {
            $this->trace->info(
              TraceCode::FTS_UPDATE_FUND_TRANSFER_ATTEMPT_SKIPPED,
              [
                'current_status'  => $this->fta->getStatus(),
                'received_status' => $input[TransferAttempt::STATUS],
                'message'         => 'webhook update skipped due to invalid state transition',
              ]);

            return;
        }

        $routeName = $this->app['api.route']->getCurrentRouteName();

        if ($routeName !== 'update_payout_status_batch')
        {
            $this->fta->setFTSTransferId($input[Constants::FUND_TRANSFER_ID]);
        }

        if (empty($input[ResponseFields::UTR]) === false)
        {
            $this->fta->setUtr($input[ResponseFields::UTR]);
        }

        if (empty($input[ResponseFields::MODE]) === false)
        {
            $this->fta->setMode($input[ResponseFields::MODE]);
        }

        if (empty($input[ResponseFields::BANK_PROCESSED_TIME]) === false)
        {
            $this->fta->setDateTime($input[ResponseFields::BANK_PROCESSED_TIME]);
        }

        if ((isset($input[ResponseFields::EXTRA_INFO]) === true) and
          (is_array($input[ResponseFields::EXTRA_INFO]) === true))
        {
            $this->updateExtraInfo($input[ResponseFields::EXTRA_INFO]);
        }

        if (empty($input[ResponseFields::FAILURE_REASON]) === false)
        {
            $this->fta->setFailureReason($input[ResponseFields::FAILURE_REASON]);
        }

        if (empty($input[ResponseFields::BANK_STATUS_CODE]) === false)
        {
            $this->fta->setBankStatusCode($input[ResponseFields::BANK_STATUS_CODE]);
        }

        if (empty($input[ResponseFields::REMARKS]) === false)
        {
            $this->fta->setRemarks($input[ResponseFields::REMARKS]);
        }

        if (empty($input[ResponseFields::GATEWAY_REF_NO]) === false)
        {
            $this->fta->setGatewayRefNo($input[ResponseFields::GATEWAY_REF_NO]);
        }

        if (empty($input[Constants::CHANNEL]) === false)
        {
            $this->fta->setChannel(strtolower($input[Constants::CHANNEL]));
        }

        if (empty($input[Constants::STATUS]) === false)
        {
            $this->fta->setStatus($input[Constants::STATUS]);
        }

        $this->repo->fund_transfer_attempt->saveOrFail($this->fta);
    }

    protected function updateExtraInfo(array $info)
    {
        if (isset($info['cms_ref_no']) === true)
        {
            $this->fta->setCmsRefNo($info['cms_ref_no']);
        }
    }
}

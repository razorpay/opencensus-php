<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Base;
use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\FundAccount;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;

class Validator extends Base\Validator
{
    protected static $editRules = [
        Entity::STATUS           => 'sometimes|string|custom',
        Entity::FAILURE_REASON   => 'sometimes|string|max:100',
        Entity::REMARKS          => 'sometimes|string|max:100',
        Entity::BANK_STATUS_CODE => 'sometimes|string|max:30',
        Entity::CHANNEL          => 'sometimes|string|custom',
    ];

    protected static $initiateFundTransferRules = [
        Entity::PURPOSE         => 'required|filled|string|max:30|custom',
        Entity::SOURCE_TYPE     => 'sometimes|filled|string|max:32|in:refund,payout',
        // This will be used while generating response while mock. Only used in api based settlements
        'failed_response'       => 'sometimes|int'
    ];

    protected static $bulkReconcileRules = [
        'from' => 'required_with:to|epoch|date_format:U',
        'to'   => 'required_with:from|epoch|date_format:U',
    ];

    protected static $retryBeamFileUploadRules = [
        'file_id'         => 'required|filled|string|alpha_num|size:14',
        Entity::CHANNEL   => 'required|filled|string',
        Entity::FILE_TYPE => 'required|filled|string',
    ];

    protected static $ftsStatusUpdateRules = [

        Entity::UTR            => 'sometimes|string',
        Entity::STATUS         => 'required|string|custom',
        Entity::REMARKS        => 'sometimes|string',
        Entity::NARRATION      => 'sometimes|string',
        Entity::DATE_TIME      => 'sometimes|string',
        Entity::SOURCE_ID      => 'required|string',
        Entity::FAILURE_REASON => 'sometimes|string',
        'fund_transfer_id'     => 'required|int',
    ];

    protected function validateStatus($attribute, $value)
    {
        if (Status::isValidForBulkUpdate($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid status',
                $attribute,
                $value);
        }
    }

    /**
     * @param string $attribute
     * @param string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateChannel(string $attribute, string $value)
    {
        $channels = [Channel::AXIS, Channel::ICICI, Channel::YESBANK];

        if (in_array($value, $channels, true) !== true)
        {
            throw new BadRequestValidationFailureException('Invalid channel value : ' . $value);
        }
    }

    /**
     * @param string $attribute
     * @param string $value
     * @throws BadRequestValidationFailureException
     */
    public function validateFileType(string $attribute, string $value)
    {
        $fileType = [Entity::BENEFICIARY, Entity::SETTLEMENT];

        if (in_array($value, $fileType, true) !== true)
        {
            throw new BadRequestValidationFailureException('Invalid file type : ' . $value);
        }
    }

    public function validateModeIfSet()
    {
        $attempt = $this->entity;

        if ($attempt->hasMode() === false)
        {
            return;
        }

        $mode = $attempt->getMode();
        $destinationType = $attempt->getDestinationType();

        Mode::validateModeOfAccountType($mode, $destinationType);

        $channel = $attempt->getChannel();

        // If we want to support for other channels, we need to make changes in the channel specific classes
        // for mode related initiations, allowed/not allowed, cron timings, settlement times, etc
        if (($destinationType === Constants\Entity::BANK_ACCOUNT) and
            ($channel !== Channel::YESBANK))
        {
            throw new LogicException(
                'Mode should be sent only for Yesbank',
                ErrorCode::SERVER_ERROR_FTA_MODE_SENT_NON_YESBANK,
                [
                    'attempt_id'    => $attempt->getId(),
                    'mode'          => $mode,
                    'channel'       => $channel,
                ]);
        }

        $amount = $attempt->source->getAmount();

        $minRtgsAmount = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount = NodalAccount::MAX_IMPS_AMOUNT * 100;
        $maxUpiAmount = FundAccount\Validator::MAX_VPA_AMOUNT;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)) or
            (($mode === Mode::UPI) and ($amount > $maxUpiAmount)))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'            => $amount,
                    'mode'              => $mode,
                    'min_rtgs_amount'   => $minRtgsAmount,
                    'max_imps_amount'   => $maxImpsAmount,
                    'attempt_id'        => $attempt->getId(),
                ]);
        }
    }

    protected function validatePurpose($attribute, $value)
    {
        if (Purpose::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid purpose', $attribute);
        }
    }
}

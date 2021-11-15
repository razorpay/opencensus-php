<?php

namespace RZP\Models\FundTransfer\Attempt;

use App;

use RZP\Base;
use RZP\Constants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\FundAccount;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Settlement\Channel;
use RZP\Exception\BadRequestException;
use RZP\Services\FTS\Base as FtsService;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\FundTransfer\Attempt\Constants as FundTransferAttemptConstants;

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
        Entity::PURPOSE         => 'required|filled|string|max:30|in:refund,settlement,penny_testing',
        Entity::SOURCE_TYPE     => 'required|filled|string|max:32|in:refund,payout,settlement,fund_account_validation',
        // This will be used while generating response while mock. Only used in api based settlements
        'failed_response'       => 'sometimes|string',
        'ignore_time_limit'     => 'sometimes|string',
    ];

    protected static $ftaControlRules = [
        Entity::CHANNEL => 'required|string|custom',
        'action'        => 'required|in:enable,disable',
    ];

    protected static $bulkReconcileRules = [
        'from' => 'required_with:to|epoch|date_format:U',
        'to'   => 'required_with:from|epoch|date_format:U',
        'limit'=> 'sometimes|int'
    ];

    protected static $retryBeamFileUploadRules = [
        'file_id'         => 'required|filled|string|alpha_num|size:14',
        Entity::CHANNEL   => 'required|filled|string',
        Entity::FILE_TYPE => 'required|filled|string',
    ];

    protected static $ftsStatusUpdateRules = [
        Entity::UTR                  => 'sometimes|string',
        Entity::STATUS               => 'required|string|custom',
        Entity::REMARKS              => 'sometimes|string',
        Entity::NARRATION            => 'sometimes|string',
        Entity::DATE_TIME            => 'sometimes|string',
        Entity::SOURCE_ID            => 'required_with:source_type|string',
        Entity::SOURCE_TYPE          => 'required_with:source_id|string',
        Entity::FAILURE_REASON       => 'sometimes|string',
        Entity::MODE                 => 'sometimes|string',
        'bank_processed_time'        => 'sometimes|string',
        'fund_transfer_id'           => 'required|int',
        'extra_info'                 => 'sometimes',
        'extra_info.*'               => 'sometimes',
        'return_utr'                 => 'sometimes|string',
        Entity::BANK_STATUS_CODE     => 'sometimes|string',
        Entity::GATEWAY_REF_NO       => 'sometimes|string',
        Entity::GATEWAY_ERROR_CODE   => 'sometimes|string',
        Entity::CHANNEL              => 'sometimes|string',
        Entity::SOURCE_ACCOUNT_ID    => 'sometimes|int',
        Entity::BANK_ACCOUNT_TYPE    => 'sometimes',
        Entity::STATUS_DETAILS       => 'sometimes',
        Entity::REASON               => 'sometimes',
        Entity::PARAMETERS           => 'sometimes',
    ];

    protected  static $ftsFundTransferRules = [
        Entity::ID => 'required_without_all:from,to,limit,size|public_id|size:18',
        'from'     => 'required_with:to,limit|epoch|date_format:U',
        'to'       => 'required_with:from,limit|epoch|date_format:U',
        'limit'    => 'required_with:from,to|int',
        'size'     => 'required_without_all:from,to,limit,id|filled|int',
        'action'   => 'required|filled|custom'
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
        $channels = [Channel::AXIS, Channel::ICICI, Channel::YESBANK, Channel::AXIS2, Channel::RBL, Channel::HDFC];

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

    /**
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws LogicException
     */
    public function validateModeIfSet()
    {
        /** @var Entity $attempt */
        $attempt = $this->entity;

        $destinationType = $attempt->getDestinationType();

        $mode = $attempt->getMode();

        $channel = $attempt->getChannel();

        $app = App::getFacadeRoot();

        if ($attempt->hasMode() === false)
        {
            return;
        }

        if ($destinationType === Constants\Entity::CARD)
        {
            $cardType = $attempt->card->getType();

            $cardIssuer = $attempt->card->getIssuer();

            $iin = $attempt->card->iinRelation;

            if (empty($iin) === false)
            {
                $iinIssuer = $iin->getIssuer();

                if ($iinIssuer !== $cardIssuer)
                {
                    $app['trace']->info(
                        TraceCode::FTA_CARD_IIN_ISSUER_MISMATCH,
                        [
                            'fta_id'      => $attempt->getId(),
                            'iin'         => $iin->getIin(),
                            'card_id'     => $attempt->card->getId(),
                            'card_issuer' => $cardIssuer,
                            'iin_issuer'  => $iin->getIssuer(),
                        ]
                    );

                    // IIN is source of truth
                    $cardIssuer = $iinIssuer;
                }

                // IIN is the source of truth
                $cardType = $iin->getType();
            }

            if (($attempt->card->isAmex() === true) and
                ($cardIssuer === null))
            {
                $cardIssuer = FundTransferAttemptConstants::DEFAULT_ISSUER;
            }

            //
            // Issuer is being directly fetched from IIN entity
            // If IIN entity is not present, then card issuer is null - this has been observed only in the case of
            // Amex Cards. Hence, we are throwing error to check if any other cases have this issue where
            // IIN is not present for the card
            //
            if (empty($cardIssuer) === true)
            {
                throw new BadRequestValidationFailureException("Issuer is null");
            }

            $networkCode = $attempt->card->getNetworkCode();

            if (($cardType === \RZP\Models\Card\Type::DEBIT) or
                ($channel === Channel::M2P))
            {
                //
                // The only supported mode for Fund Transfer to Debit Cards
                //
                $supportedModes = [Mode::CT];

                if (in_array($mode, $supportedModes, true) === false)
                {
                    throw new BadRequestValidationFailureException("$mode is not a valid mode for Debit Cards");
                }
            }
            else
            {
                Mode::validateModeOfIssuer($mode, $cardIssuer, $networkCode);
            }
        }

        $valid = Channel::validateChannelAndMode($channel, $destinationType, $mode);

        if ($valid === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_MODE_NOT_SUPPORTED,
                null,
                [
                    'channel'           => $channel,
                    'mode'              => $mode,
                    'destination_type'  => $destinationType
                ],
                $mode . ' is not supported'
            );
        }

        $amount = $attempt->source->getAmount();

        $minRtgsAmount = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount = NodalAccount::MAX_IMPS_AMOUNT * 100;
        $maxUpiAmount  = FundAccount\Validator::MAX_UPI_AMOUNT;
        $maxAmazonPayAmount = FundAccount\Validator::MAX_WALLET_ACCOUNT_AMAZON_PAY_AMOUNT;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)) or
            (($mode === Mode::UPI) and ($amount > $maxUpiAmount)) or
            (($mode === Mode::AMAZONPAY) and ($amount > $maxAmazonPayAmount)))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FTA_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'                          => $amount,
                    'mode'                            => $mode,
                    'min_rtgs_amount'                 => $minRtgsAmount,
                    'max_imps_amount'                 => $maxImpsAmount,
                    'maxWalletAccountAmazonPayAmount' => $maxAmazonPayAmount,
                    'attempt_id'                      => $attempt->getId(),
                ]);
        }
    }

    protected function validatePurpose($attribute, $value)
    {
        if (Purpose::isValid($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid purpose passed to FTA',
                $attribute,
                [
                    'value' => $value
                ]);
        }
    }

    protected function validateAction($attribute, $value)
    {
        if (FtsService::isValidFtsAction($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid action for FTS',
                $attribute,
                [
                    'value' => $value
                ]);
        }
    }
}

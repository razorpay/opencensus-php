<?php

namespace RZP\Models\Payout;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Card;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Models\FundAccount;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\FundTransfer;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Mode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\FundTransfer\Attempt\Constants;
use RZP\Models\Feature\Repository as FeatureRepo;
use RZP\Models\PayoutSource\Entity as PayoutSource;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\FundTransfer\Base\Initiator\NodalAccount;
use RZP\Models\Workflow\Action\Checker\Entity as ActionChecker;

class Validator extends Base\Validator
{

    // We are increasing this from 100 to 200. Slack thread for reference:
    // https://razorpay.slack.com/archives/C013868TRK4/p1615796447155300?thread_ts=1615544530.147100&cid=C013868TRK4
    // TODO: Finalize on some final number that we wish to support in the long run
    const MAX_PURPOSES_ALLOWED = 200;

    const MAX_PURPOSES_ALLOWED_TO_XPAYROLL = 100;

    /**
     * Rate limit on items sending for bulk payout create.
     */
    const MAX_BULK_PAYOUTS_LIMIT = 15;

    const CALCULATE_ES_ON_DEMAND_FEES = 'calculate_es_on_demand_fees';

    const FUND_ACCOUNT_PAYOUT_COMPOSITE = 'fund_account_payout_composite';

    const BEFORE_CREATE_FUND_ACCOUNT_PAYOUT = 'before_create_fund_account_payout';

    const BEFORE_CREATE_FUND_ACCOUNT_PAYOUT_WITH_OTP = 'before_create_fund_account_payout_with_otp';

    // The max payout amount allowed for merchant payouts is 80 L
    const MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT = 800000000;

    // The max payout amount allowed for merchant payouts on demand is 2 Cr
    const MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT = 2000000000;

    const MIN_TRANSACTION_AMOUNT_ALLOWED_IN_PAISE = 100;

    const APPROVE_PAYOUT_RULES = 'approve_payout';

    const CANCEL_PAYOUT = 'cancel_payout';

    const PROCESS_QUEUED_PAYOUTS_INITIATE = 'process_queued_payouts_initiate';

    // Scheduled Payouts Initiate
    const PROCESS_SCHEDULED_PAYOUTS = 'process_scheduled_payouts';

    const PAYOUT_STATUS_MANUAL = 'payout_status_manual';

    const ACCEPTED_ORIGIN_VALUES = 'accepted_origin_values';

    const VALIDATE_PAYOUT_PURPOSE = 'validate_payout_purpose';

    const PAYOUT_BULK_SAMPLE_FILE = 'payout_bulk_sample_file';

    const PAYOUT_BULK_STATUS_UPDATE_MANUAL = 'payout_bulk_status_update_manual';

    // Payout Service Validations
    const PAYOUT_SERVICE_CREATE                     = 'payout_service_create';
    const PAYOUT_SERVICE_TRANSACTION_CREATE         = 'payout_service_transaction_create';
    const PAYOUT_SERVICE_FTS_CREATE                 = 'payout_service_fts_create';
    const RETRY_PAYOUTS_ON_SERVICE                  = 'retry_payouts_on_service';

    const AMOUNT_REGEX = '/[^0-9]/';

    //
    // This is required for build. Currently, build does not
    // accept ruleName as a parameter. Hence, this list needs
    // to contain the master attributes. We run a different
    // validation for the actual operation.
    //
    protected static $createRules = [
        Entity::PURPOSE              => 'sometimes|string',
        Entity::AMOUNT               => 'sometimes|integer',
        Entity::CURRENCY             => 'sometimes|size:3',
        Entity::NOTES                => 'sometimes|notes',
        Entity::CUSTOMER_ID          => 'sometimes|public_id',
        Entity::DESTINATION          => 'sometimes|public_id',
        Entity::TYPE                 => 'sometimes|string',
        Entity::BALANCE_ID           => 'sometimes|string|size:14',
        Entity::FUND_ACCOUNT_ID      => 'sometimes|public_id',
        Entity::MODE                 => 'sometimes|nullable|string',
        Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION            => 'sometimes|nullable|string|max:30',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
        Entity::IDEMPOTENCY_KEY      => 'sometimes|nullable|string',
        Entity::SCHEDULED_AT         => 'sometimes|filled|epoch',
        Entity::ORIGIN               => 'sometimes|filled',
    ];

    /**
     * @see Payout\Batch\Validator Need to change for payout rules if any changes are done here
     *
     * @var array
     */
    protected static $fundAccountPayoutCompositeRules = [
        Entity::PURPOSE                              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::AMOUNT                               => 'required|integer|min:100|custom',
        Entity::CURRENCY                             => 'required|size:3|in:INR',
        Entity::NOTES                                => 'sometimes|notes',
        Entity::BALANCE_ID                           => 'sometimes|filled|size:14',
        Entity::MODE                                 => 'required|string|custom',
        Entity::REFERENCE_ID                         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION                            => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
        Entity::PAYOUT_LINK_ID                       => 'sometimes|filled|public_id',
        Entity::QUEUE_IF_LOW_BALANCE                 => 'sometimes|filled|boolean',
        Entity::SKIP_WORKFLOW                        => 'filled|boolean',
        Entity::FUND_ACCOUNT                         => 'required|filled|array|custom',
        Entity::FUND_ACCOUNT . "." . Entity::CONTACT => 'required|filled|array',
        Entity::ORIGIN                                             => 'sometimes|filled',
        Entity::SOURCE_DETAILS                                     => 'sometimes|filled|array',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_ID   => 'required|string',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_TYPE => 'required|string|',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::PRIORITY    => 'required|integer|min:1'
    ];

    /**
     * @see Batch\Validator Need to change for payout rules if any changes are done here
     *
     * @see Payout\Batch\Validator A change needed here as well
     *
     * @var array
     */
    protected static $fundAccountPayoutRules = [
        Entity::PURPOSE              => 'required|filled|string|max:30|alpha_dash_space',
        Entity::AMOUNT               => 'required|integer|custom',
        Entity::CURRENCY             => 'required|size:3|in:INR',
        Entity::NOTES                => 'sometimes|notes',
        Entity::BALANCE_ID           => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID      => 'required|public_id',
        Entity::MODE                 => 'required|string|custom',
        Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Entity::NARRATION            => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
        Entity::IDEMPOTENCY_KEY      => 'sometimes|nullable|string',
        Entity::PAYOUT_LINK_ID       => 'sometimes|filled|public_id',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
        Entity::SCHEDULED_AT         => 'sometimes|filled|epoch|custom',
        Entity::ORIGIN               => 'sometimes|filled',
    ];

    protected static $customerWalletPayoutRules = [
        Entity::PURPOSE         => 'sometimes|filled|string|max:30|in:refund',
        Entity::AMOUNT          => 'required|integer|min:100|max:500000000',
        Entity::CURRENCY        => 'required|size:3|in:INR',
        Entity::NOTES           => 'sometimes|notes',
        Entity::BALANCE_ID      => 'sometimes|filled|size:14',
        Entity::FUND_ACCOUNT_ID => 'required|public_id',
        Entity::REFERENCE_ID    => 'sometimes|nullable|string|max:40',
        Entity::NARRATION       => 'sometimes|nullable|string|max:30|regex:/^[a-zA-Z0-9 ]*$/',
    ];

    protected static $beforeCreateFundAccountPayoutRules = [
        Entity::FUND_ACCOUNT_ID                                    => 'required_without:fund_account|public_id',
        Entity::FUND_ACCOUNT                                       => 'required_without:fund_account_id|array',
        Entity::ORIGIN                                             => 'sometimes|filled',
        Entity::SOURCE_DETAILS                                     => 'sometimes|filled|array',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_ID   => 'required|string',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::SOURCE_TYPE => 'required|string|',
        Entity::SOURCE_DETAILS . '.*.' . PayoutSource::PRIORITY    => 'required|integer|min:1',
        Entity::ENABLE_WORKFLOW_FOR_INTERNAL_CONTACT               => 'sometimes|boolean'
    ];

    protected static $payoutServiceCreateRules = [
        Entity::ID                   => 'required|string|size:14',
        Entity::MERCHANT_ID          => 'required|string|size:14'
    ];


    protected static $beforeCreateFundAccountPayoutWithOtpRules = [
        Entity::ORIGIN => 'sometimes|filled|in:' . Entity::DASHBOARD,
    ];

    protected static $beforeCreateFundAccountPayoutValidators = [
        'origin',
        'source_details',
    ];

    protected static $fundAccountPayoutCompositeValidators = [
        'origin',
        'source_details',
        'amount',
        'amount_as_integer'
    ];

    protected static $customerWalletPayoutValidators = [
        'amount_as_integer'
    ];

    protected static $fundAccountPayoutValidators = [
        'amount',
        'amount_as_integer'
    ];

    protected static $beforeCreateFundAccountPayoutWithOtpValidators = [
        'source_details',
    ];

    // Both regular(type:default) and on demand(type:on_demand) payouts are validated through merchantPayoutRules.
    protected static $merchantPayoutRules = [
        Entity::PURPOSE    => 'required|string|max:30|in:payout',
        Entity::METHOD     => 'sometimes|string',
        Entity::AMOUNT     => 'required|integer',
        Entity::CURRENCY   => 'required|size:3',
        Entity::TYPE       => 'required|string|max:30|in:default,on_demand',
        Entity::BALANCE_ID => 'sometimes|filled|size:14',
    ];

    // On calling merchant/payout merchantRules gets used for validation of input.
    protected static $merchantRules = [
        Entity::MERCHANT_ID   => 'required|string|size:14',
        Entity::AMOUNT        => 'sometimes|integer|max:' . self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT,
        Entity::MIN_AMOUNT    => 'sometimes|integer|min:100',
        Entity::MODULO        => 'sometimes|integer|min:100',
        Entity::BUFFER_AMOUNT => 'sometimes|integer|min:10000000'
    ];

    // On calling merchant/payout/demand merchantPayoutOnDemandRules gets used for validation of input.
    protected static $merchantPayoutOnDemandRules = [
        Entity::AMOUNT   => 'required|integer|min:100|max:' . self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT,
        Entity::CURRENCY => 'required|size:3',
    ];

    protected static $createPurposeRules = [
        Entity::PURPOSE      => 'required|filled|string|max:30|alpha_dash_space',
        Entity::PURPOSE_TYPE => 'required|filled|string|in:refund,settlement',
    ];

    protected static $calculateEsOnDemandFeesRules = [
        Entity::AMOUNT   => 'required|integer|min:100',
        Entity::CURRENCY => 'required|size:3|in:INR,',
    ];

    protected static $approvePayoutRules = [
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $cancelPayoutRules = [
        Entity::REMARKS => 'sometimes|filled|string|max:255',
    ];

    protected static $bulkApproveRules = [
        Entity::PAYOUT_IDS           => 'required|array',
        Entity::PAYOUT_IDS . '.*'    => 'required|public_id|size:19',
        User\Entity::OTP             => 'required|filled|min:4',
        User\Entity::TOKEN           => 'required|unsigned_id',
        ActionChecker::USER_COMMENT  => 'sometimes|nullable|string|max:255',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $batchApproveRules = [
        Entity::PAYOUT_IDS           => 'required|array',
        Entity::PAYOUT_IDS . '.*'    => 'required|public_id|size:19',
    ];

    protected static $batchRejectRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
    ];

    protected static $retryPayoutsOnServiceRules = [
        Entity::PAYOUT_IDS        => 'required|array',
        Entity::PAYOUT_IDS . '.*' => 'required|string|size:14',
    ];

    protected static $bulkRetryWorkflowRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
    ];

    protected static $bulkRejectRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|public_id|size:19',
        Entity::FORCE_REJECT        => 'filled|boolean',
        ActionChecker::USER_COMMENT => 'sometimes|nullable|string|max:255',
    ];

    protected static $processQueuedPayoutsInitiateRules = [
        Entity::BALANCE_IDS     => 'sometimes|array',
        Entity::BALANCE_IDS_NOT => 'sometimes|array',
    ];

    // Both regular and on demand payouts are validated through the merchantPayoutValidators.
    protected static $merchantPayoutValidators = [
        'type_and_amount',
        'amount_as_integer'
    ];

    protected static $processScheduledPayoutsRules = [
        Entity::BALANCE_IDS     => 'sometimes|array',
        Entity::BALANCE_IDS_NOT => 'sometimes|array',
    ];

    protected static $payoutStatusManualRules = [
        Entity::STATUS              => 'required|string',
        Entity::FAILURE_REASON      => 'sometimes|string',
    ];

    protected static $payoutStatusManualValidators = [
        'final_status',
    ];

    protected static $skipWorkflowRules = [
        Entity::SKIP_WORKFLOW   => 'filled|boolean'
    ];

    protected static $skipWorkflowValidators = [
        'skip_workflow'
    ];

    protected static $validatePayoutPurposeRules = [
        Entity::PURPOSE         => 'required|string',
    ];

    protected static $payoutBulkSampleFileRules = [
        Entity::FILE_TYPE       => 'required|string|in:sample_file,template_file',
        Entity::FILE_EXTENSION  => 'required|string|in:csv,xlsx',
    ];

    protected static $payoutServiceFtsCreateRules = [
        Entity::ID => 'required|string|size:14',
    ];

    protected static $payoutServiceTransactionCreateRules = [
        Entity::ID                   => 'required|string|size:14',
        Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    protected static $payoutBulkStatusUpdateManualRules = [
        Entity::PAYOUT_IDS          => 'required|array',
        Entity::PAYOUT_IDS . '.*'   => 'required|string|size:14',
        Entity::STATUS              => 'required|string',
        Entity::FAILURE_REASON      => 'sometimes|string',
    ];

    protected static $payoutBulkStatusUpdateManualValidators = [
        'final_status',
    ];

    protected function validateMethod($attribute, $method)
    {
        Method::validateMethod($method);
    }

    protected function validateMode($attribute, $value)
    {
        PayoutMode::validateMode($value);
    }

    protected function validateScheduledAt($attribute, $value)
    {
        Schedule::validateScheduledAt($value);
    }

    protected function validateTypeAndAmount($input)
    {

        // We have different limits for both regular and on demand payouts. They need to be validated accordingly.
        $type = $input[Entity::TYPE];

        $amount = $input[Entity::AMOUNT];

        // Validation in case of type 'on demand'
        if (($type === Entity::ON_DEMAND) and
            ($amount > self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT))
        {
            $message = 'The amount may not be greater than ' . self::MAX_LIMIT_MERCHANT_ON_DEMAND_PAYOUT_AMOUNT . '.';
            throw new Exception\BadRequestValidationFailureException(
                $message,
                Entity::AMOUNT,
                $amount
            );
        }

        // Validation in case of type 'default'
        if (($type === Entity::DEFAULT) and
            ($amount > self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT))
        {
            $message = 'The amount may not be greater than ' . self::MAX_LIMIT_MERCHANT_PAYOUT_AMOUNT . '.';
            throw new Exception\BadRequestValidationFailureException(
                $message,
                Entity::AMOUNT,
                $amount
            );
        }
        // Noticed during dev that data field sent with the above calls to BadRequestValidationFailureException came out at other end (log/response) as null
    }

    public function validateFundAccountMode($input)
    {
        /** @var Entity $payout */
        $payout = $this->entity;
        //
        // We use mode from the entity and not from the input, because
        // in case of UPI, we set the mode to UPI in modifiers (called in build).
        // But this particular validateMode function is not called via build.
        // It's explicitly called later after build. Since we don't pass input by
        // reference to build, this function does not have the modified input.
        // Due to this, we would end up NOT validating mode for UPI.
        // Hence, we take the mode from the entity directly which would be filled by build.
        //
        $mode = $payout->getMode();

        $fundAccount = $payout->fundAccount;

        $accountType = $fundAccount->getAccountType();

        Mode::validateModeOfAccountType($mode, $accountType);

        $this->validateCardAccountType($payout);

        $this->blockAmazonPayPayoutsFromDirectAccounts($payout);

        $this->validateModeAndAmount($input, $payout);
    }

    protected function validateCardAccountType(Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $accountType = $fundAccount->getAccountType();

        if ($accountType === FundAccount\Type::CARD)
        {
            $cardIssuer = $fundAccount->account->getIssuer();

            $app = App::getFacadeRoot();

            if (($fundAccount->account->isAmex() === true) and
                ($cardIssuer === null))
            {
                $cardIssuer = Constants::DEFAULT_ISSUER;
            }

            $networkCode = $fundAccount->account->getNetworkCode();

            if (($cardIssuer === Issuer::SCBL) and
                ((new Card\Core)->checkAllowedNetworksForSCBL($fundAccount->account) === false))
            {
                throw new BadRequestValidationFailureException(
                    Network::getFullName($networkCode) . " cards are not supported for issuer " . Issuer::SCBL,
                    null,
                    [
                        Card\Entity::TYPE       => $fundAccount->account->getType(),
                        Card\Entity::ISSUER     => $cardIssuer,
                        Card\Entity::NETWORK    => Network::getFullName($networkCode),
                        Entity::FUND_ACCOUNT_ID => $fundAccount->getId(),

                    ]);
            }

            $cardType    = $fundAccount->account->getType();
            $cardIin     = $fundAccount->account->getIin();
            $cardNetwork = $fundAccount->account->getNetwork();

            if($mode === Mode::CARD)
            {
                $hasSupportedModes = false;
                $supportedModeConfigs = (new Mode)->getM2PSupportedChannelModeConfig(
                    $cardIssuer,
                    $cardNetwork,
                    $cardType,
                    $cardIin
                );

                foreach ($supportedModeConfigs as $supportedMode)
                {
                    if ($supportedMode[Mode::CHANNEL] === Settlement\Channel::M2P)
                    {
                        $hasSupportedModes = true;
                    }
                }

                if($hasSupportedModes === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Payout mode CARD is not supported for the fund account',
                        null,
                        [
                            'fund_account' => $fundAccount->getId(),
                            'card_issuer'  => $cardIssuer,
                            'card_network' => $networkCode,
                            'cardType'     => $cardType
                        ]);
                }

                return;
            }

            Mode::validateModeOfIssuer($mode, $cardIssuer, $networkCode);
        }
    }

    protected function validateModeAndAmount(array $input, Entity $payout)
    {
        $fundAccount = $payout->fundAccount;

        $mode = $payout->getMode();

        $amount = $input[Entity::AMOUNT];

        $minRtgsAmount       = NodalAccount::MIN_RTGS_AMOUNT * 100;
        $maxImpsAmount       = NodalAccount::MAX_IMPS_AMOUNT * 100;
        $maxUpiAmount        = FundAccount\Validator::MAX_UPI_AMOUNT;
        $maxAmazonPayAmount  = FundAccount\Validator::MAX_WALLET_ACCOUNT_AMAZON_PAY_AMOUNT;

        if ((($mode === Mode::RTGS) and ($amount < $minRtgsAmount)) or
            (($mode === Mode::IMPS) and ($amount > $maxImpsAmount)) or
            (($mode === Mode::UPI) and ($amount > $maxUpiAmount)) or
            (($mode === Mode::AMAZONPAY) and ($amount > $maxAmazonPayAmount)))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_AMOUNT_MODE_MISMATCH,
                null,
                [
                    'amount'                          => $amount,
                    'mode'                            => $mode,
                    'min_rtgs_amount'                 => $minRtgsAmount,
                    'max_imps_amount'                 => $maxImpsAmount,
                    'maxWalletAccountAmazonPayAmount' => $maxAmazonPayAmount,
                    'fund_account_id'                 => $fundAccount->getId(),
                    'account_type'                    => $fundAccount->getAccountType(),
                ]);
        }
    }

    public function validatePayoutAmount($input, $payment)
    {
        if (isset($input[Entity::AMOUNT]) === false)
        {
            return;
        }

        $payoutAmount = $input[Entity::AMOUNT];

        $payoutAmountPending = $payment->getAmount() - $payment->getAmountPaidout();

        if ($payoutAmountPending === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FULLY_PAIDOUT);
        }

        if ($payoutAmount > $payment->getAmount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED);
        }

        if ($payoutAmount > $payoutAmountPending)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_PENDING);
        }
    }

    /**
     * Validate that a payout can be created on a particular payment
     *
     * @param array          $input
     * @param Payment\Entity $payment
     *
     * @throws Exception\BadRequestException
     */
    public function validatePaymentForPayout(array $input, Payment\Entity $payment)
    {
        //
        // If method is not sent in input, skip the
        // following validation and allow the call to
        // fail during Payout build
        //
        if (isset($input[Entity::METHOD]) === false)
        {
            return;
        }

        // Only validating for card payments
        if ($payment->isCard() === false)
        {
            return;
        }

        $card = $payment->card;

        if ($card->getType() === Card\Type::CREDIT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_FUND_TRANSFER_ON_CREDIT_CARD_PAYMENT);
        }
    }

    public function validatePayoutStatusForApproveOrReject()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_STATE,
                null,
                [
                    'id'     => $payout->getId(),
                    'status' => $payout->getStatus(),
                ]
            );
        }
    }

    public function validateRetryPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->hasPayment() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_FOR_PAYMENT_NOT_ALLOWED,
                null,
                [
                    'payout_id'  => $payout->getId(),
                    'payment_id' => $payout->getPaymentId(),
                ]);
        }

        $payoutStatus = $payout->getStatus();

        if ($payout->isStatusReversedOrFailed() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_RETRY_NOT_IN_REVERSED,
                null,
                [
                    'payout_id'     => $payout->getId(),
                    'payout_status' => $payoutStatus,
                ]);
        }
    }

    public function validateProcessingQueuedPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusQueued() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validateOnHoldPayoutProcessing()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        // Already processed by another queue job due to overlap of cron runs.
        if ($payout->isStatusOnHold() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_ON_HOLD,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }

    /**
     * @param Entity $payout
     *
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function validateProcessingScheduledPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        //
        // Triggered when payout was already processed by another queue job due to overlap of cron runs.
        // Or if the payout has not yet been approved. We are not going to throw an error for payout in pending state,
        // because we have a custom auto reject logic for that and we don't wish to throw any error here
        //
        if (($payout->isStatusScheduled() === true) or
            ($payout->isStatusPending() === true))
        {
            $this->validateIsFundAccountPayout($payout);

            return;
        }

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYOUT_NOT_SCHEDULED_STATUS,
            null,
            [
                'payout_id' => $payout->getId(),
                'status'    => $payout->getStatus(),
            ]);
    }

    public function validateProcessingBatchProcessingPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusBatchSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_BATCH_SUBMITTED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);
    }

    public function validatePostCreateProcessPayout()
    {
        $payout = $this->entity;

        if ($payout->isStatusCreateRequestSubmitted() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_CREATE_REQUEST_SUBMITTED_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }
    }

    public function validateProcessingPendingPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    public function validateRejectPayout()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if ($payout->isStatusPending() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_PENDING_STATUS,
                null,
                [
                    'payout_id' => $payout->getId(),
                    'status'    => $payout->getStatus(),
                ]);
        }

        $this->validateIsFundAccountPayout($payout);

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    public function validateIsFundAccountPayout(Entity $payout)
    {
        if (($payout->hasFundAccount() === false) or
            ($payout->hasCustomer() === true))
        {
            throw new Exception\LogicException(
                'Payout is not of RX or not a proper fund_account type',
                null,
                [
                    'payout_id'       => $payout->getId(),
                    'balance_type'    => $payout->balance->getType(),
                    'fund_account_id' => $payout->getFundAccountId(),
                    'customer_id'     => $payout->getCustomerId(),
                ]);
        }
    }

    public function validateCancel()
    {
        /** @var Entity $payout */
        $payout = $this->entity;

        if (($payout->isStatusQueued() === false) and
            ($payout->isStatusScheduled() === false) and
            ($payout->isStatusOnHold() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_NOT_QUEUED_OR_SCHEDULED_STATUS,
                null,
                [
                    'payout_id'         => $payout->getId(),
                    'current_status'    => $payout->getStatus(),
                    'next_status'       => Status::CANCELLED,
                ]);
        }

        $app = App::getFacadeRoot();

        if (($payout->isStatusScheduled() === true) and
            ($app['basicauth']->isProxyAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SCHEDULED_PAYOUT_CANCEL_AUTH_NOT_SUPPORTED,
                null,
                [
                    'payout_id' => $payout->getId(),
                ]);
        }

        $this->validateCancelOrApproveOrRejectRequestForScheduledPayouts($payout);
    }

    /**
     * @param array $input
     * Rate limit on number of payout creation in Bulk Route
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkPayoutCount(array $input)
    {
        if (count($input) > self::MAX_BULK_PAYOUTS_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of Bulk Contact is ' . self::MAX_BULK_PAYOUTS_LIMIT,
                null,
                null
            );
        }
    }

    protected function validateFundAccount($attribute, $value)
    {
        if (isset($value[Entity::CONTACT_ID]) === true)
        {
            throw new ExtraFieldsException(
                 Entity::FUND_ACCOUNT . '.' . Entity::CONTACT_ID
            );
        }
    }

    protected function validateFinalStatus(array $input)
    {
        $status = $input[Entity::STATUS];

        $isValid = Status::isFinalState($status);

        if ($isValid === false)
        {
            throw new BadRequestValidationFailureException(
                'Payout can be updated to only a final status',
                null,
                [
                    'status' => $status
                ]
            );
        }
    }
    /**
     * @param Entity $payout
     *
     * @throws Exception\BadRequestException
     */
    private function validateCancelOrApproveOrRejectRequestForScheduledPayouts(Entity $payout)
    {
        if ($payout->toBeScheduled() === false)
        {
            return;
        }

        Schedule::validateCancelOrApproveOrRejectRequest($payout);
    }

    protected function validateSkipWorkflow($input)
    {
        $skipWorkflow = (bool) $input[Entity::SKIP_WORKFLOW];

        if ($skipWorkflow === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Only true is valid for skip_workflow key.",
                'skip_workflow');
        }
    }

    protected function validateOrigin($input)
    {
        if (isset($input[Entity::ORIGIN]) === true)
        {
            $origin = $input[Entity::ORIGIN];

            $this->validateIfFieldShouldBeSentWithCompositeApi($input, Entity::ORIGIN, $origin);

            $this->validateIfFieldShouldBeSentBasedOnAuth(Entity::ORIGIN, $origin);

            $origin               = strtolower($origin);
            $acceptedOriginValues = array_keys(Entity::ORIGIN_SERIALIZER);

            if (in_array($origin, $acceptedOriginValues) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Invalid origin.",
                    Entity::ORIGIN,
                    [
                        Entity::ORIGIN               => $origin,
                        self::ACCEPTED_ORIGIN_VALUES => $acceptedOriginValues,
                    ]
                );
            }
        }
    }

    protected function validateIfFieldShouldBeSentWithCompositeApi(array $input, string $fieldName, $fieldValue)
    {
        // In settlements & XPayroll service, all payouts will be made via composite API,
        // so we'll allow composite API for these apps
        if (((new Service)->isSettlementsApp() === true) or
            ((new Service)->isXPayrollApp() === true) or
            ((new Service)->isScroogeApp() === true))
        {
            return;
        }

        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                $fieldName . " is/are not required and should not be sent",
                $fieldName,
                [
                    $fieldName => $fieldValue,
                ]
            );
        }
    }

    protected function validateIfCardTokenReceivedForScroogeAppOnly(array $input)
    {
        //For instant refunds migration vault token will be received instead of card details.
        //Allowing vault token only for refund service for now.
        if ((isset($input[Entity::FUND_ACCOUNT]) === true) and
            (isset($input[Entity::FUND_ACCOUNT][Entity::CARD]) === true) and
            (isset($input[Entity::FUND_ACCOUNT][Entity::CARD][Card\Entity::TOKEN]) === true) and
            ((new Service)->isScroogeApp() === false)) {

            throw new Exception\BadRequestValidationFailureException(
                Entity::CARD . '.' . Card\Entity::TOKEN . " is/are not required and should not be sent"
            );
        }
    }

    protected function validateIfFieldShouldBeSentBasedOnAuth(string $fieldName, $fieldValue)
    {
        if ((new Service)->isAllowedInternalApp() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $fieldName . " is/are not required and should not be sent",
                $fieldName,
                [
                    $fieldName => $fieldValue,
                ]
            );
        }
    }

    protected function validateSourceDetails($input)
    {
        if (isset($input[Entity::SOURCE_DETAILS]) === true)
        {
            $sourceDetails = $input[Entity::SOURCE_DETAILS];

            $this->validateIfFieldShouldBeSentWithCompositeApi($input, Entity::SOURCE_DETAILS, $sourceDetails);

            $this->validateIfFieldShouldBeSentBasedOnAuth(Entity::SOURCE_DETAILS, $sourceDetails);

            $this->validatePrioritySequence($input[Entity::SOURCE_DETAILS]);

            $this->validateIfCardTokenReceivedForScroogeAppOnly($input);
        }
    }

    protected function validatePrioritySequence(array $sourceDetails)
    {
        $priorities = [];

        foreach ($sourceDetails as $sourceDetail)
        {
            array_push($priorities, $sourceDetail[PayoutSource::PRIORITY]);
        }

        $priorities = array_unique($priorities);

        if (count($priorities) !== count($sourceDetails))
        {
            throw new Exception\BadRequestValidationFailureException(
                "source_details has sources with duplicate priorities",
                Entity::SOURCE_DETAILS,
                [
                    Entity::SOURCE_DETAILS => $sourceDetails,
                ]
            );
        }
    }

    public function validateChannelAndModeForPayouts(string $merchantId,
                                                     string $channel = null,
                                                     string $destinationType = null,
                                                     string $mode = null,
                                                     string $accountType = null) : bool
    {
        if (($channel === Settlement\Channel::RBL) and
            ($destinationType === FundAccount\Type::CARD))
            {
                $app = App::getFacadeRoot();

                $variant = $app->razorx->getTreatment(
                    $merchantId,
                    Merchant\RazorxTreatment::PAYOUT_TO_CARDS_VIA_RBL,
                    $this->getMode()
                );

                if ($variant !== 'on')
                {
                    return false;
                }
            }

        if (($channel === Settlement\Channel::RBL) and
            ($mode === PayoutMode::UPI))
        {
            if($this->isUpiModeEnabledOnRblDirectAccountForMerchantId($merchantId) === false)
            {
                return false;
            }
        }

        return PayoutMode::validateChannelAndModeForPayouts($channel, $destinationType, $mode, $accountType);
    }

    public function validateAndUpdateCardMode(array & $input)
    {
        if((isset($input[Entity::MODE]) === true) and
            (strtolower($input[Entity::MODE]) === PayoutMode::CARD))
        {
            $input[Entity::MODE] = PayoutMode::CARD;
        }

    }

    public function blockAmazonPayPayoutsFromDirectAccounts(Payout\Entity $payout)
    {
        $balance = $payout->balance;

        if (($balance->getAccountType() === Balance\AccountType::DIRECT) and
            ($payout->getMode() === FundTransfer\Mode::AMAZONPAY))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_AMAZONPAY_PAYOUT_NOT_ALLOWED_ON_DIRECT_ACCOUNT,
                null,
                [
                    Payout\Entity::MERCHANT_ID     => $payout->getMerchantId(),
                    Payout\Entity::BALANCE_ID      => $payout->getBalanceId()
                ]);
        }
    }

    protected function validateAmount($input)
    {
        if (isset($input[Entity::AMOUNT]) === true)
        {
            $maxPayoutAmountLimit = Entity::MAX_PAYOUT_LIMIT;

            if ((new Service)->isSettlementsApp() === true)
            {
                $maxPayoutAmountLimit = Entity::MAX_SETTLEMENT_PAYOUT_LIMIT;
            }

            if ($input[Entity::AMOUNT] > $maxPayoutAmountLimit)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "The amount may not be greater than " . $maxPayoutAmountLimit . ".",
                    Entity::AMOUNT,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                    ]
                );
            }

            if ($input[Entity::AMOUNT] < self::MIN_TRANSACTION_AMOUNT_ALLOWED_IN_PAISE)
            {
                throw new Exception\BadRequestValidationFailureException(
                    "Minimum transaction amount should be 100 paise",
                    Entity::AMOUNT,
                    [
                        Entity::AMOUNT => $input[Entity::AMOUNT],
                    ]
                );
            }
        }
    }

    public function validateAmountAsInteger($input)
    {
        if (isset($input[Entity::AMOUNT]) === true)
        {
            $amount = $input[Entity::AMOUNT];

            if (is_string($amount) === true)
            {
                if ((empty($amount) === true) or
                    (preg_match(self::AMOUNT_REGEX, $amount) !== 0))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        "The amount must be an integer.",
                        Entity::AMOUNT,
                        [
                            Entity::AMOUNT => $input[Entity::AMOUNT],
                        ]
                    );
                }
            }
            else
            {
                if (is_int($amount) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        "The amount must be an integer.",
                        Entity::AMOUNT,
                        [
                            Entity::AMOUNT => $input[Entity::AMOUNT],
                        ]
                    );
                }
            }
        }
    }

    public function validateBeneStatusReceivedFromFts(string $status)
    {
        if($status != 'started' and $status != 'resolved')
        {
            throw new Exception\BadRequestValidationFailureException(
                "The status received from fts is " . $status . ".",
                null,
                [
                    'status' => $status,
                ]
            );
        }
    }

    public function isUpiModeEnabledOnRblDirectAccountForMerchantId(string $merchantId)
    {
        $featureList = (new FeatureRepo())->findMerchantWithFeatures($merchantId, [Features::RBL_CA_UPI]);
        return (count($featureList) !== 0);
    }

    public function validatebulkPurposeCreation(int $count)
    {
        if($count >= 100 ){
            throw new Exception\BadRequestValidationFailureException(
                "The limit for max purpose creation in 1 call is 100, please reduce the number from ".$count."."
            );
        }
    }
}

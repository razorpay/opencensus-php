<?php

namespace RZP\Models\Batch;

use App;
use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator as LaravelValidator;
use Lib\Gstin;

use RZP\Base;
use RZP\Exception;
use RZP\Models\User;
use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Pricing;
use RZP\Models\Invoice;
use RZP\Constants\Mode;
use RZP\Models\Settings;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\User\Role;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Http\UserRolesScope;
use RZP\Models\Payment\Refund;
use RZP\Models\RawAddress;
use RZP\Exception\BaseException;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\BatchHelper;
use RZP\Models\Merchant\Entity as ME;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact as ContactModel;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Batch\Helpers\OauthMigration as OMHelper;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as HdfcEMDebitHeadings;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as HdfcEMRegisterHeadings;

/**
 * Class Validator
 *
 * @package RZP\Models\Batch
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    const PAN_REGEX = '/^[a-zA-Z]{3}[aAbBcCfFgGhHjJlLpPtT][a-zA-Z][0-9]{4}[a-zA-Z]{1}$/';

    // Default rule for file validation. Per type a different file rule can be written.
    const DEFAULT_MIME_RULE = ''
        // Allowed mime types.
        . '|mime_types:'
        . 'application/zip,'
        . 'application/vnd.ms-excel,'
        . 'application/vnd.oasis.opendocument.spreadsheet,'
        . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
        . 'application/octet-stream,'
        . 'application/xml,'
        . 'text/csv,'
        . 'text/xml,'
        . 'text/plain,'
        . 'application/cdfv2-unknown,'
        . 'application/vnd.ms-office,'
        . 'application/excel,'
        . 'application/msexcel,'
        // Allowed mimes/extensions.
        . '|mimes:'
        . 'zip,'
        . 'xlsx,'
        . 'xls,'
        . 'xml,'
        . 'csv,'
        . 'txt,';

    // Rule for allowing only csv or plain text file.
    const CSV_MIME_RULE = ''
        // Allowed mime types.
        . '|mime_types:'
        . 'text/csv,'
        . 'text/plain,'
        // Allowed mimes/extensions.
        . '|mimes:'
        . 'csv,'
        . 'txt,';

    // Rule for allowing csv, xlsx or plain text file.
    const CSV_EXCEL_MIME_RULE = ''
        // Allowed mime types.
        . '|mime_types:'
        . 'application/zip,'
        . 'application/vnd.ms-excel,'
        . 'application/vnd.oasis.opendocument.spreadsheet,'
        . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
        . 'application/octet-stream,'
        . 'application/xml,'
        . 'text/csv,'
        . 'text/plain,'
        . 'application/cdfv2-unknown,'
        . 'application/vnd.ms-office,'
        . 'application/excel,'
        . 'application/msexcel,'
        // Allowed mimes/extensions.
        . '|mimes:'
        . 'csv,'
        . 'zip,'
        . 'xlsx,'
        . 'txt,';

    const VALIDATE_FILE_NAME = 'validate_file_name';

    protected static $validateFileNameRules = [
        'filename'      => 'required|string',
        'batch_type_id' => 'required|string|in:' . Constants::TALLY_PAYOUT_BATCH
    ];

    protected static $defaultCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $ledgerOnboardOldAccountCreateRules = [
        Entity::TYPE                    => 'required|in:ledger_onboard_old_account',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,  // 60MB
        Entity::FILE_ID                 => 'required_without:file|public_id',
    ];

    protected static $ledgerBulkJournalCreateRules = [
        Entity::TYPE                    => 'required|in:ledger_bulk_journal_create',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,  // 60MB
        Entity::FILE_ID                 => 'required_without:file|public_id',
    ];

    protected static $tokenHqChargeCreateRules = [
        Entity::TYPE                 => 'required|in:token_hq_charge',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $irctcSettlementCreateRules = [
        Entity::TYPE                 => 'required|in:irctc_settlement',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:5120' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $irctcRefundCreateRules = [
        Entity::TYPE                 => 'required|in:irctc_refund',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:5120' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $irctcDeltaRefundCreateRules = [
        Entity::TYPE                 => 'required|in:irctc_delta_refund',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:5120' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $paymentLinkCreateRules = [
        Entity::TYPE                    => 'required|in:payment_link',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Invoice\Entity::DRAFT           => 'filled|in:0,1',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
        Entity::CONFIG                  => 'filled|array',
        Entity::SCHEDULE                => 'sometimes|numeric',
    ];

    // CONFIG is made optional for backward compatibility
    protected static $partnerSubmerchantInviteCreateRules = [
        Entity::TYPE                    => 'required|in:partner_submerchant_invite',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'sometimes|array',
    ];

    protected static array $partnerSubmerchantInviteCapitalCreateRules = [
        Entity::TYPE                    => 'required|in:partner_submerchant_invite_capital',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'sometimes|array',
    ];

    protected static array $partnerSubmerchantReferralInviteCreateRules = [
        Entity::TYPE                    => 'required|in:partner_submerchant_referral_invite',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'sometimes|array',
    ];

    protected static $paymentPageCreateRules = [
        Entity::TYPE                    => 'required|in:payment_page',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'required|array',
    ];

    protected static $partnerReferralFetchCreateRules = [
        Entity::TYPE                    => 'required|in:partner_referral_fetch',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
    ];

    protected static $paymentLinkV2CreateRules = [
        Entity::TYPE                    => 'required|in:payment_link_v2',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
        Entity::CONFIG                  => 'filled|array',
        Entity::SCHEDULE                => 'sometimes|numeric',
    ];

    protected static $linkedAccountReversalCreateRules = [
        Entity::TYPE                    => 'required|in:linked_account_reversal',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::SCHEDULE                => 'sometimes|numeric',
    ];

    protected static $directDebitCreateRules = [
        Entity::TYPE            => 'required|in:direct_debit',
        Entity::FILE            => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::NAME            => 'filled|string|max:255',
        Entity::TOKEN           => 'required_without:file_id|max:255|alpha_num',
        Entity::FILE_ID         => 'required_without:file|public_id',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $recurringChargeCreateRules = [
        Entity::TYPE            => 'required|in:recurring_charge',
        Entity::FILE            => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE, // 60MB
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE_ID         => 'required_without:file|public_id',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $recurringChargeBulkCreateRules = [
        Entity::TYPE            => 'required|in:recurring_charge_bulk',
        Entity::FILE            => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE, // 60MB
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE_ID         => 'required_without:file|public_id',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $recurringChargeAxisCreateRules = [
        Entity::TYPE            => 'required|in:recurring_charge_axis',
        Entity::FILE            => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE, // 60MB
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE_ID         => 'required_without:file|public_id',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $recurringChargeBseCreateRules = [
        Entity::TYPE            => 'required|in:recurring_charge_bse',
        Entity::FILE            => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE_ID         => 'required_without:file|public_id',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $tokenRules = [
        Entity::TOKEN           => 'required|max:255|alpha_num',
    ];

    protected static $reconciliationCreateRules = [
        Entity::TYPE            => 'required|in:reconciliation',
        Entity::GATEWAY         => 'required|string|max:25',
        Entity::FILE            => 'required|file',
        Entity::CONFIG          => 'filled|array',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $vaultMigrateTokenNsCreateRules = [
        Entity::TYPE                 => 'required|in:vault_migrate_token_ns',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $emandateCreateRules = [
        Entity::FILE        => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:emandate',
        Entity::SUB_TYPE    => 'required|string|in:register,debit,acknowledge,cancel_debit,cancel',
        Entity::GATEWAY     => 'required|string',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $nachCreateRules = [
        Entity::FILE        => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:nach',
        Entity::SUB_TYPE    => 'required|string|in:register,debit,acknowledge,cancel,update',
        Entity::GATEWAY     => 'required|string',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $hitachiCbkMastercardCreateRules = [
        Entity::FILE        => 'required|file',
        Entity::TYPE        => 'required|string|in:hitachi_cbk_mastercard',
    ];

    protected static $hitachiCbkVisaCreateRules = [
        Entity::FILE        => 'required|file',
        Entity::TYPE        => 'required|string|in:hitachi_cbk_visa',
    ];

    protected static $hitachiCbkRupayCreateRules = [
        Entity::FILE        => 'required|file',
        Entity::TYPE        => 'required|string|in:hitachi_cbk_rupay',
    ];

    protected static $nachMigrationCreateRules = [
        Entity::FILE        => 'required_without:file_id|file|max:102400' . self::DEFAULT_MIME_RULE,    // in KB
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::TYPE        => 'required|in:nach_migration',
        Entity::CONFIG      => 'required|array',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $nachMigrationValidateRules = [
        Entity::FILE        => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,    // in KB
        Entity::TYPE        => 'required|in:nach_migration',
        Entity::CONFIG      => 'required|array',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $paymentPageValidateRules = [
        Entity::FILE        => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,    // in KB
        Entity::TYPE        => 'required|in:payment_page',
        Entity::CONFIG      => 'required|array',
    ];

    protected static $merchantOnboardingCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:merchant_onboarding',
        Entity::GATEWAY     => 'required|string',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $terminalCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::SUB_TYPE             => 'required|string|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $bankingAccountActivationCommentsCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:banking_account_activation_comments',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $iciciLeadAccountActivationCommentsCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:icici_lead_account_activation_comments',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $rblBulkUploadCommentsCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:rbl_bulk_upload_comments',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $iciciBulkUploadCommentsCreateRules = [
        Entity::FILE          => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE          => 'required|in:icici_bulk_upload_comments',
        Entity::CONFIG        => 'filled|array',
    ];

    protected static $iciciVideoKycBulkUploadCreateRules = [
        Entity::FILE          => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE          => 'required|in:icici_video_kyc_bulk_upload',
        Entity::CONFIG        => 'filled|array',
    ];

    protected static $iciciStpMisCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:icici_stp_mis',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $virtualBankAccountCreateRules = [
        Entity::TYPE                 => 'required|in:virtual_bank_account',
        Entity::FILE                 => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $elfinCreateRules = [
        Entity::TYPE        => 'required|custom',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG      => 'filled|array',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $entityMappingCreateRules = [
        Entity::TYPE                         => 'required|in:entity_mapping',
        Entity::NAME                         => 'filled|string|max:255',
        Entity::FILE                         => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG                       => 'required|array',
        Entity::CONFIG . '.entity_from_type' => 'required|string',
        Entity::CONFIG . '.entity_to_type'   => 'required|string',
        Entity::SCHEDULE                     => 'sometimes|numeric',
    ];

    protected static $edMerchantSearchCreateRules = [
        Entity::FILE        => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:ed_merchant_search',
    ];

    protected static $authLinkCreateRules = [
        Entity::TYPE                    => 'required|in:auth_link',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE, // 60MB
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'filled|array',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
        Entity::SCHEDULE                => 'sometimes|numeric',
    ];

    protected static $adjustmentCreateRules = [
        Entity::TYPE         => 'required|in:adjustment',
        Entity::FILE         => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID      => 'required_without:file',
        Entity::NAME         => 'filled|string|max:255',
        Entity::SCHEDULE     => 'sometimes|numeric',
    ];

    protected static $settlementOndemandFeatureConfigCreateRules = [
        Entity::TYPE         => 'required|in:settlement_ondemand_feature_config',
        Entity::FILE         => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID      => 'required_without:file',
        Entity::NAME         => 'filled|string|max:255',
        Entity::SCHEDULE     => 'sometimes|numeric',
    ];

    protected static $capitalMerchantEligibilityConfigCreateRules = [
        Entity::TYPE         => 'required|in:capital_merchant_eligibility_config',
        Entity::FILE         => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID      => 'required_without:file',
        Entity::NAME         => 'filled|string|max:255',
        Entity::SCHEDULE     => 'sometimes|numeric',
    ];

    protected static $earlySettlementTrialCreateRules = [
        Entity::TYPE         => 'required|in:early_settlement_trial',
        Entity::FILE         => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID      => 'required_without:file',
        Entity::NAME         => 'filled|string|max:255',
        Entity::SCHEDULE     => 'sometimes|numeric',
    ];

    protected static $merchantCapitalTagsCreateRules = [
        Entity::TYPE         => 'required|in:merchant_capital_tags',
        Entity::FILE         => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID      => 'required_without:file',
        Entity::NAME         => 'filled|string|max:255',
        Entity::SCHEDULE     => 'sometimes|numeric',
    ];

    protected static $iinNpciRupayCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:4096' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $iinHitachiVisaCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $iinMcMastercardCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $collectLocalConsentsToCreateTokensCreateRules = [
        Entity::TYPE            => 'required|in:collect_local_consents_to_create_tokens',
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE            => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $captureSettingCreateRules = [
        Entity::TYPE                    => 'required|in:capture_setting',
        Entity::FILE                    => 'required_without:file_id|file|max:60720' . self::DEFAULT_MIME_RULE,
    ];
    /**
     * Defines the required keys to be present in emandate hdfc register file
     * and the corresponding error message to be thrown when they are absent or empty
     *
     * @var array
     */
    protected static $emandateRegisterHdfcRequiredEntries = [
        HdfcEMRegisterHeadings::MANDATE_ID                  => 'Mandate ID must be present',
        HdfcEMRegisterHeadings::CUSTOMER_ACCOUNT_NUMBER     => 'Customer Account Number must be present',
        HdfcEMRegisterHeadings::STATUS                      => 'Status must be present',
    ];

    /**
     * Defines the required keys to be present in instant activation batch file
     * and the corresponding error message to be thrown when they are absent or empty
     *
     * @var array
     */
    protected static $instantActivationRequiredEntries = [
        ME::MERCHANT_ID                  => 'merchant id must be present',
    ];

    /**
     * Defines the required keys to be present in emandate hdfc debit file
     * and the corresponding error message to be thrown when they are absent or empty
     *
     * @var array
     */
    protected static $emandateDebitHdfcRequiredHeaders = [
        HdfcEMDebitHeadings::TRANSACTION_REF_NO     => 'Transaction Reference No. must be present',
        HdfcEMDebitHeadings::ACCOUNT_NO             => 'Account No must be present',
        HdfcEMDebitHeadings::STATUS                 => 'Status must be present',
    ];

    protected static $subMerchantCreateRules = [
        Entity::TYPE        => 'required|in:sub_merchant',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG      => 'filled|array',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $subMerchantConfigRules = [
        ME::AUTO_SUBMIT               => 'filled|boolean',
        ME::INSTANTLY_ACTIVATE        => 'filled|boolean',
        ME::AUTOFILL_DETAILS          => 'filled|boolean',
        ME::AUTO_ACTIVATE             => 'filled|boolean',
        ME::USE_EMAIL_AS_DUMMY        => 'filled|boolean',
        ME::PARTNER_ID                => 'required|string|size:14',
        ME::AUTO_ENABLE_INTERNATIONAL => 'filled|boolean',
        ME::SKIP_BA_REGISTRATION      => 'filled|boolean',
        ME::CREATE_SUBMERCHANT        => 'filled|boolean',
        ME::DEDUPE                    => 'sometimes|boolean',
    ];

    protected static $oauthMigrationTokenCreateRules = [
        Entity::TYPE           => 'required|custom',
        Entity::NAME           => 'filled|string|max:255',
        Entity::FILE           => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        OMHelper::CLIENT_ID    => 'required|string|size:14',
        OMHelper::USER_ID      => 'required|string|size:14',
        OMHelper::REDIRECT_URI => 'required|url',
        Entity::SCHEDULE       => 'sometimes|numeric',
    ];

    protected static $fundAccountCreateRules = [
        Entity::TYPE        => 'required|in:fund_account',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $fundAccountV2CreateRules = [
        Entity::TYPE        => 'required|in:fund_account_v2',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $payoutValidateRules = [
        Entity::TYPE        => 'required|in:payout',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_EXCEL_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $bulkPayoutValidateRules = [
        Entity::TYPE        => 'required|in:bulk_payouts',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_EXCEL_MIME_RULE,
    ];

    protected static $tallyPayoutValidateRules = [
        Entity::TYPE        => 'required|in:tally_payout',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id'
    ];

    // Further validations for otp and token happen in validateOtpAndTokenForPayoutCreate()
    // Making OTP and token validation rules as sometimes here to not break the flow
    protected static $payoutCreateRules = [
        Entity::TYPE        => 'required|in:payout',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_EXCEL_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::SCHEDULE    => 'sometimes|numeric',
        Entity::CONFIG      => 'sometimes',
        Entity::OTP         => 'sometimes',
        Entity::TOKEN       => 'sometimes',
    ];

    protected static $payoutCreateOtpRules = [
        Entity::OTP         => 'required|filled|min:4',
        Entity::TOKEN       => 'required|unsigned_id',
    ];

    protected static $payoutCreateValidators = [
        'otp_and_token_for_payout_create',
    ];

    protected static $payoutApprovalCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::OTP                  => 'required|filled|min:4',
        Entity::TOKEN                => 'required|unsigned_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
        Entity::CONFIG               => 'sometimes',
    ];

    protected static $tallyPayoutCreateRules = [
        Entity::TYPE                 => 'required|in:tally_payout',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::OTP                  => 'required|filled|min:4',
        Entity::TOKEN                => 'required|unsigned_id'
    ];

    protected static $payoutApprovalValidateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::FILE                 => 'required|file|max:10240' . self::DEFAULT_MIME_RULE,
    ];

    protected static $fundAccountTypeRowRules = [
        Header::FUND_ACCOUNT_TYPE         => 'required|string|in:bank_account,vpa,wallet',
        Header::FUND_ACCOUNT_NAME         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER       => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA          => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::FUND_ACCOUNT_PHONE_NUMBER => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|nullable|string',
        Header::FUND_ACCOUNT_EMAIL        => 'sometimes|nullable|string|email',
        Header::FUND_ACCOUNT_PROVIDER     => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|nullable|string|in:amazonpay',
        Header::CONTACT_ID                => 'sometimes|nullable|public_id|size:19',
        Header::CONTACT_TYPE              => 'required_without:'.Header::CONTACT_ID.'|nullable|string',
        Header::CONTACT_NAME_2            => 'required_without:'.Header::CONTACT_ID.'|nullable|string|custom',
        Header::CONTACT_EMAIL_2           => 'sometimes|nullable|string|custom',
        Header::CONTACT_MOBILE_2          => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID      => 'sometimes|nullable|string',
        Header::NOTES                     => 'sometimes|nullable|notes',
    ];

    protected static $fundAccountV2TypeRowRules = [
        Header::FUND_ACCOUNT_TYPE         => 'required|string|in:bank_account,vpa,wallet',
        Header::FUND_ACCOUNT_NAME         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER       => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA          => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::FUND_ACCOUNT_PHONE_NUMBER => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|nullable|string',
        Header::FUND_ACCOUNT_EMAIL        => 'sometimes|nullable|string|email',
        Header::FUND_ACCOUNT_PROVIDER     => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|nullable|string|in:amazonpay',
        Header::CONTACT_ID                => 'sometimes|nullable|public_id|size:19',
        Header::CONTACT_TYPE              => 'required_without:'.Header::CONTACT_ID.'|nullable|string',
        Header::CONTACT_NAME_2            => 'required_without:'.Header::CONTACT_ID.'|nullable|string|custom',
        Header::CONTACT_EMAIL_2           => 'sometimes|nullable|string|custom',
        Header::CONTACT_MOBILE_2          => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID      => 'sometimes|nullable|string',
        Header::NOTES                     => 'sometimes|nullable|notes',
        Header::FUND_BANK_ACCOUNT_TYPE    => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string|in:savings,current',
        Header::CONTACT_GSTIN             => 'sometimes|string|max:15|custom',
        Header::CONTACT_PAN               => 'sometimes|string|max:10|custom',
    ];

    // This is not a copy paste of above ^ rules!
    protected static $payoutTypeRowRules = [
        Header::RAZORPAYX_ACCOUNT_NUMBER    => 'required|alpha_space_num|between:5,22',
        Header::PAYOUT_PURPOSE              => 'required|string|max:30|alpha_dash_space',
        Header::PAYOUT_NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Header::PAYOUT_AMOUNT               => 'required|integer|min:100|max:10000000000',
        Header::PAYOUT_CURRENCY             => 'required|size:3|in:INR',
        Header::PAYOUT_MODE                 => 'required|string|custom',
        Header::PAYOUT_REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Header::FUND_ACCOUNT_ID             => 'sometimes|nullable|public_id|size:17',
        Header::FUND_ACCOUNT_TYPE           => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string|in:bank_account,vpa,wallet',
        Header::FUND_ACCOUNT_NAME           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA            => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::FUND_ACCOUNT_PHONE_NUMBER   => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|string|nullable',
        Header::CONTACT_NAME_2              => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string|custom',
        Header::FUND_ACCOUNT_EMAIL          => 'sometimes|nullable|string|email',
        Header::CONTACT_TYPE                => 'sometimes|nullable|string',
        Header::CONTACT_EMAIL_2             => 'sometimes|nullable|string|custom',
        Header::CONTACT_MOBILE_2            => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID        => 'sometimes|nullable|string',
        Header::NOTES                       => 'sometimes|nullable|notes',
    ];

    protected static $tallyPayoutTypeRowRules = [
        Header::RAZORPAYX_ACCOUNT_NUMBER    => 'required|alpha_num|between:5,22',
        Header::PAYOUT_PURPOSE              => 'required|string|max:30|alpha_dash_space',
        Header::PAYOUT_REFERENCE_ID         => 'required|string|max:40',
        Header::PAYOUT_MODE                 => 'required|string|custom',
        Header::PAYOUT_AMOUNT_RUPEES        => 'required|regex:/^-?\d+(\.\d{1,2})?$/|numeric|min:1',
        Header::PAYOUT_CURRENCY             => 'required|size:3|in:INR',
        Header::PAYOUT_DATE                 => 'required|string|custom',
        Header::PAYOUT_NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Header::FUND_ACCOUNT_TYPE           => 'required|string|in:bank_account,vpa',
        Header::FUND_ACCOUNT_NAME           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|string',
        Header::FUND_ACCOUNT_IFSC           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|string',
        Header::FUND_ACCOUNT_NUMBER         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|string',
        Header::FUND_ACCOUNT_VPA            => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|string',
        Header::CONTACT_NAME_2              => 'required_without:'. Header::FUND_ACCOUNT_NAME .'|sometimes|string',
        Header::CONTACT_TYPE                => 'sometimes|string',
        Header::CONTACT_ADDRESS             => 'sometimes|string',
        Header::CONTACT_CITY                => 'sometimes|string',
        Header::CONTACT_ZIPCODE             => 'sometimes|string|max:10',
        Header::CONTACT_STATE               => 'sometimes|string',
        Header::CONTACT_EMAIL_2             => 'sometimes|string|email',
        Header::CONTACT_MOBILE_2            => 'sometimes|numeric',
        Header::NOTES_STR_VALUE             => 'sometimes|string|max:256',
    ];

    // Similar to the above rules but this one will contain headers for the rupees version.
    // TODO: Update this as per the final template.
    protected static $payoutRupeesTypeRowRules = [
        Header::RAZORPAYX_ACCOUNT_NUMBER    => 'required|alpha_space_num|between:5,22',
        Header::PAYOUT_PURPOSE              => 'required|string|max:30|alpha_dash_space',
        Header::PAYOUT_NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Header::PAYOUT_AMOUNT_RUPEES        => 'required|regex:/^\d+(\.\d{1,2})?$/',
        Header::PAYOUT_CURRENCY             => 'required|size:3|in:INR',
        Header::PAYOUT_MODE                 => 'required|string|custom',
        Header::PAYOUT_REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Header::FUND_ACCOUNT_ID             => 'sometimes|nullable|public_id|size:17',
        Header::FUND_ACCOUNT_TYPE           => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string|in:bank_account,vpa,wallet',
        Header::FUND_ACCOUNT_NAME           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA            => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::FUND_ACCOUNT_PHONE_NUMBER   => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',wallet|string|nullable',
        Header::CONTACT_NAME_2              => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string|custom',
        Header::FUND_ACCOUNT_EMAIL          => 'sometimes|nullable|string|email',
        Header::CONTACT_TYPE                => 'sometimes|nullable|string',
        Header::CONTACT_EMAIL_2             => 'sometimes|nullable|string|custom',
        Header::CONTACT_MOBILE_2            => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID        => 'sometimes|nullable|string',
        Header::NOTES                       => 'sometimes|nullable|notes',
    ];

    // Expect APPROVE_REJECT_PAYOUT & Payout ID other columns are optional
    protected static $payoutApprovalTypeRowRules = [
        Header::APPROVE_REJECT_PAYOUT       => 'required|size:1|in:A,R',
        Header::P_A_AMOUNT                  => 'sometimes|numeric|between:1,100000000',
        Header::P_A_CURRENCY                => 'sometimes|size:3|in:INR',
        Header::P_A_CONTACT_NAME            => 'sometimes|string',
        Header::P_A_MODE                    => 'sometimes|string',
        Header::P_A_PURPOSE                 => 'sometimes|string',
        Header::P_A_PAYOUT_ID               => 'required|string',
        Header::P_A_CONTACT_ID              => 'sometimes|string',
        Header::P_A_FUND_ACCOUNT_ID         => 'sometimes|string',
        Header::P_A_CREATED_AT              => 'sometimes',
        Header::P_A_ACCOUNT_NUMBER          => 'sometimes',
        Header::P_A_STATUS                  => 'sometimes|string',
        Header::P_A_NOTES                   => 'sometimes|string',
        Header::P_A_FEES                    => 'sometimes|integer',
        Header::P_A_TAX                     => 'sometimes|integer',
        Header::P_A_SCHEDULED_AT            => 'sometimes',
    ];

    protected static $creditTypeRowRules = [
        Header::CREDITS_MERCHANT_ID                 => 'required|alpha_num|size:14',
        Header::CREDIT_POINTS                       => 'required|integer',
        Header::REMARKS                             => 'sometimes|string|nullable|max:255',
        Header::CAMPAIGN                            => 'required|string|max:255',
        Header::PRODUCT                             => 'required|string|in:banking',
        Header::TYPE                                => 'required|string',
    ];

    protected static $settlementOndemandFeatureConfigTypeRowRules = [
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID                 => 'required|string|size:14',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT            => 'required|integer',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT => 'required|integer',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT     => 'required|integer',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT             => 'required|integer',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT          => 'sometimes|integer',
        Header::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS                 => 'required|in:yes,no'
    ];

    protected static $capitalMerchantEligibilityConfigTypeRowRules = [
        Header::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_MERCHANT_ID        => 'required|string|size:14',
        Header::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRODUCT_NAME       => 'required|string',
        Header::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_SEGMENT            => 'required|string',
        Header::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_ELIGIBLE           => 'required|in:yes,no',
        Header::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRE_APPROVED_LIMIT => 'sometimes|nullable|integer',
    ];

    protected static $earlySettlementTrialTypeRowRules = [
        Header::EARLY_SETTLEMENT_TRIAL_MERCHANT_ID  => 'required|string|size:14',
        Header::EARLY_SETTLEMENT_TRIAL_FULL_ACCESS  => 'required|in:yes,no',
        Header::EARLY_SETTLEMENT_TRIAL_DISABLE_DATE => 'required|string|custom',
        Header::EARLY_SETTLEMENT_TRIAL_AMOUNT_LIMIT => 'sometimes|integer',
        Header::EARLY_SETTLEMENT_ES_PRICING         => 'required|integer'
    ];

    protected static $merchantCapitalTagsTypeRowRules = [
        Header::MERCHANT_CAPITAL_TAGS_MERCHANT_ID => 'required|string|size:14',
        Header::MERCHANT_CAPITAL_TAGS_ACTION      => 'required|in:insert,delete',
        Header::MERCHANT_CAPITAL_TAGS_TAGS        => 'required'
    ];

    protected static $terminalNetbankingHdfcRules = [
        Header::HDFC_NB_MERCHANT_ID          => 'required|string|size:14',
        Header::HDFC_NB_GATEWAY_MERCHANT_ID  => 'required|string|max:30|alpha_dash_space',
        Header::HDFC_NB_CATEGORY             => 'required',
        Header::HDFC_NB_TPV                  => 'sometimes|nullable|in:0,1,2',
    ];

    protected static $sendMailRules = [
        Entity::BATCH            => 'required|array|custom',
        Entity::BUCKET_TYPE      => 'required|string',
        Entity::OUTPUT_FILE_PATH => 'required|string',
        Entity::DOWNLOAD_FILE    => 'required|boolean',
        Entity::SETTINGS         => 'sometimes|array|nullable',
    ];

    protected static $sendSMSRules = [
        Entity::BATCH            => 'required|array|custom',
        Entity::SETTINGS         => 'sometimes|array|nullable',
    ];

    protected static $sendMailBatchRules = [
        Entity::TYPE        => 'required|custom',
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
    ];

    protected static $adminBatchCreateRules = [
        Entity::TYPE                            => 'required|in:admin_batch',
        Entity::NAME                            => 'filled|string|max:255',
        Entity::FILE                            => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG                          => 'filled|array',
        Entity::SCHEDULE                        => 'sometimes|numeric',
    ];

    protected static $entityUpdateActionCreateRules = [
        Entity::TYPE            => 'required|in:entity_update_action',
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE            => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG          => 'required|array|custom',
        Entity::SCHEDULE        => 'sometimes|numeric',
    ];

    protected static $entityUpdateActionConfigRules = [
        Constants::BATCH_ACTION => 'required|string|custom',
        Constants::ENTITY       => 'required|string|custom',
        Constants::ACTION       => 'sometimes|string',
    ];


    protected static $merchantStatusActionCreateRules = [
        Entity::TYPE        => 'required|in:merchant_status_action',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG      => 'required|array|custom',
        Entity::SCHEDULE    => 'sometimes|numeric',
    ];

    protected static $merchantActivationCreateRules = [
        Entity::TYPE   => 'required|in:merchant_activation',
        Entity::NAME   => 'filled|string|max:255',
        Entity::FILE   => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG => 'required|array|custom',
    ];

    protected static $submerchantLinkCreateRules = [
        Entity::TYPE   => 'required|in:submerchant_link',
        Entity::NAME   => 'filled|string|max:255',
        Entity::FILE   => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG => 'required|array|custom',
    ];

    protected static $submerchantDelinkCreateRules = [
        Entity::TYPE   => 'required|in:submerchant_delink',
        Entity::NAME   => 'filled|string|max:255',
        Entity::FILE   => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG => 'required|array|custom',
    ];

    protected static $submerchantPartnerConfigUpsertCreateRules = [
        Entity::TYPE     => 'required|in:submerchant_partner_config_upsert',
        Entity::NAME     => 'filled|string|max:255',
        Entity::FILE     => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG   => 'required|array|custom',
    ];


    protected static $submerchantTypeUpdateCreateRules = [
        Entity::TYPE     => 'required|in:submerchant_type_update',
        Entity::NAME     => 'filled|string|max:255',
        Entity::FILE     => 'required|file|max:3072' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG   => 'required|array|custom'
    ];

    protected static $ecollectRblCreateRules = [
        Entity::TYPE                 => 'required|in:ecollect_rbl',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $ecollectIciciCreateRules = [
        Entity::TYPE                 => 'required|in:ecollect_icici',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:102400' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $bankTransferEditCreateRules = [
        Entity::TYPE                 => 'required|in:bank_transfer_edit',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $refundCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $rawAddressCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $fulfillmentOrderUpdateCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $oneCcCodEligibilityAttributeWhitelistUpsertCreateRules = [
        Entity::TYPE                 => 'required|in:one_cc_cod_eligibility_attribute_whitelist_upsert',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::CSV_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $oneCcCodEligibilityAttributeBlacklistUpsertCreateRules = [
        Entity::TYPE                 => 'required|in:one_cc_cod_eligibility_attribute_blacklist_upsert',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::CSV_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $createWalletAccountsCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $createWalletLoadsCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $createWalletContainerLoadsCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
    ];

    protected static $createWalletUserContainersCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:51200' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id'
    ];

    protected static $creditCreateRules = [
        Entity::TYPE    => 'required|in:credit',
        Entity::NAME    => 'filled|string|max:255',
        Entity::FILE    => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID => 'required_without:file|public_id',
    ];

    protected static $internalInstrumentRequestCreateRules = [
        Entity::TYPE        => 'required|in:internal_instrument_request',
        Entity::FILE        => 'required|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $linkedAccountCreateCreateRules = [
        Entity::TYPE                 => 'required|in:linked_account_create',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $paymentTransferCreateRules = [
        Entity::TYPE                 => 'required|in:payment_transfer',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $transferReversalCreateRules = [
        Entity::TYPE                 => 'required|in:transfer_reversal',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID              => 'required_without:file|public_id',
        Entity::SCHEDULE             => 'sometimes|numeric',
    ];

    protected static $payoutLinkBulkValidateRules = [
        Entity::TYPE        => 'required|in:payout_link_bulk',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
    ];

    protected static $virtualAccountEditCreateRules = [
        Entity::TYPE        => 'required|in:virtual_account_edit',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
    ];

    protected static $payoutLinkBulkTypeRowRules = [
        Header::PAYOUT_LINK_BULK_CONTACT_NAME      => 'required|string',
        Header::PAYOUT_LINK_BULK_CONTACT_NUMBER    => 'required_if:'.Header::PAYOUT_LINK_BULK_SEND_SMS.',Yes|string',
        Header::PAYOUT_LINK_BULK_CONTACT_EMAIL     => 'required_if:'.Header::PAYOUT_LINK_BULK_SEND_EMAIL.',Yes|string|email',
        Header::PAYOUT_LINK_BULK_PAYOUT_DESC       => 'required|string',
        Header::CONTACT_TYPE                       => 'required|string',
        Header::PAYOUT_LINK_BULK_AMOUNT            => 'required|regex:/^-?\d+(\.\d{1,2})?$/',
        Header::PAYOUT_LINK_BULK_SEND_SMS          => 'required|string|in:Yes,No',
        Header::PAYOUT_LINK_BULK_SEND_EMAIL        => 'required|string|in:Yes,No',
        Header::PAYOUT_PURPOSE                     => 'required|string',
        Header::PAYOUT_LINK_BULK_REFERENCE_ID      => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_NOTES_TITLE       => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_NOTES_DESC        => 'sometimes|string',
    ];

    protected static $payoutLinkBulkCreateRules = [
        Entity::TYPE        => 'required|in:payout_link_bulk',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::OTP         => 'required|filled|min:4',
        Entity::TOKEN       => 'required|unsigned_id',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static $payoutLinkBulkV2ValidateRules = [
        Entity::TYPE        => 'required|in:payout_link_bulk_v2',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
    ];

    protected static $payoutLinkBulkV2TypeRowRules = [
        Header::PAYOUT_LINK_BULK_CONTACT_NAME      => 'required|string',
        Header::PAYOUT_LINK_BULK_CONTACT_NUMBER    => 'required_if:'.Header::PAYOUT_LINK_BULK_SEND_SMS.',Yes|string',
        Header::PAYOUT_LINK_BULK_CONTACT_EMAIL     => 'required_if:'.Header::PAYOUT_LINK_BULK_SEND_EMAIL.',Yes|string|email',
        Header::PAYOUT_LINK_BULK_PAYOUT_DESC       => 'required|string',
        Header::CONTACT_TYPE                       => 'required|string',
        Header::PAYOUT_LINK_BULK_AMOUNT            => 'required|regex:/^-?\d+(\.\d{1,2})?$/',
        Header::PAYOUT_LINK_BULK_SEND_SMS          => 'required|string|in:Yes,No',
        Header::PAYOUT_LINK_BULK_SEND_EMAIL        => 'required|string|in:Yes,No',
        Header::PAYOUT_PURPOSE                     => 'required|string',
        Header::PAYOUT_LINK_BULK_REFERENCE_ID      => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_NOTES_TITLE       => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_NOTES_DESC        => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_EXPIRY_DATE       => 'sometimes|string',
        Header::PAYOUT_LINK_BULK_EXPIRY_TIME       => 'sometimes|string',
    ];

    protected static $payoutLinkBulkV2CreateRules = [
        Entity::TYPE        => 'required|in:payout_link_bulk_v2',
        Entity::NAME        => 'filled|string|max:255',
        Entity::FILE        => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID     => 'required_without:file|public_id',
        Entity::OTP         => 'required|filled|min:4',
        Entity::TOKEN       => 'required|unsigned_id',
        Entity::CONFIG      => 'filled|array',
    ];

    protected static array $merchantUploadMiqTypeRowRules = [
        Header::MIQ_MERCHANT_NAME                    => 'required',
        Header::MIQ_DBA_NAME                         => 'required',
        Header::MIQ_WEBSITE                          => 'sometimes',
        Header::MIQ_WEBSITE_ABOUT_US                 => 'sometimes',
        Header::MIQ_WEBSITE_TERMS_CONDITIONS         => 'sometimes',
        Header::MIQ_WEBSITE_CONTACT_US               => 'sometimes',
        Header::MIQ_WEBSITE_PRIVACY_POLICY           => 'sometimes',
        Header::MIQ_WEBSITE_PRODUCT_PRICING          => 'sometimes',
        Header::MIQ_WEBSITE_REFUNDS                  => 'sometimes',
        Header::MIQ_WEBSITE_CANCELLATION             => 'sometimes',
        Header::MIQ_WEBSITE_SHIPPING_DELIVERY        => 'sometimes',
        Header::MIQ_CONTACT_NAME                     => 'required',
        Header::MIQ_CONTACT_EMAIL                    => 'required',
        Header::MIQ_TXN_REPORT_EMAIL                 => 'required',
        Header::MIQ_ADDRESS                          => 'required',
        Header::MIQ_CITY                             => 'required',
        Header::MIQ_PIN_CODE                         => 'required',
        Header::MIQ_STATE                            => 'required',
        Header::MIQ_CONTACT_NUMBER                   => 'required',
        Header::MIQ_CIN                              => 'sometimes',
        Header::MIQ_BUSINESS_TYPE                    => 'required',
        Header::MIQ_BUSINESS_PAN                     => 'required',
        Header::MIQ_BUSINESS_NAME                    => 'required',
        Header::MIQ_AUTHORISED_SIGNATORY_PAN         => 'sometimes',
        Header::MIQ_PAN_OWNER_NAME                   => 'required',
        Header::MIQ_BUSINESS_CATEGORY                => 'required',
        Header::MIQ_SUB_CATEGORY                     => 'required',
        Header::MIQ_GSTIN                            => 'sometimes',
        Header::MIQ_BUSINESS_DESCRIPTION             => 'required',
        Header::MIQ_ESTD_DATE                        => 'required',
        Header::MIQ_FEE_MODEL                        => 'required',
        Header::MIQ_UPI_FEE_TYPE                     => 'required',
        Header::MIQ_UPI_FEE_BEARER                   => 'sometimes',
        Header::MIQ_UPI                              => 'sometimes',
        Header::MIQ_NB_FEE_TYPE                      => 'required',
        Header::MIQ_NB_FEE_BEARER                    => 'sometimes',
        Header::MIQ_AXIS                             => 'sometimes',
        Header::MIQ_HDFC                             => 'sometimes',
        Header::MIQ_ICICI                            => 'sometimes',
        Header::MIQ_SBI                              => 'sometimes',
        Header::MIQ_YES                              => 'sometimes',
        Header::MIQ_NB_ANY                           => 'sometimes',
        Header::MIQ_WALLETS_FEE_TYPE                 => 'required',
        Header::MIQ_WALLETS_FEE_BEARER               => 'sometimes',
        Header::MIQ_WALLETS_FREECHARGE               => 'sometimes',
        Header::MIQ_WALLETS_ANY                      => 'sometimes',
        Header::MIQ_DEBIT_CARD_FEE_TYPE              => 'required',
        Header::MIQ_DEBIT_CARD_FEE_BEARER            => 'sometimes',
        Header::MIQ_DEBIT_CARD_0_2K                  => 'sometimes',
        Header::MIQ_DEBIT_CARD_2K_1CR                => 'sometimes',
        Header::MIQ_RUPAY_FEE_TYPE                   => 'required',
        Header::MIQ_RUPAY_FEE_BEARER                 => 'sometimes',
        Header::MIQ_RUPAY_0_2K                       => 'sometimes',
        Header::MIQ_RUPAY_2K_1CR                     => 'sometimes',
        Header::MIQ_CREDIT_CARD_FEE_TYPE             => 'required',
        Header::MIQ_CREDIT_CARD_FEE_BEARER           => 'sometimes',
        Header::MIQ_CREDIT_CARD_0_2K                 => 'sometimes',
        Header::MIQ_CREDIT_CARD_2K_1CR               => 'sometimes',
        Header::MIQ_INTERNATIONAL                    => 'required',
        Header::MIQ_INTL_CARD_FEE_TYPE               => 'required',
        Header::MIQ_INTL_CARD_FEE_BEARER             => 'sometimes',
        Header::MIQ_INTERNATIONAL_CARD               => 'sometimes',
        Header::MIQ_BUSINESS_FEE_TYPE                => 'required',
        Header::MIQ_BUSINESS_FEE_BEARER              => 'sometimes',
        Header::MIQ_BUSINESS                         => 'sometimes',
        Header::MIQ_BANK_ACC_NUMBER                  => 'required',
        Header::MIQ_BENEFICIARY_NAME                 => 'required',
        Header::MIQ_BRANCH_IFSC_CODE                 => 'required',
    ];

    protected static $partnerSubmerchantReferralInviteTypeRowRules = [
        Header::NAME                                 => 'required|alpha_space|max:255',
        Header::EMAIL                                => 'required|email|max:255',
        Header::CONTACT_MOBILE                       => 'sometimes|min:10|max:15|contact_syntax'
    ];

    public function validateConfig($attribute, $value)
    {
        (new Validator())->validateInput('entityUpdateActionConfig', $value);
    }


    public function validateBatchAction($attribute, $batchAction)
    {
        (new Merchant\Validator())->validateBatchAction($attribute, $batchAction);
    }

    public function validateEntity($attribute, $BatchActionEntity)
    {
        (new Merchant\Validator())->validateEntity($attribute, $BatchActionEntity);
    }

    protected function validateBatch($attribute, $value)
    {
        $this->validateInput('sendMailBatch', $value);
    }

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
    }

    protected function validateSubType($attribute, $value)
    {
        Type::validateSubType($value);
    }

    protected function validatePayoutMode($attribute, $value)
    {
        PayoutMode::validateMode($value);
    }

    public function validatePayoutDate($attribute, $value)
    {
        $expectedFormat = 'd/m/Y';

        $d = DateTime::createFromFormat($expectedFormat, $value);

        if (!$d || $d->format($expectedFormat) != $value)
        {
            throw new BadRequestValidationFailureException('Invalid Payout Date format, should be d/m/Y');
        }
    }

    public function validateDisableDate($attribute, $value)
    {
        $expectedFormat = 'd/m/Y';

        $d = DateTime::createFromFormat($expectedFormat, $value);

        if (!$d || $d->format($expectedFormat) != $value)
        {
            throw new BadRequestValidationFailureException('Invalid Disable Date format, should be d/m/Y');
        }
    }

    protected function validateContactName($attribute , $value)
    {
        $this->validateUtf8Encoding($attribute, $value);
    }

    protected function validateContactEmail($attribute , $value)
    {
        $this->validateUtf8Encoding($attribute, $value);
    }

   protected function validateUtf8Encoding($attribute, $value)
    {
        if (is_valid_utf8($value) === false)
        {
            $exception = new BadRequestValidationFailureException(
                "Invalid encoding of $attribute. Non UTF-8 character(s) found.",
                null,
                $value
            );
            $this->getTrace()->traceException($exception , Logger::ERROR, TraceCode::FAILED_TO_VALIDATE_UTF_8_ENCODING,
                [
                    'attribute' => $attribute,
                ]
            );
            throw $exception;
        }
    }

    protected static $payoutRupeesTypeRowValidators = [
        'payout_amount_rupees',
    ];

    protected function validatePayoutAmountRupees($input)
    {
        $amountInRupees = $input[Header::PAYOUT_AMOUNT_RUPEES];

        if ($amountInRupees < 1)
        {
            // The payout amount may not be less than 1.00.
            throw new BadRequestValidationFailureException('The payout amount (in rupees) may not be less than 1.00');
        }
        if ($amountInRupees > 100000000)
        {
            // The payout amount may not be greater than 100000000.00.
            throw new BadRequestValidationFailureException('The payout amount (in rupees) may not be greater than 100000000.00');
        }
    }

    // Verifying than all records on same gateway merchant id have same plans in input.
    protected function validateTerminalCreationEntries($input)
    {
        $failedRecords = [];

         collect($input)->groupBy(Batch\Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID)
            ->map(function ($rows) use (& $failedRecords)
            {
                if (count($rows->pluck(Batch\Header::TERMINAL_CREATION_PLAN_NAME)->unique()) > 1)
                {
                    $failedRecords[] = $rows->pluck('Gateway Merchant ID')->toArray()[0];
                }
            });

        if (count($failedRecords) > 0)
        {
            throw new BadRequestValidationFailureException(json_encode($failedRecords));
        }
    }

    // Grouping input buy pricing plans with plan name and range validating.
    protected function validateBuyPricingRuleEntries($input)
    {
        $groupedRules = (new Pricing\Entity)->groupBuyPricingRules($input)->toArray();
        $failedRules = [];

        foreach ($groupedRules as $rules)
        {
            try
            {
                (new Pricing\Validator)->validateBuyPricingRules($rules);
            }
            catch (\Throwable $t)
            {
                $failedMai = [];
                foreach (Pricing\Entity::$buyPricingMethods as $method)
                {
                    $failedMai[$method] = $rules[0][$method];
                }
                $failedRules[] = $failedMai;
            }
        }

        if (count($failedRules) > 0)
        {
            throw new BadRequestValidationFailureException(json_encode($failedRules));
        }
    }


    /**
     * Throws error if batch is not in a state which can be processed
     */
    public function validateIfProcessable()
    {
        if ($this->entity->isProcessed() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_ALREADY_PROCESSED,
                Entity::STATUS,
                $this->entity->toArray());
        }
        else if ($this->entity->isProcessing() === true)
        {
            if ($this->shouldRetryInProcessingBatch() === true)
            {
                return;
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_UNDER_PROCESSING,
                Entity::STATUS,
                $this->entity->toArray());
        }
        else if ($this->entity->getProcessedCount() > 0)
        {
            //
            // if processed count is greater than 0, that means, batch was processed earlier and it's a retry,
            // check if batch type is disabled for retry
            //
            $type = $this->entity->getType();

            if (Type::isRetryDisabled($type) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_CANNOT_BE_RETRIED,
                    Entity::STATUS,
                    $this->entity->toArray());
            }
        }
    }

    protected function shouldRetryInProcessingBatch()
    {
        // For stuck batches, we want to enable retry based on some conditions.
        $time = Carbon::now()->getTimestamp();

        $type = $this->entity->getType();

        if ((isset(Type::$retryInProcessingBatchTypes[$type]) === true) and
            ($this->entity->getUpdatedAt() < ($time - Type::$retryInProcessingBatchTypes[$type])))
        {
            $updatedAt = $this->entity->getUpdatedAt();

            $this->getTrace()->info(
                TraceCode::RETRY_ALLOWED_FOR_IN_PROCESSING_BATCH,
                [
                    'batch_id'          => $this->entity->getId(),
                    'type'              => $this->entity->getType(),
                    'processed_count'   => $this->entity->getProcessedCount(),
                    'last_updated_at'   => Carbon::createFromTimestamp($updatedAt, Timezone::IST)->format('M d,Y h:i:s A'),
                ]);

            return true;
        }

        return false;
    }

    /**
     * Gets the header rule name, will be used to generate output file header
     * for emandate file entries
     *
     * @return string
     */
    public function getHeaderRule(): string
    {
        $rules = $this->getRuleNames();

        return $rules['header_rule'];
    }

    /**
     * Validates entries(array) of batch input file before
     * creating the batch entity.
     *
     * @param array $entries
     * @param array $params
     * @param ME    $merchant
     */
    public function validateEntries(array & $entries, array $params, ME $merchant)
    {
        $rules = $this->getRuleNames();

        // Limit validations
        Limit::validate($rules['limit_rule'], count($entries));

        // Header validations
        Header::validate($rules['header_rule'], array_keys(current($entries)));

        //adding validation for type payment_page
        if ($rules['header_rule'] === TYPE::PAYMENT_PAGE)
        {
            Header::validatePaymentPageHeaders(array_keys(current($entries)), $params['config']);
        }

        //
        // Formatted notes can be present in entries. Addition to above validation (where existence of notes header is
        // validated) per batch type, here we validate the keys count & their lengths to avoid multiple failure at later
        // stage (consumption - entity building etc in respective processors).
        //
        $firstEntry = current($entries);
        if (isset($firstEntry[Header::NOTES]) === true)
        {
            Header::validateNotesKeys(array_keys($firstEntry[Header::NOTES]));
        }

        // Data validations
        $validatorMethodName = $rules['validator_method'];

        if (method_exists($this, $validatorMethodName) === true)
        {
            $this->$validatorMethodName($entries, $params, $merchant);
        }
    }

    public function validateTerminalNetbankingHdfcEntries($entries, array $params, $merchant)
    {
        foreach ($entries as $entry)
        {
            $this->validateInput('terminal_netbanking_hdfc', $entry);
        }
    }

    public function validateAuthForBatchType()
    {
        $batch = $this->entity;
        $batchType = $batch->getType();

        $basicAuth = App::getFacadeRoot()['basicauth'];

        //
        // 1/ The outer brackets are very important!
        //    Gives an incorrect result otherwise.
        // 2/ We need the `proxyAuth` check for the following reason:
        //    In basic auth, we set `app=true` if the route is
        //    private route but is made via dashboard (via proxy).
        //    So, these should not be considered as made via
        //    app (cron, lambda, etc) / admin (dashboard).
        //    Hence, we remove proxyAuth explicitly.
        //    But, for some reason, if a route is a proxy route already,
        //    `app` is not set to `true`. Need to check why.
        // 3/ Currently, `/admin/batches` is put under admin routes
        //    and `/batches` route is put under proxy routes.
        //    If `/batches` is called from app/admin, it'll fail at route middleware.
        //    If emandate batch is created via proxy auth
        //    (bypassed route middleware - through lambda or recon or some other code flow),
        //    it'll fail at this validation layer. If it's created via private auth, it'll
        //    anyway fail because it's neither appAuth nor proxyAuth. If it's created via
        //    private auth via dashboard, it'll again fail because of the proxyAuth condition.
        //
        $onlyAppAuth = (($basicAuth->isAppAuth() === true) and
                        ($basicAuth->isProxyAuth() === false));

        if (Type::isAppType($batchType) === true)
        {
            $this->validateAppTypeBatch($batch, $onlyAppAuth);
        }
        else
        {
            $this->validateNonAppTypeBatch($batch);
        }
    }

    protected function validateAppTypeBatch(Entity $batch, bool $appAuth)
    {
        $merchantId = $batch->getMerchantId();

        if ($appAuth === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid type passed for batch creation',
                Entity::TYPE,
                $this->getTraceDataForTypeValidation($appAuth, $batch)
            );
        }

        if ($merchantId !== Merchant\Account::SHARED_ACCOUNT)
        {
            throw new BadRequestValidationFailureException(
                'Invalid merchant trying to create an app-type batch: ' . $merchantId,
                Entity::MERCHANT_ID,
                $this->getTraceDataForTypeValidation($appAuth, $batch)
            );
        }
    }

    protected function validateNonAppTypeBatch(Entity $batch)
    {
        $merchantId = $batch->getMerchantId();

        if ($merchantId === Merchant\Account::SHARED_ACCOUNT)
        {
            throw new BadRequestValidationFailureException(
                'Invalid merchant trying to create a non-app-type batch: ' . $merchantId,
                Entity::MERCHANT_ID,
                $this->getTraceDataForTypeValidation(false, $batch)
            );
        }
    }

    protected function getTraceDataForTypeValidation(bool $appAuth, Entity $batch)
    {
        return [
            'app_auth'      => $appAuth,
            'batch_id'      => $batch->getId(),
            'batch_type'    => $batch->getType(),
        ];
    }

    /**
     * Gets the rule names that will be used for header, limit and data validation
     * for emandate file entries
     *
     * @return array
     */
    protected function  getRuleNames(): array
    {
        $type = $this->entity->getType();
        $subType = $this->entity->getSubType();
        $gateway = $this->entity->getGateway();

        // Calls validate method of corresponding type.
        $limitValidatorName = $type;
        $headerRuleName = $type;
        $validatorMethodName = 'validate' . studly_case($type);

        // Add sub_type to the names
        if (empty($subType) === false)
        {
            $headerRuleName .= '_' . $subType;
            $limitValidatorName .= '_' . $subType;
            $validatorMethodName .= studly_case($subType);
        }

        // Add gateway to the names
        if (empty($gateway) === false)
        {
            $headerRuleName .= '_' . strtolower($gateway);
            $limitValidatorName .= '_' . strtolower($gateway);
            $validatorMethodName .= studly_case($gateway);
        }

        $validatorMethodName .= 'Entries';

        return [
            'header_rule'       => $headerRuleName,
            'limit_rule'        => $limitValidatorName,
            'validator_method'  => $validatorMethodName,
        ];
    }

    // TODO: move to batch service
    // Ok to keep it here temporarily because uploaded file is expected to be a small file.
    protected function validateBankingAccountActivationCommentsEntries(array &$entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $bankReferenceNumber = $entry[Header::RZP_REF_NO];

            if ((empty($bankReferenceNumber) === true) or (is_numeric($bankReferenceNumber) === false))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_RZP_REF_NO);
            }
        }
    }

    protected function validateVirtualAccountEditEntries(array &$entries, array $params, ME $merchant)
    {

        if ($merchant->isFeatureEnabled(Feature::VA_EDIT_BULK) === false)
        {
            throw new BadRequestValidationFailureException(
                'Bulk VA edit is not enabled for the merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        foreach ($entries as $entry)
        {
            $vaId = $entry[Header::VIRTUAL_ACCOUNT_ID];

            if (strlen($vaId) != 17)
            {
                throw new BadRequestValidationFailureException("Invalid Virtual Account Id");
            }

            $dt = $entry[Header::EXPIRE_BY];

            $dtime = DateTime::createFromFormat("d-m-Y H:i", $dt, new \DateTimeZone('Asia/Kolkata'));

            $currentTimestamp = Carbon::now()->getTimestamp();

            if ($dtime === false)
            {
                throw new BadRequestValidationFailureException("Invalid date time format");
            }

            $dtTimestamp = $dtime->getTimestamp();

            // the createFromFormat accepts month value greater than 12. It set the month value as input modulus 12 +1
            // and increase the year account accordingly, we don't want to allow that so doing string compare of te input
            // and the one after reformatting from the timestamp of the converted datetime
            $date = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));

            $date->setTimestamp($dtTimestamp);

            $formattedDate = $date->format("d-m-Y H:i");

            if ($dt != $formattedDate)
            {
                throw new BadRequestValidationFailureException("Invalid date time format");
            }

            if ($dtTimestamp < $currentTimestamp)
            {
                throw new BadRequestValidationFailureException("Expiry time must be greater than current time");
            }
        }
    }

    protected function validateRefundEntries(array & $entries, array $params, ME $merchant)
    {
        $existingPaymentIds = [];

        if ($merchant->isFeatureEnabled(Feature::DISABLE_REFUNDS) === true)
        {
            throw new BadRequestValidationFailureException(
                'Refunds are not allowed on this account',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        $isInstantRefundDisabled = $merchant->isFeatureEnabled(Feature::DISABLE_INSTANT_REFUNDS) === true;

        foreach ($entries as $entry)
        {
            $amount     = $entry[Header::AMOUNT];
            $paymentId  = $entry[Header::PAYMENT_ID];

            if (empty($paymentId) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_PAYMENT_ID);
            }

            if (empty($amount) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            if ((is_numeric($amount) === false) or ($amount <= 0))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            // Batch File should not contain multiple entries for the same
            // payment id

            if (in_array($paymentId, $existingPaymentIds))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_PAYMENT_ID);
            }

            //
            // If merchant has instant refund disabled in their feature set,
            // dont even allow them to process a refund batch with optimum speed
            //
            if (($isInstantRefundDisabled === true) and
                (empty($entry[Header::SPEED]) === false) and
                (in_array(strtolower($entry[Header::SPEED]), [Refund\Constants::OPTIMUM], true) === true))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INSTANT_REFUNDS_DISABLED);
            }

            //
            // Speed is optional, but if specified,
            // should strictly contain only one of the two values
            //
            if ((empty($entry[Header::SPEED]) === false) and
                (in_array(strtolower($entry[Header::SPEED]), [Refund\Constants::NORMAL, Refund\Constants::OPTIMUM], true) === false))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_SPEED);
            }

            $existingPaymentIds[] = $paymentId;
        }
    }

    /**
     * Validates payment link entries.
     * - Creates dummy invoice object and validates them as it happens
     *   otherwise in creation by API flow. This approach let us re-use code.
     *
     * @param array $entries
     * @param array $params
     * @param ME    $merchant
     *
     * @throws BadRequestException
     */
    protected function validatePaymentLinkEntries(array & $entries, array $params, ME $merchant)
    {
        // Skip the pre validation for payment links to reduce the execution time to support large files.
        // TODO: Move this to async!
        if (count($entries) > Constants::ROW_LEVEL_VALIDATION_THRESHOLD)
        {
            return;
        }

        //
        // Instead of new Validator instance, we get validator out of a dummy invoice entity having
        // Merchant\Entity associated. This is required for custom validations run in create rules
        // which uses merchant's max payment amount configurations etc.
        //
        $validator = (new Invoice\Entity)
                        ->merchant()
                        ->associate($merchant)
                        ->getValidator();

        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry) use ($validator)
        {
            $input = Helpers\PaymentLink::getEntityInput($entry);

            $rule = $input[Invoice\Entity::DRAFT] === '0' ?
                Invoice\Validator::CREATE_ISSUED :
                Invoice\Validator::CREATE_DRAFT;

            $validator->validateInput($rule, $input);
        });
    }

    protected function validateVirtualBankAccountEntries(array & $entries, array $params, ME $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::VIRTUAL_ACCOUNTS) === false)
        {
            throw new BadRequestValidationFailureException(
                'Batch type is not enabled for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }

    protected function validateRecurringChargeEntries(array & $entries, array $params, ME $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::CHARGE_AT_WILL) === false)
        {
            throw new BadRequestValidationFailureException(
                'Batch type is not enabled for merchant',
                null,
                [
                    Entity::ID          => $this->entity->getId(),
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }

    protected function validateRecurringChargeBulkEntries(array & $entries, array $params, ME $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::CHARGE_AT_WILL) === false)
        {
            throw new BadRequestValidationFailureException(
                'Batch type is not enabled for merchant',
                null,
                [
                    Entity::ID          => $this->entity->getId(),
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }

    protected function validatePayoutEntries(array & $entries, array $params, ME $merchant)
    {
        $countOfPayouts = count($entries);

        $this->assertCustomLimitForMerchant($merchant, $countOfPayouts);

        if ($merchant->isFeatureEnabled(Feature::PAYOUT) === false)
        {
            throw new BadRequestValidationFailureException('Batch type is not enabled for merchant');
        }

        $expectedAmountType = $this->getAmountTypeForPayouts($merchant);

        $app = App::getFacadeRoot();

        $variant  = $app['razorx']->getTreatment($merchant->getId(),
                                                 Merchant\RazorxTreatment::BULK_PAYOUTS_IMPROVEMENTS_ROLLOUT,
                                                 Mode::LIVE,
                                                 3);

        if (strtolower($variant) === 'control')
        {
            $expectedAmountType = BatchHelper::PAISE;
        }

        // Since Payouts Batch uses an API for MFN, we allow paise for MFN merchants
        if (($app['api.route']->getCurrentRouteName() === 'payouts_batch_create' or
                $app['api.route']->getCurrentRouteName() === 'payouts_batch_create_x_demo_cron') and
            ($merchant->isFeatureEnabled(Feature::PAYOUTS_BATCH)))
        {
            $expectedAmountType = BatchHelper::PAISE;
        }

        $actualAmountType = $this->getActualAmountTypeBasedOnUploadedData($entries[0]);

        if ($actualAmountType != $expectedAmountType)
        {
            $message = 'You seem to have entered a wrong amount header. The amount has to be entered in ' .
                       ucfirst($expectedAmountType) . ' format instead of ' . ucfirst($actualAmountType) . ' format';

            throw new BadRequestValidationFailureException($message);
        }

        $this->getTrace()->info(TraceCode::BULK_PAYOUTS_VALIDATION_BEGINS, [
            'count' => $countOfPayouts
        ]);

        // For existing merchants who use bulk, we will keep letting them upload their files with amount as paise
        if ($expectedAmountType === BatchHelper::PAISE)
        {
            $operation = 'payoutTypeRow';

            if ($merchant->isFeatureEnabled(Feature::ALLOW_COMPLETE_ERROR_DESC))
            {
                $this->validateEntriesWithPublicExceptionHandledAndCompleteErrorDescription($entries, $operation);
            }
            else
            {
                $this->validateEntriesWithPublicExceptionHandled($entries, function(array $entry) use ($operation) {
                    $this->validateInput($operation, $entry);
                });
            }
        }
        // For new merchants as well as existing merchants that have transferred over to amount type rupees,
        // we will allow them to upload their file only in rupees.
        else
        {
            $operation = 'payoutRupeesTypeRow';

            if ($merchant->isFeatureEnabled(Feature::ALLOW_COMPLETE_ERROR_DESC))
            {
                $this->validateEntriesWithPublicExceptionHandledAndCompleteErrorDescription($entries, $operation);
            }
            else
            {
                $this->validateEntriesWithPublicExceptionHandled($entries, function(array $entry) use ($operation) {
                    $this->validateInput($operation, $entry);
                });
            }
        }

        $this->getTrace()->info(TraceCode::BULK_PAYOUTS_VALIDATION_ENDS, [
            'count' => $countOfPayouts
        ]);

        // Following are Payout Amount Validations.
        // Only to be done if merchant does not have CA. If the merchant has a current account,
        // then we do not know his current balance in real time, and hence we simply skip these validations

        if ($merchant->hasDirectBankingBalance() === true)
        {
            return;
        }

        // After validating contents per row only should do following aggregate validations.

        if ($expectedAmountType === BatchHelper::PAISE)
        {
            $totalPayoutAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT));
        }
        else
        {
            $totalPayoutAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT_RUPEES)) * 100;
        }

        $bankingBalance = $merchant->sharedBankingBalance->getBalanceWithLockedBalanceFromLedger();

        if ($totalPayoutAmount > $bankingBalance)
        {
            throw new BadRequestValidationFailureException(
                'Total payout amount in uploaded file is more than the available account balance',
                Entity::FILE,
                compact('totalPayoutAmount', 'bankingBalance'));
        }
    }

    protected function validatePayoutLinkBulkRowContactInfo($rowData)
    {
        if(empty($rowData[Header::PAYOUT_LINK_BULK_CONTACT_NUMBER]) === true
            && empty($rowData[Header::PAYOUT_LINK_BULK_CONTACT_EMAIL]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Both contact number and contact email cannot be empty',
                Entity::FILE);
        }
    }

    protected function validatePayoutLinkBulkRowNotesData($rowData)
    {
        // If Notes Title field's value is present, Notes Description should also be there
        if(empty($rowData[Header::PAYOUT_LINK_BULK_NOTES_TITLE]) === true
            && empty($rowData[Header::PAYOUT_LINK_BULK_NOTES_DESC]) === false)
        {
            throw new BadRequestValidationFailureException(
                'Notes title missing',
                Entity::FILE);
        }

        // vice versa of above
        if(empty($rowData[Header::PAYOUT_LINK_BULK_NOTES_TITLE]) === false
            && empty($rowData[Header::PAYOUT_LINK_BULK_NOTES_DESC]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Notes description missing',
                Entity::FILE);
        }
    }

    protected function validatePayoutLinkBulkRowExpiryData($rowData)
    {
        // If Expiry Time field's value is present, Expiry Date should also be there.
        // If Expiry Date is present, default expiry time will be 11:59 PM
        if(empty($rowData[Header::PAYOUT_LINK_BULK_EXPIRY_TIME]) === false
            && empty($rowData[Header::PAYOUT_LINK_BULK_EXPIRY_DATE]) === true)
        {
            throw new BadRequestValidationFailureException(
                'Expiry Date missing but Expiry Time present',
                Entity::FILE);
        }
    }

    protected function validatePayoutLinkExpiryDateFormat($value)
    {
        $expectedFormat = 'd/m/Y';

        $d = DateTime::createFromFormat($expectedFormat, $value);

        if (!$d || $d->format($expectedFormat) != $value)
        {
            throw new BadRequestValidationFailureException('Invalid Expiry Date format should be DD/MM/YYYY');
        }
    }

    protected function validatePayoutLinkExpiryTimeFormat($value)
    {
        $expectedFormat = 'H:i';

        $d = DateTime::createFromFormat($expectedFormat, $value);

        if (!$d || $d->format($expectedFormat) != $value)
        {
            throw new BadRequestValidationFailureException('Invalid Expiry Time format should be HH:MM');
        }
    }

    protected function validatePayoutLinkBulkEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('payoutLinkBulkTypeRow', $entry);

            $this->validatePayoutLinkBulkRowContactInfo($entry);

            $this->validatePayoutLinkBulkRowNotesData($entry);
        });
    }

    protected function validatePayoutLinkBulkV2Entries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('payoutLinkBulkV2TypeRow', $entry);

            $this->validatePayoutLinkBulkRowContactInfo($entry);

            $this->validatePayoutLinkBulkRowNotesData($entry);

            $this->validatePayoutLinkBulkRowExpiryData($entry);

            if(empty($entry[Header::PAYOUT_LINK_BULK_EXPIRY_DATE]) === false)
            {
                $this->validatePayoutLinkExpiryDateFormat($entry[Header::PAYOUT_LINK_BULK_EXPIRY_DATE]);
            }

            if(empty($entry[Header::PAYOUT_LINK_BULK_EXPIRY_TIME]) === false)
            {
                $this->validatePayoutLinkExpiryTimeFormat($entry[Header::PAYOUT_LINK_BULK_EXPIRY_TIME]);
            }
        });
    }

    protected function validatePayoutApprovalEntries(array & $entries, array $params, ME $merchant)
    {
        //Limit number of entries. We are using same bulk payout count restriction
        $countOfPayouts = count($entries);

        $this->assertCustomLimitForMerchant($merchant, $countOfPayouts);

        //Check other validations required
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('payoutApprovalTypeRow', $entry);
        });
    }

    protected function validateTallyPayoutEntries(array & $entries, array $params, ME $merchant)
    {
        // Limit number of entries. We are using same bulk payout count restriction
        $countOfPayouts = count($entries);

        $this->assertCustomLimitForMerchant($merchant, $countOfPayouts);

        if ($merchant->isFeatureEnabled(Feature::PAYOUT) === false)
        {
            throw new BadRequestValidationFailureException('Batch type is not enabled for merchant');
        }

        $this->getTrace()->info(TraceCode::TALLY_PAYOUTS_VALIDATION_BEGINS, [
            'count' => $countOfPayouts,
            'entries' => $entries
        ]);

        //Check other validations required
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('tallyPayoutTypeRow', $entry);
        });

        $this->getTrace()->info(TraceCode::TALLY_PAYOUTS_VALIDATION_ENDS, [
            'count' => $countOfPayouts
        ]);

        // Following are Payout Amount Validations.
        // Only to be done if merchant does not have CA. If the merchant has a current account,
        // then we do not know his current balance in real time, and hence we simply skip these validations

        if ($merchant->hasDirectBankingBalance() === true)
        {
            return;
        }

        // After validating contents per row only should do following aggregate validations.
        $totalPayoutAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT_RUPEES));

        $bankingBalance = $merchant->sharedBankingBalance->getBalanceWithLockedBalance();

        if ($totalPayoutAmount > $bankingBalance)
        {
            throw new BadRequestValidationFailureException(
                'Total payout amount in uploaded file is more than the available account balance',
                Entity::FILE,
                compact('totalPayoutAmount', 'bankingBalance'));
        }
    }

    protected function validateCreditEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('creditTypeRow', $entry);
        });
    }

    protected function validateLinkedAccountEntries(array & $entries, array $params, ME $merchant)
    {
        //
        // Batch creation for linked account should only be allowed for
        // marketplace merchant accounts.
        //
        if ($merchant->isMarketplace() === false)
        {
            throw new BadRequestValidationFailureException(
                'Linked account creation not allowed for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        //
        // TODO:
        // - Probably should rename these methods to validate<BatchType>Input() as
        //   it now does more than validating just the input entries.
        //
    }

    protected function validateEmandateRegisterHdfcEntries(
        array & $entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $entry = array_map('trim', $entry);

            foreach (self::$emandateRegisterHdfcRequiredEntries as $attr => $errorMessage)
            {
                if (empty($attr) === true)
                {
                    throw new BadRequestValidationFailureException(
                        $errorMessage, $attr, $entry);
                }
            }
        }
    }

    protected function validateEmandateDebitHdfcEntries(
        array & $entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $entry = array_map('trim', $entry);

            foreach (self::$emandateDebitHdfcRequiredHeaders as $attr => $errorMessage)
            {
                if (empty($attr) === true)
                {
                    throw new BadRequestValidationFailureException(
                        $errorMessage, $attr, $entry);
                }
            }
        }
    }

    protected function validateSubMerchantEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateInput('subMerchantConfig', $params[Entity::CONFIG] ?? []);

        $partnerId = $params[Entity::CONFIG][ME::PARTNER_ID] ?? null;

        /** @var Merchant\Entity $partner */
        $partner = (new Merchant\Repository)->findOrFailPublic($partnerId);

        if ($partner->isNonPurePlatformPartner() === false)
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                null,
                [
                    Entity::MERCHANT_ID => $partner->getId(),
                ]);
        }
    }

    protected function validateInstantActivationEntries(array & $entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $entry = array_map('trim', $entry);

            foreach (self::$instantActivationRequiredEntries as $attr => $errorMessage)
            {
                if (empty($attr) === true)
                {
                    throw new BadRequestValidationFailureException(
                        $errorMessage, $attr, $entry);
                }
            }
        }
    }

    protected function validateContactEntries(array & $entries, array $params, ME $merchant)
    {
        $validator = new ContactModel\Validator;

        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry) use ($validator)
        {
            $input = Helpers\Contact::getContactInput($entry);

            $validator->validateInput('create', $input);
        });
    }

    protected function validateFundAccountEntries(array & $entries, array $params, ME $merchant)
    {
        $operation = 'fundAccountTypeRow';

        if ($merchant->isFeatureEnabled(Feature::ALLOW_COMPLETE_ERROR_DESC))
        {
            $this->validateEntriesWithPublicExceptionHandledAndCompleteErrorDescription($entries, $operation);
        }
        else
        {
            $this->validateEntriesWithPublicExceptionHandled($entries, function(array $entry) use ($operation) {
                $this->validateInput($operation, $entry);
            });
        }
    }

    protected function validateFundAccountV2Entries(array & $entries, array $params, ME $merchant)
    {
        $operation = 'fundAccountV2TypeRow';

        if ($merchant->isFeatureEnabled(Feature::ALLOW_COMPLETE_ERROR_DESC))
        {
            $this->validateEntriesWithPublicExceptionHandledAndCompleteErrorDescription($entries, $operation);
        }
        else
        {
            $this->validateEntriesWithPublicExceptionHandled($entries, function(array $entry) use ($operation) {
                $this->validateInput($operation, $entry);
            });
        }
    }


    /**
     * Validates batch entry.
     * @throws BadRequestValidationFailureException
     */
    protected function validateMerchantUploadMiqEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('merchantUploadMiqTypeRow', $entry);
        });
    }

    protected function validateAdjustmentEntries(array & $entries, array $params, ME $merchant)
    {
        $referenceIds = array_map(function ($entry)
                                            {
                                                return $entry[Header::ADJUSTMENT_REFERENCE_ID];
                                            }, $entries);

        $nonUniqueIds =array_diff_assoc($referenceIds, array_unique($referenceIds));

        if (empty($nonUniqueIds) !== true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_VALIDATION_FAILURE, null, null, 'non unique reference_id found' . implode(' ', $nonUniqueIds));
        }

        foreach ($entries as $key => $entry)
        {
            $entry[Header::ADJUSTMENT_BALANCE_TYPE] = trim($entry[Header::ADJUSTMENT_BALANCE_TYPE]) ?:
                                                                Merchant\Balance\Type::PRIMARY;

            $entries[$key] = $entry;

            $this->checkValidBalanceTypeForAdjustment($entry[Header::ADJUSTMENT_BALANCE_TYPE], $entry[Header::ADJUSTMENT_REFERENCE_ID]);
        }
    }

    public function validateSettlementOndemandFeatureConfigEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('settlementOndemandFeatureConfigTypeRow', $entry);
        });
    }

    public function validateCapitalMerchantEligibilityConfigEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('capitalMerchantEligibilityConfigTypeRow', $entry);
        });
    }

    public function validateEarlySettlementTrialEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('earlySettlementTrialTypeRow', $entry);
        });
    }

    public function validateMerchantCapitalTagsEntries(array & $entries, array $params, ME $merchant)
    {
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('merchantCapitalTagsTypeRow', $entry);
        });
    }

    private function checkValidBalanceTypeForAdjustment(string $balanceType, string $referenceId)
    {
        $validBalanceTypes = [
            Merchant\Balance\Type::PRIMARY,
            Merchant\Balance\Type::BANKING,
            Merchant\Balance\Type::COMMISSION,
        ];

        if (in_array($balanceType, $validBalanceTypes, true) === false)
        {
            throw new BadRequestValidationFailureException('invalid balance type'. $balanceType . 'for reference id'. $referenceId);
        }
    }

    protected function validateEntriesWithPublicExceptionHandledAndCompleteErrorDescription(array & $entries, $operation)
    {
        $errors          = [];
        $erroneousRows   = [];
        $customFieldRules = [];

        $rulesVar = $this->getRulesVariableName($operation);

        $rules = static::$$rulesVar;

        foreach ($rules as $key => $value)
        {
            $rule = explode('|', $value);

            $customRule = preg_grep('/^custom:?.*$/', array_values($rule));

            if (empty($customRule) === false)
            {
                $rules[$key] = str_replace('|' . array_values($customRule)[0], '', $value);

                $customFieldRules[$key] = array_values($customRule)[0];
            }
        }

        foreach ($entries as $seq => $entry)
        {
            try
            {
                $this->validateInputByRules($operation, $entry, $rules);

                $this->runValidators($operation, $entry);

                $error[Header::ERROR_CODE]        = null;
                $error[Header::ERROR_DESCRIPTION] = null;
            }
            catch (BaseException $e)
            {
                $error[Header::ERROR_CODE]        = $e->getError()->getPublicErrorCode();
                $error[Header::ERROR_DESCRIPTION] = $e->getError()->getDescription();

                $errors[$seq] = $error;
                array_push($erroneousRows, $seq + 1);
            }

            $messages = [];
            foreach ($customFieldRules as $key => $rule)
            {
                try
                {
                    if (array_key_exists($key, $entry))
                    {
                        (new Validator)->setStrictFalse()->validateInputByRules($operation, $entry, [$key => $rule]);
                    }
                }
                catch (BaseException $e)
                {
                    $messages[] = $e->getError()->getDescription();
                }
            }
            if (empty($messages) === false)
            {
                // Adding vertical tab between error messages to keep it inside same field
                // in ouput csv error file for batch payouts
                $message                   = implode("\v", $messages);

                $error[Header::ERROR_CODE] = $e->getError()->getPublicErrorCode();

                if (empty($error[Header::ERROR_DESCRIPTION]) === false)
                {
                    $error[Header::ERROR_DESCRIPTION] = $error[Header::ERROR_DESCRIPTION] . "\v" . $message;
                }
                else
                {
                    $error[Header::ERROR_DESCRIPTION] = $message;
                }
                $errors[$seq] = $error;

                array_push($erroneousRows, $seq + 1);
            }

            $entries[$seq] += $error;
        }

        $errorsCount = count($errors);

        // If request done via earlier direct upload flow (instead of validation flow), throw 4XX.
        if (($errorsCount > 0) and ($this->entity->isCreatedByFileUpload() === true))
        {
            throw new BadRequestValidationFailureException(
                sprintf(
                    'There are validation errors in %s %s of the file',
                    $errorsCount,
                    $errorsCount === 1 ? 'row' : 'rows'),
                Entity::FILE,
                array_slice($errors, 0, 15, true));
        }
    }

    protected function validateInputByRules($operation, $input, $rules)
    {
        if ($this->strict)
        {
            $invalidKeys = array_keys(array_diff_key($input, $rules));

            if (count($invalidKeys) > 0)
            {
                $this->throwExtraFieldsException($invalidKeys);
            }
        }

        $customAttributes = $this->getCustomAttributes($operation);

        $validator = LaravelValidator::make(
            $input,
            $rules,
            array(),
            $customAttributes);

        $this->laravelValidatorInstance = $validator;

        $validator->setEntityValidator($this);

        if ($validator->fails())
        {
            $this->processValidationFailureWithCompleteMessage($validator->messages(), $operation, $input);
        }
    }

    protected function validateEntriesWithPublicExceptionHandled(array & $entries, \Closure $validator)
    {
        // Indexed errors map against row number.
        $errors = [];
        $erroneousRows = [];

        foreach ($entries as $seq => $entry)
        {
            try
            {
                $validator($entry);

                $error[Header::ERROR_CODE] = null;
                $error[Header::ERROR_DESCRIPTION] = null;
            }
            catch (BaseException $e)
            {
                $error[Header::ERROR_CODE] = $e->getError()->getPublicErrorCode();
                $error[Header::ERROR_DESCRIPTION] = $e->getError()->getDescription();

                $errors[$seq] = $error;
                array_push($erroneousRows , $seq+1);
            }

            $entries[$seq] += $error;
        }

        if (count($erroneousRows) > 0)
        {
            $this->getTrace()->info(TraceCode::VALIDATION_ERROR_IN_BATCH_FILE_ROW,
                [
                    'row_numbers' => $erroneousRows,
                ]
            );
        }

        $errorsCount = count($errors);

        // If request done via earlier direct upload flow (instead of validation flow), throw 4XX.
        if (($errorsCount > 0) and ($this->entity->isCreatedByFileUpload() === true))
        {
            throw new BadRequestValidationFailureException(
                sprintf(
                    'There are validation errors in %s %s of the file',
                    $errorsCount,
                    $errorsCount === 1 ? 'row' : 'rows'),
                Entity::FILE,
                array_slice($errors, 0, 15, true));
        }
    }

    /**
     * Validates otp while creating a batch.
     * E.g. for creating payout type batch otp confirmation by logged in user is required.
     * @param array $input
     */
    public function validateOtp(array $input)
    {
        if (isset($input[Entity::OTP], $input[Entity::TOKEN]) === false)
        {
            return;
        }

        $auth = app()->basicauth;

        //TODO : Doing this for the bulk payout approval. This is not how it should be done,
        // ideally would have wanted to take the action as a param, but because of previously hard coded create_{}_batch, needed to do this
        // Change After this is fixed
        switch($input[Entity::TYPE])
        {
            case 'payout_approval':
                $action = 'bulk_payout_approve';
                break;

            //TODO: Doing this for now as a temporary fix (as changes in raven is also required to support this),
            // Will change to create_bulk_payout_link_batch (the default behaviour) once done with raven side changes
            case 'payout_link_bulk':
            // Doing this, as we expect payout_link_bulk and payout_link_bulk_v2 to behave the same way
            case 'payout_link_bulk_v2':
                $action = 'create_bulk_payout_link';
                break;

            // Doing this, as we expect payout and tally_payout to behave the same way
            case 'tally_payout':
                $action = 'create_payout_batch';
                break;

            default:
                $action = "create_{$input[Entity::TYPE]}_batch";
        }

        $params   = [
            Entity::OTP         => $input[Entity::OTP],
            Entity::TOKEN       => $input[Entity::TOKEN],
            User\Entity::ACTION => $action,
        ];

        (new User\Core)->verifyOtp($params, $auth->getMerchant(), $auth->getUser(), $this->isTestMode());
    }

    protected function validateLinkedAccountReversalEntries(array & $entries, array $params, ME $merchant)
    {
        $existingTransferIds = [];

        if ($merchant->isFeatureEnabled(Feature::ALLOW_REVERSALS_FROM_LA) === false)
        {
            throw new BadRequestValidationFailureException(
                'Refunds are not allowed on this linked account',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }

        foreach ($entries as $entry)
        {
            $amount      = $entry[Header::AMOUNT_IN_PAISE];
            $transferId  = $entry[Header::TRANSFER_ID];

            if (empty($transferId) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TRANSFER_ID);
            }

            if ((empty($amount) === true) or (is_numeric($amount) === false) or ($amount < 100))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT);
            }

            // Batch File should not contain multiple entries for the same
            // transfer id

            if (in_array($transferId, $existingTransferIds))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_TRANSFER_ID);
            }

            $existingTransferIds[] = $transferId;
        }
    }

    public function validateBatchTypeForUserRole(string $userRole, string $variant, string $batchType)
    {
        $batchTypeRoles = (new UserRolesScope())->getRouteBatchTypeUserRoles($batchType);

        $hasAccess = in_array($userRole, $batchTypeRoles,true);

        /*
         * there is two conditions
         * 1. if role does not have access throw error
         * 2. if current user role has access then check whether role is Epos or Not
         *  if role is not epos means it can access that route because we are restricting only epos users
         *  if user role is epos then only those epos role user can access whose MIDs are whitelisted by experiment.
         */
        if(($hasAccess === false) or
           (($userRole === Role::SELLERAPP) and
            ($variant !== 'on')))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }
    }

    public function validateAdminRoleIfApplicable(\RZP\Models\Admin\Admin\Entity $admin , string $batchType)
    {
        if (isset(Type::$batchToAdminPermissionMapping[$batchType]) === true)
        {
            if (in_array(Type::$batchToAdminPermissionMapping[$batchType], $admin->getPermissionsList(), true) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND);
            }
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_BATCH_TYPE_PERMISSION_MISSING);
        }
    }

    /**
     * If user has not required permission to trigger dedupe workflow then throw an error.
     * @param Admin\Admin\Entity $admin
     * @param array $input
     * @param $org
     * @throws BadRequestException
     */
    public function validatePermissionForDedupe(Admin\Admin\Entity $admin, array $input, $org)
    {
        if (isset($input['config']['dedupe']) === true)
        {
            if(($input['config']['dedupe'] == '1') and ($org->isFeatureEnabled(Feature::ORG_SUB_MERCHANT_MCC_PENDING) === true))
            {
                if (in_array(Permission::SUB_MERCHANT_DEDUPE, $admin->getPermissionsList(), true) === false)
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND ,
                        "dedupe",$input['config']['dedupe'],
                        "User does not have required permission to perform dedupe"
                    );
                }
            }
        }
    }

    protected static function assertCustomLimitForMerchant(ME $merchant, int $total)
    {
        $listOfLimits = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::RX_PAYOUTS_CUSTOM_BATCH_FILE_LIMIT_MERCHANTS
        ]);

        // If the MID has a custom limit set from redis, we shall apply that limit
        if (in_array($merchant->getId(), array_keys($listOfLimits), true) === true)
        {
            $limit = $listOfLimits[$merchant->getId()];
        }
        else
        {
            // If the limit isn't set for a certain merchant, we default to the global Redis based limit
            $limit = (new Admin\Service)->getConfigKey([
                'key' => Admin\ConfigKey::RX_PAYOUTS_DEFAULT_MAX_BATCH_FILE_COUNT
            ]);

            // If the global redis limit isn't set either, then we fall back to the hardcoded limit
            if (empty($limit) === true)
            {
                $limit = Limit::DEFAULT_PAYOUT_LIMIT;
            }
        }

        if ($total > $limit)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT,
                null,
                [
                    'type'  => 'payout',
                    'total' => $total,
                ]);
        }
    }

    protected function getAmountTypeForPayouts(ME $merchant)
    {
        $expectedAmountType = $this->getSettingsAccessor($merchant)->get(Constants::TYPE);

        // If merchant is not a existing bulk merchant, then the merchant has amount type rupees.
        if ($expectedAmountType != BatchHelper::PAISE)
        {
            $expectedAmountType = BatchHelper::RUPEES;
        }

        return $expectedAmountType;
    }


    /**
     * Doing this only for LIVE MODE so that we don't have to run migrations for both Live and test mode.
     * This way the test mode validations etc will mimic a merchant's Live mode automatically.
     *
     * @param ME $merchant
     * @return Settings\Accessor
     */
    protected function getSettingsAccessor(ME $merchant): Settings\Accessor
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_AMOUNT_TYPE, Mode::LIVE);
    }

    protected function getActualAmountTypeBasedOnUploadedData(array $sampleRow)
    {
        if (array_key_exists(Header::PAYOUT_AMOUNT_RUPEES, $sampleRow) === true)
        {
            return BatchHelper::RUPEES;
        }
        else
        {
            return BatchHelper::PAISE;
        }
    }

    protected function validateOtpAndTokenForPayoutCreate(array $input)
    {
        $app = App::getFacadeRoot();

        if ($app['basicauth']->isStrictPrivateAuth() === true)
        {
            if (array_key_exists(Entity::OTP, $input))
            {
                $this->throwExtraFieldsException([Entity::OTP]);
            }
            if (array_key_exists(Entity::TOKEN, $input))
            {
                $this->throwExtraFieldsException([Entity::TOKEN]);
            }
        }
        else
        {
            $this->validateInputValues('payout_create_otp', $input);
        }
    }

    protected function processValidationFailureWithCompleteMessage($messages, $operation, $input)
    {
        if ($this->checkIfOperationIsAllowed($operation) === true)
        {
            throw new Exception\Batch\BadRequestValidationFailureException($messages, null, $operation);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException($messages);
        }
    }

    public function checkIfOperationIsAllowed($operation)
    {
        // Add all the operations for which the all the error descriptions are shown
        $operations = [
            'payoutRupeesTypeRow',
            'payoutTypeRow',
            'fundAccountTypeRow',
            'fundAccountV2TypeRow'
        ];

        return (in_array($operation, $operations) === true);
    }

    protected function validateIciciLeadAccountActivationCommentsEntries(array &$entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $applicationNumber = $entry[Header::APPLICATION_NO];

            if (empty($applicationNumber) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_APPLICATION_NO);
            }
        }
    }

    protected function validateStpMisEntries(array &$entries, array $params, ME $merchant)
    {
        foreach ($entries as $entry)
        {
            $accountNumber = $entry[Header::STP_ACCOUNT_NO];

            if (empty($accountNumber) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_ACCOUNT_NO);
            }
        }
    }

    protected function validateRblBulkUploadCommentsEntries(array &$entries, array $params, ME $merchant)
    {
        foreach($entries as $entry)
        {
            $mid = $entry[Header::MERCHANT_ID];

            if (empty($mid) === true)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PASSED);
            }
        }
    }

    public function validateLinkedAccountBatchActionAllowed($input, Merchant\Entity $merchant)
    {
        if(($input[Entity::TYPE] === Type::LINKED_ACCOUNT_CREATE) and
            (in_array($merchant->getCategory(), Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Merchant\Entity::CATEGORY]) === true) and
            (in_array($merchant->getCategory2(), Merchant\Constants::LINKED_ACCOUNT_ACTIONS_BLOCKED[Merchant\Entity::CATEGORY2]) === true) and
            (app('basicauth')->isAdminAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CREATION_NOT_ALLOWED
            );
        }
    }

    protected function validateContactGstin($attribute, $value)
    {
        $isValidGstin = Gstin::isValid($value);

        if ($isValidGstin === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The gstin field is invalid.',
                Header::CONTACT_GSTIN);
        }
    }

    protected function validateContactPan($attribute, $value)
    {
        $valid = preg_match(self::PAN_REGEX, $value, $matches);

        $this->getTrace()->info(TraceCode::CONTACT_PAN_REGEX_MATCH,
            [
                'isPanFormatValid' => $valid,
            ]
        );

        if ($valid != 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The pan field is invalid.',
                Header::CONTACT_PAN);
        }
    }

    public function validatePartnerSubmerchantReferralInviteEntries(array & $entries, array $params, ME $merchant)
    {
        $existingKeys = [];

        foreach ($entries as $entry)
        {
            $this->validateInput('partnerSubmerchantReferralInviteTypeRow', $entry);

            $email      = $entry[Header::EMAIL] ?? '';
            $contactNo  = $entry[Header::CONTACT_MOBILE] ?? '';

            $key = strtolower($email.$contactNo);

            // Batch File should not contain multiple entries for the same key
            if (in_array($key, $existingKeys))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_CONTACTS);
            }

            $existingKeys[] = $key;
        }
    }
}

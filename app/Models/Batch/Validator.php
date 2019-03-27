<?php

namespace RZP\Models\Batch;

use App;
use RZP\Base;
use RZP\Models\User;
use RZP\Models\Invoice;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Entity as ME;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact as ContactModel;
use RZP\Models\Feature\Constants as Feature;
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

    protected static $defaultCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
    ];

    protected static $paymentLinkCreateRules = [
        Entity::TYPE                    => 'required|in:payment_link',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:10240' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Invoice\Entity::DRAFT           => 'filled|in:0,1',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
        Entity::CONFIG                  => 'filled|array',
    ];

    protected static $directDebitCreateRules = [
        Entity::TYPE            => 'required|in:direct_debit',
        Entity::FILE            => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::NAME            => 'filled|string|max:255',
        Entity::TOKEN           => 'required_without:file_id|max:255|alpha_num',
        Entity::FILE_ID         => 'required_without:file|public_id',
    ];

    protected static $recurringChargeCreateRules = [
        Entity::TYPE            => 'required|in:recurring_charge',
        Entity::FILE            => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::NAME            => 'filled|string|max:255',
        Entity::FILE_ID         => 'required_without:file|public_id',
    ];

    protected static $tokenRules = [
        Entity::TOKEN           => 'required|max:255|alpha_num',
    ];

    protected static $reconciliationCreateRules = [
        Entity::TYPE            => 'required|in:reconciliation',
        Entity::GATEWAY         => 'required|string|max:25',
        Entity::FILE            => 'required|file',
        Entity::CONFIG          => 'filled|array',
    ];

    protected static $emandateCreateRules = [
        Entity::FILE        => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::TYPE        => 'required|in:emandate',
        Entity::SUB_TYPE    => 'required|string|in:register,debit,acknowledge',
        Entity::GATEWAY     => 'required|string',
    ];

    protected static $merchantOnboardingCreateRules = [
        Entity::FILE    => 'required|file' . self::DEFAULT_MIME_RULE,
        Entity::TYPE    => 'required|in:merchant_onboarding',
        Entity::GATEWAY => 'required|string',
    ];

    protected static $terminalCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::SUB_TYPE             => 'required|string|in:hitachi,netbanking_icici',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
    ];

    protected static $virtualBankAccountCreateRules = [
        Entity::TYPE                 => 'required|in:virtual_bank_account',
        Entity::FILE                 => 'required|file' . self::DEFAULT_MIME_RULE,
    ];

    protected static $elfinCreateRules = [
        Entity::TYPE   => 'required|custom',
        Entity::NAME   => 'filled|string|max:255',
        Entity::FILE   => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG => 'filled|array',
    ];

    protected static $entityMappingCreateRules = [
        Entity::TYPE                         => 'required|in:entity_mapping',
        Entity::NAME                         => 'filled|string|max:255',
        Entity::FILE                         => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::CONFIG                       => 'required|array',
        Entity::CONFIG . '.entity_from_type' => 'required|string',
        Entity::CONFIG . '.entity_to_type'   => 'required|string',
    ];

    protected static $authLinkCreateRules = [
        Entity::TYPE                    => 'required|in:auth_link',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Entity::CONFIG                  => 'filled|array',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
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
        Entity::TYPE           => 'required|in:sub_merchant',
        Entity::NAME           => 'filled|string|max:255',
        Entity::FILE           => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        ME::AUTO_SUBMIT        => 'filled|boolean',
        ME::AUTOFILL_DETAILS   => 'filled|boolean',
        ME::AUTO_ACTIVATE      => 'filled|boolean',
        ME::USE_EMAIL_AS_DUMMY => 'filled|boolean',
    ];

    protected static $oauthMigrationTokenCreateRules = [
        Entity::TYPE           => 'required|custom',
        Entity::NAME           => 'filled|string|max:255',
        Entity::FILE           => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        OMHelper::CLIENT_ID    => 'required|string|size:14',
        OMHelper::USER_ID      => 'required|string|size:14',
        OMHelper::REDIRECT_URI => 'required|url',
    ];

    protected static $fundAccountCreateRules = [
        Entity::TYPE    => 'required|in:fund_account',
        Entity::NAME    => 'filled|string|max:255',
        Entity::FILE    => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID => 'required_without:file|public_id',
    ];

    protected static $payoutCreateRules = [
        Entity::TYPE    => 'required|in:payout',
        Entity::NAME    => 'filled|string|max:255',
        Entity::FILE    => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID => 'required_without:file|public_id',
        Entity::OTP     => 'required|filled|min:4',
        Entity::TOKEN   => 'required|unsigned_id',
    ];

    protected static $payoutValidateRules = [
        Entity::TYPE    => 'required|in:payout',
        Entity::NAME    => 'filled|string|max:255',
        Entity::FILE    => 'required_without:file_id|file|max:10240' . self::CSV_MIME_RULE,
        Entity::FILE_ID => 'required_without:file|public_id',
    ];

    protected static $fundAccountTypeRowRules = [
        Header::FUND_ACCOUNT_TYPE         => 'required|string|in:bank_account,vpa',
        Header::FUND_ACCOUNT_NAME         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER       => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA          => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::CONTACT_ID                => 'sometimes|nullable|public_id|size:19',
        Header::CONTACT_TYPE              => 'required_without:'.Header::CONTACT_ID.'|nullable|string',
        Header::CONTACT_NAME_2            => 'required_without:'.Header::CONTACT_ID.'|nullable|string',
        Header::CONTACT_EMAIL_2           => 'sometimes|nullable|string',
        Header::CONTACT_MOBILE_2          => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID      => 'sometimes|nullable|string',
        Header::NOTES                     => 'sometimes|nullable|notes',
    ];

    // This is not a copy paste of above ^ rules!
    protected static $payoutTypeRowRules = [
        Header::RAZORPAYX_ACCOUNT_NUMBER    => 'required|string',
        Header::PAYOUT_PURPOSE              => 'required|string|max:30|alpha_dash_space',
        Header::PAYOUT_NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Header::PAYOUT_AMOUNT               => 'required|integer|min:100|max:500000000',
        Header::PAYOUT_CURRENCY             => 'required|size:3|in:INR',
        Header::PAYOUT_MODE                 => 'sometimes|nullable|string',
        Header::PAYOUT_REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Header::FUND_ACCOUNT_ID             => 'sometimes|nullable|public_id|size:17',
        Header::FUND_ACCOUNT_TYPE           => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string|in:bank_account,vpa',
        Header::FUND_ACCOUNT_NAME           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_IFSC           => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_NUMBER         => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',bank_account|nullable|string',
        Header::FUND_ACCOUNT_VPA            => 'required_if:'.Header::FUND_ACCOUNT_TYPE.',vpa|nullable|string',
        Header::CONTACT_TYPE                => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string',
        Header::CONTACT_NAME_2              => 'required_without:'.Header::FUND_ACCOUNT_ID.'|nullable|string',
        Header::CONTACT_EMAIL_2             => 'sometimes|nullable|string',
        Header::CONTACT_MOBILE_2            => 'sometimes|nullable|string',
        Header::CONTACT_REFERENCE_ID        => 'sometimes|nullable|string',
        Header::NOTES                       => 'sometimes|nullable|notes',
    ];

    protected function validateType($attribute, $value)
    {
        Type::validateType($value);
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
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_UNDER_PROCESSING,
                Entity::STATUS,
                $this->entity->toArray());
        }
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
    protected function getRuleNames(): array
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

    protected function validatePayoutEntries(array & $entries, array $params, ME $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::PAYOUT) === false)
        {
            throw new BadRequestValidationFailureException('Batch type is not enabled for merchant');
        }

        if (in_array(app()->basicauth->getUserRole(), [User\Role::ADMIN, User\Role::OWNER], true) === false)
        {
            throw new BadRequestValidationFailureException('Only admins and owners are allowed to take this action');
        }

        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('payoutTypeRow', $entry);
        });

        // After validating contents per row only should do following aggregate validations.

        $totalPayoutAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT));
        $bankingBalance = $merchant->bankingBalance->getBalance();

        if ($totalPayoutAmount > $bankingBalance)
        {
            throw new BadRequestValidationFailureException(
                'Total payout amount in uploaded file exceeds available account balance',
                Entity::FILE,
                compact('totalPayoutAmount', 'bankingBalance'));
        }
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
        if ($merchant->isNonPurePlatformPartner() === false)
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
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
        $this->validateEntriesWithPublicExceptionHandled($entries, function (array $entry)
        {
            $this->validateInput('fundAccountTypeRow', $entry);
        });
    }

    protected function validateEntriesWithPublicExceptionHandled(array & $entries, \Closure $validator)
    {
        // Indexed errors map against row number.
        $errors = [];

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

        $params   = [
            Entity::OTP         => $input[Entity::OTP],
            Entity::TOKEN       => $input[Entity::TOKEN],
            User\Entity::ACTION => "create_{$input[Entity::TYPE]}_batch",
        ];

        (new User\Core)->verifyOtp($params, $auth->getMerchant(), $auth->getUser());
    }
}

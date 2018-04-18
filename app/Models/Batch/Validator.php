<?php

namespace RZP\Models\Batch;

use RZP\Base;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use RZP\Exception\BadRequestException;
use RZP\Models\Feature\Constants as Feature;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as HdfcEMDebitHeadings;
use RZP\Models\Batch\Processor\HdfcEmandateRegister;
use RZP\Exception\BadRequestValidationFailureException;
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
    /**
     * Default rule for file validation. Per type a different file rule can be
     * written. We validate both mime_types and mimes(basically extension).
     */
    const DEFAULT_MIME_RULE = '|mime_types:'
                                    . 'application/zip,'
                                    . 'application/vnd.ms-excel,'
                                    . 'application/vnd.oasis.opendocument.spreadsheet,'
                                    . 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                                    . 'application/octet-stream,'
                                    . 'application/xml,'
                                    . 'text/csv,'
                                    . 'text/plain'
                                . '|mimes:'
                                    . 'zip,'
                                    . 'xlsx,'
                                    . 'xls,'
                                    . 'xml,'
                                    . 'csv,'
                                    . 'txt';

    //
    // TODO:
    // - csv files doesn't expect headers, it throws error in that case.
    // - Should not keep csv, txt in default rules.
    //

    protected static $defaultCreateRules = [
        Entity::TYPE                 => 'required|custom',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
    ];

    protected static $paymentLinkCreateRules = [
        Entity::TYPE                    => 'required|in:payment_link',
        Entity::NAME                    => 'filled|string|max:255',
        Entity::FILE                    => 'required_without:file_id|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::FILE_ID                 => 'required_without:file|public_id',
        Invoice\Entity::DRAFT           => 'filled|in:0,1',
        Invoice\Entity::SMS_NOTIFY      => 'filled|in:0,1',
        Invoice\Entity::EMAIL_NOTIFY    => 'filled|in:0,1',
        Entity::CONFIG                  => 'filled|array',
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
        Entity::TYPE                 => 'required|in:sub_merchant',
        Entity::NAME                 => 'filled|string|max:255',
        Entity::FILE                 => 'required|file|max:1024' . self::DEFAULT_MIME_RULE,
        Entity::APPLICATION_ID       => 'filled|string|size:14',
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
     * Validates entries(array) of batch input file before
     * creating the batch entity.
     *
     * @param array           $entries
     * @param array           $params
     * @param Merchant\Entity $merchant
     *
     */
    public function validateEntries(
        array & $entries,
        array $params,
        Merchant\Entity $merchant)
    {
        $rules = $this->getRuleNames();

        // Limit validations
        Limit::validate($rules['limit_rule'], count($entries));

        // Header validations
        Header::validate($rules['header_rule'], array_keys(current($entries)));

        // Data validations
        $validatorMethodName = $rules['validator_method'];

        if (method_exists($this, $validatorMethodName) === true)
        {
            $this->$validatorMethodName($entries, $params, $merchant);
        }
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

    protected function validateRefundEntries(
        array & $entries,
        array $params,
        Merchant\Entity $merchant)
    {
        $existingPaymentIds = [];

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
     * @param array             $entries
     * @param array             $params
     * @param Merchant\Entity   $merchant
     *
     * @throws BadRequestException
     */
    protected function validatePaymentLinkEntries(
        array & $entries,
        array $params,
        Merchant\Entity $merchant)
    {
        // Associative array with index as input file's row index and values
        // as the error message.

        $errorCount = 0;

        foreach ($entries as $idx => $entry)
        {
            $input = Helpers\PaymentLink::getEntityInput($entry, $params);

            // Need to create dummy entity and associate merchant
            // for the validation around max allowed payment to happen.

            $rule = Invoice\Validator::CREATE_DRAFT;

            if ($input[Invoice\Entity::DRAFT] === '0')
            {
                $rule = Invoice\Validator::CREATE_ISSUED;
            }

            $invoice = new Invoice\Entity;

            $invoice->merchant()->associate($merchant);

            try
            {
                $invoice->getValidator()->validateInput($rule, $input);

                $error = [
                    Header::ERROR_CODE          => null,
                    Header::ERROR_DESCRIPTION   => null,
                ];

                $entries[$idx] = ($entry + $error);
            }
            catch (BaseException $e)
            {
                $errorCount++;

                $error = [
                    Header::ERROR_CODE          => $e->getError()->getPublicErrorCode(),
                    Header::ERROR_DESCRIPTION   => $e->getError()->getDescription(),
                ];

                $entries[$idx] = ($entry + $error);
            }
            finally
            {
                unset($invoice);
            }

            if (($errorCount > 0) and ($this->entity->isCreatedByFileUpload() === true))
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_PAYMENT_LINK_FILE_ERRORS,
                    Entity::FILE,
                    [
                        'count'         => $errorCount,
                        'merchant_id'   => $merchant->getId(),
                    ]);
            }
        }
    }

    protected function validateVirtualBankAccountEntries(array & $entries, array $params, Merchant\Entity $merchant)
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

    protected function validateRecurringChargeEntries(array & $entries, array $params, Merchant\Entity $merchant)
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

    protected function validatePayoutEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::PAYOUT) === false)
        {
            throw new BadRequestValidationFailureException(
                'Batch type is not enabled for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }

    protected function validateLinkedAccountEntries(array & $entries, array $params, Merchant\Entity $merchant)
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
        array & $entries, array $params, Merchant\Entity $merchant)
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
        array & $entries, array $params, Merchant\Entity $merchant)
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

    protected function validateSubMerchantEntries(array & $entries, array $params, Merchant\Entity $merchant)
    {
        if ($merchant->isFeatureEnabled(Feature::AGGREGATOR) === false)
        {
            throw new BadRequestValidationFailureException(
                'Sub-merchant creation not allowed for merchant',
                null,
                [
                    Entity::MERCHANT_ID => $merchant->getId(),
                ]);
        }
    }
}


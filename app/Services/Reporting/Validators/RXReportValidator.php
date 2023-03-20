<?php

namespace RZP\Services\Reporting\Validators;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Services\Reporting;
use RZP\Services\Reporting\Constants;
use RZP\Models\CreditTransfer\Entity as CTEntity;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\SubVirtualAccount\Entity as SubVAEntity;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class RXReportValidator
 * A validator class for all RazorpayX Report Requests
 *
 * @package RZP\Services\Reporting
 */
class RXReportValidator extends BaseValidator
{
    protected function validateEmails(array $emails)
    {
        $configsToSkipValidation = $this->app['config']->get('reporting.config_ids_to_skip_email_validation');

        $configPassed = array_get($this->input,'config_id','');

        $this->app->trace->info(
            TraceCode::REPORTING_CONFIGS_TO_SKIP_VALIDATION,
            [
                "configs_to_skip_validation" => $configsToSkipValidation,
                "config_passed"              => $configPassed
            ]);

        if (empty($configPassed) === false &&
            in_array($configPassed, $configsToSkipValidation) === true)
        {
            return;
        }

        $merchant = $this->merchantService->getMerchantDetails();

        $users = $this->merchantService->getUsers();

        $regEmailAddresses = [];

        if ((isset($merchant[MerchantEntity::TRANSACTION_REPORT_EMAIL])) and
            is_array($merchant[MerchantEntity::TRANSACTION_REPORT_EMAIL]))
        {
            $regEmailAddresses = $merchant[MerchantEntity::TRANSACTION_REPORT_EMAIL];
        }

        array_push($regEmailAddresses, $merchant[MerchantEntity::EMAIL]);

        foreach ($users as $user)
        {
            $regEmailAddresses[] = $user[MerchantEntity::EMAIL];
        }

        $this->app->trace->info(
            TraceCode::REPORTING_EMAIL_VALIDATION,
            [
                "validator"         => "RXReportValidator",
                "config_id"         => $this->input["config_id"] ?? "",
                "input_emails"      => $emails,
                "registered_emails" => $regEmailAddresses
            ]);

        $this->failIfAnyEmailIsInvalid($regEmailAddresses, $emails);
    }

    /**
     * These validations are kept for data security. In Account <> Sub-Account setup, master merchant can request
     * different reports of their linked sub merchants. In this regard, we should validate the following to ensure we
     * do not allow any fraudulent/unauthorised request to Reporting service. For this we check the following
     *      1. The sub_merchant_ids list in the payload is a subset of the sub merchant ids linked to the master merchant.
     *         This is to ensure that a master merchant cannot fetch fetch data for other merchants who are not linked to them
     *      2. In case of filters present (currently only two kinds of filters), check if they contain valid data
     *          2.1. If payer_merhcant_id is present in the filters, the value should be same as the merchant_id derived
     *               from auth context. This will ensure that tampered payload fails validation
     *          2.2. If account_number is present in filters, the values should be a subset of the account numbers
     *               of the sub merchants' shared account number which is linked to the master merchant.
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     */
    protected function validateMasterMerchantIdSubMerchantIdsAndFilters()
    {
        $subMerchantIdsInInput = $this->input[self::SUB_MERCHANT_IDS];

        if (is_array($subMerchantIdsInInput) === false)
        {
            throw new BadRequestValidationFailureException(
                self::SUB_MERCHANT_IDS . " is not an array"
            );
        }

        $repo = app('repo');

        $masterMerchantId = $this->input[Constants::GENERATED_BY];

        $subVirtualAccounts = $repo->sub_virtual_account->getSubVirtualAccountsFromMasterMerchantId($masterMerchantId);

        $subMerchantIds = $subVirtualAccounts->pluck(SubVAEntity::SUB_MERCHANT_ID)->toArray();

        if (empty($subMerchantIds) === true)
        {
            throw new BadRequestValidationFailureException(
                "Invalid master merchant id: " . $masterMerchantId
            );
        }

        if (count(array_diff($subMerchantIdsInInput, $subMerchantIds)) !== 0)
        {
            throw new BadRequestValidationFailureException(
              "Invalid " . self::SUB_MERCHANT_IDS . " list in input",
              null,
              [
                  self::SUB_MERCHANT_IDS => $subMerchantIdsInInput
              ]
            );
        }

        $this->validateFiltersIfPresent($subVirtualAccounts);
    }

    protected function validateFiltersIfPresent($subVirtualAccounts)
    {
        $filters = $this->input[Reporting::TEMPLATE_OVERRIDES][Reporting::FILTERS] ?? null;

        if (empty($filters) === true)
        {
            return;
        }

        /* The filters array will look something like the following

            "filters" => [
                "credit_transfers" => [
                    "payer_merchant_id" => [
                        "op"     => "IN",
                        "values" => ["JIkgSlFUhGL4jY"]
                    ]
                ],
                "balance"          => [
                    "account_number" => [
                        "op"     => "IN",
                        "values" => ["34341234567890", "7878781234567890"]
                    ]
                ]
            ];

         */
        foreach ($filters as $key => $value)
        {
            if ($key === Table::CREDIT_TRANSFER)
            {
                if (empty($value[CTEntity::PAYER_MERCHANT_ID]) === false)
                {
                    $payerMerchantId = $value[CTEntity::PAYER_MERCHANT_ID]['values'][0];

                    if ($payerMerchantId !== $this->input[Constants::GENERATED_BY])
                    {
                        throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
                    }
                }
            }
            if ($key === Table::BALANCE)
            {
                $accountNumbersInInput = $value[Constants::ACCOUNT_NUMBER]['values'] ?? [];

                if (empty($accountNumbersInInput) === false)
                {
                    $subVirtualAccountNumbers = $subVirtualAccounts->pluck(SubVAEntity::SUB_ACCOUNT_NUMBER)->toArray();

                    if (count(array_diff($accountNumbersInInput, $subVirtualAccountNumbers)) > 0)
                    {
                        throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
                    }
                }
            }
        }
    }
}

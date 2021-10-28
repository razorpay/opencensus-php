<?php

namespace RZP\Services\Reporting\Validators;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
}

<?php


namespace RZP\Services\Reporting\Validators;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class PGReportValidator
 * A validator class for all Payment Gateway Report Requests
 *
 * @package RZP\Services\Reporting
 */
class PGReportValidator extends BaseValidator
{
    protected function validateEmails(array $emails)
    {
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
                "validator"         => "PGReportValidator",
                "config_id"         => $this->input["config_id"] ?? "",
                "input_emails"      => $emails,
                "registered_emails" => $regEmailAddresses
            ]);

        $this->failIfAnyEmailIsInvalid($regEmailAddresses, $emails);
    }
}

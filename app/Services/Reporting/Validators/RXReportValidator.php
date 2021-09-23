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
    //Allowed Report-ids (fetched for prod and hard coded) to skip email validation
    const ALLOWED_REPORT_CONFIG_IDS_TO_SKIP_VALIDATION = ["config_EeJ5H48IDnU0DE", "config_EWkl7gyPYK5ET2", "config_H14EVTdd8PfHKv"];

    protected function validateEmails(array $emails)
    {
        if (in_array(array_get($this->input,'config_id',''), self::ALLOWED_REPORT_CONFIG_IDS_TO_SKIP_VALIDATION) === true) {
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

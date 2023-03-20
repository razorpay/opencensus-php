<?php


namespace RZP\Services\Reporting\Validators;

use App;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class BaseValidator
 * A base class for validating Report Requests before routing it to Reporting Service
 * Extend this class to validate requests based on different criteria. Ex: ReportType
 *
 * @package RZP\Services\Reporting
 */
abstract class BaseValidator
{
    use ValidationHelper;

    const EMAILS           = "emails";
    const SUB_MERCHANT_IDS = "sub_merchant_ids";

    protected $input;
    protected $merchantService;
    protected $app;

    /**
     * Validator constructor.
     * @param array $input request payload to be validated
     */
    public function __construct(array $input)
    {
        $this->app = App::getFacadeRoot();

        $this->input = $input;

        $serviceClass = E::getEntityService(E::MERCHANT);

        $this->merchantService = new $serviceClass;
    }

    /**
     * Validates request by calling different handlers implemented by extended classes
     *
     * @throws BadRequestValidationFailureException if input is invalid
     */
    public function validate()
    {
        try
        {
            if ((isset($this->input[self::EMAILS]) === true) and
                (empty($this->input[self::EMAILS]) === false) and
                (is_array($this->input[self::EMAILS]) === true))
            {
                $this->validateEmails($this->input[self::EMAILS]);
            }

            if (empty($this->input[self::SUB_MERCHANT_IDS]) === false)
            {
                $this->validateMasterMerchantIdSubMerchantIdsAndFilters();
            }
        }
        catch(BadRequestValidationFailureException $e)
        {
            $this->app->trace->error(
                TraceCode::REPORTING_REQUEST_VALIDATION_FAILED,
                [
                    "input" => $this->input
                ]);

            throw $e;
        }
    }

    /**
     * Validates if all the given email ids are registered for the merchant.
     *
     * @param array $emails list of email ids to be validated
     * @throws BadRequestValidationFailureException if any given email id is
     * not registered for the merchant
     */
    abstract protected function validateEmails(array $emails);
}

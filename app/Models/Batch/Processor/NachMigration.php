<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Method as PaymentMethod;

class NachMigration extends Base
{

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

    }

    public function storeAndValidateInputFile(array $input): array
    {
        // step 1:
        // validate the Merchant ID , feature flag for merchant is e-mandate or nach enabled, Terminal ID
        $this->validateConfigInput($input);

        $merchantEntity = null;
        $terminalEntity = null;

        $emandateTerminalEnabled = false;
        $nachTerminalEnabled           = false;

        $this->validateMerchantTerminal($input, $merchantEntity, $terminalEntityEmandate,
                                        $terminalEntityPnach, $emandateTerminalEnabled,
                                        $nachTerminalEnabled);

        $this->validateFeature($merchantEntity);

        // step 2: pricing plan
        $emandatePaymentMethodEnabled = false;
        $nachPaymentEnabled           = false;

        $this->validatePricingPlan($merchantEntity, $emandatePaymentMethodEnabled, $nachPaymentEnabled);

        try
        {
            $precision = ini_get('precision');

            ini_set('precision', 15);

            // step 3: validate entries in file
            $response = parent::storeAndValidateInputFile($input);

            ini_set('precision', $precision);
        }
        catch (BadRequestException $be)
        {
            if ($be->getCode() === ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS)
            {
                $data  = $be->getData();
                $expectedHeaders = $data['expected_headers'];
                $inputHeaders = $data['input_headers'];
                $difference = join(", " ,array_diff($expectedHeaders, $inputHeaders));
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                    "headers",
                    null,
                    "Headers Not matching: " . $difference);
            }
            throw $be;
        }

        $response['emandate_terminal_enabled'] = $emandateTerminalEnabled;

        $response['nach_terminal_enabled'] = $nachTerminalEnabled;

        $response['emandate_payment_enabled'] = $emandatePaymentMethodEnabled;

        $response['nach_payment_enabled'] = $nachPaymentEnabled;

        return $response;
    }

    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $response = parent::getValidatedEntriesStatsAndPreview($entries);
        unset($response[Constants::PARSED_ENTRIES]);

        return $response;
    }

    protected function validateConfigInput($input): void
    {
        if (isset($input["config"]) === false or empty($input["config"]) === true or
            isset($input["config"]["merchant_id"]) === false or
            (isset($input["config"]["emand_term"]) === false and isset($input["config"]["pnach_term"]) === false ))
        {

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                "config",
                null, "terminal_id and merchant_id are mandatory");
        }
    }

    protected function validateMerchantTerminal($input, &$merchantEntity,
                                                &$terminalEntityEmandate, &$terminalEntityPnach,
                                                &$eMandateEnabled, &$nachEnabled): void
    {
        $searchingId = "";

        try
        {
            //$merchantEntity = $this->repo->merchant->get($input["config"]["merchant_id"]);
            $merchantCore = new Merchant\Core();

            $searchingId = $input["config"]["merchant_id"];

            $merchantEntity = $merchantCore->get($searchingId);

            if (empty($input["config"]["emand_term"]) === false)
            {
                $searchingId = $input["config"]["emand_term"];

                $terminalEntityEmandate = $this->repo->terminal->getByIdAndMerchantId($merchantEntity->getId(),
                    $searchingId);
            }

            if (empty($input["config"]["pnach_term"]) === false)
            {
                $searchingId = $input["config"]["pnach_term"];

                $terminalEntityPnach = $this->repo->terminal->getByIdAndMerchantId($merchantEntity->getId(),
                    $searchingId);
            }
        }
        catch (BadRequestException $e)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT,
                $merchantEntity == null ? "merchant_id" : "terminal_id",
                      $searchingId,
                "Merchant with Terminal not found");
        }

        // Validate Payment Methods
        $eMandateEnabled = $terminalEntityEmandate === null ? false : $terminalEntityEmandate->isEmandateEnabled();
        $nachEnabled     = $terminalEntityPnach === null ? false : $terminalEntityPnach->isNachEnabled();

        // Error if Both the terminals are disabled
        if (($eMandateEnabled === false) and ($nachEnabled === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TERMINAL_ID,
                "terminal_id",
                "Terminals: ". implode(", ", [ $input["config"]["emand_term"], $input["config"]["pnach_term"]]),
                "Merchant's Terminal is Neither NACH/eMandate enabled");
        }

        // Error only if input field was present and terminal is disabled
        if ($eMandateEnabled === false and empty($input["config"]["emand_term"]) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TERMINAL_ID,
                "terminal_id",
                "eMandate Terminal " . $input["config"]["emand_term"],
                "Merchant's Terminal is Not eMandate enabled");
        }

        // Error only if input field was present and terminal is disabled
        if ($nachEnabled === false and empty($input["config"]["pnach_term"]) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TERMINAL_ID,
                "terminal_id",
                "pNach Terminal " . $input["config"]["pnach_term"],
                "Merchant's Terminal is Not NACH enabled");
        }
    }

    protected function validateFeature($merchantEntity): void
    {

        if ($merchantEntity->isFeatureEnabled(Feature\Constants::CHARGE_AT_WILL) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE,
                "merchant_id",
                null, "feature not enabled for merchant");
        }
    }

    protected function validatePricingPlan($merchantEntity, &$emandatePaymentMethodEnabled, &$nachPaymentEnabled): void
    {

        $methods = $this->repo->methods->getMethodsForMerchant($merchantEntity);
        $plan    = $this->repo->pricing->getMerchantPricingPlan($merchantEntity);

        if ($methods !== null and $plan !== null)
        {

            $emandatePaymentMethodEnabled =
                $plan->hasMethod(Payment\Method::EMANDATE) and $methods->isMethodEnabled(PaymentMethod::EMANDATE);

            $nachPaymentEnabled =
                $plan->hasMethod(Payment\Method::NACH) and $methods->isMethodEnabled(PaymentMethod::NACH);
        }

        // error out if no pricing plan for the merchant for nach and emandate
        // Ideally it should not be the case
        if ($emandatePaymentMethodEnabled === false and $nachPaymentEnabled === false)
        {

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_OPERATION_NOT_ALLOWED_IN_LIVE,
                "",
                null, "Merchant does not have a pricing plan for neither NACH nor EMANDATE");
        }

    }
}

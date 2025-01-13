<?php


namespace RZP\Models\Batch\Processor;

use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Batch;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;

class UpiOnboardedTerminalEdit extends Base
{
    /**
     * @param array $entry
     * This function is used to extract input and pass it to the terminal service.
     * It has a lot of validations in place, which is explained as follows:
     * 1. The column should not be null or an empty string.
     * 2. If you are editing cc_on_upi, wallet_on_upi or credit_line_on_upi, then you can't edit anything else.
     * 3. If you are not editing one of the fields mentioned above, then only one field can be edited at a time.
     * Examples-
     *            a. You can edit cc_on_upi and wallet_on_upi together in one go.
     *            b. You cannot edit cc_on_upi, wallet_on_upi and mcc together in one go.
     *            c. You cannot edit merchant size and mcc together in one go.
     *            d. You cannot edit mcc and billing label in one go.
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function processEntry(array & $entry){
        $terminalId         = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID];
        $gateway            = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY];
        $online             = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE] ?? null;
        $vpa                = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA] ?? null;
        $gatewayTerminalId  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID] ?? null;
        $gatewayAccessCode  = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE] ?? null;
        $vpaHandle          = $entry[Batch\Header::UPI_TERMINAL_ONBOARDING_VPA_HANDLE] ?? null;
        $allowCC            = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC] ?? null;
        $allowWallet        = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET] ?? null;
        $allowCreditLine    = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE] ?? null;
        $merchantSize       = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE] ?? null;
        $mcc                = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MCC] ?? null;
        $editBillingLabel   = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL] ?? null;
        $editMobileNumber   = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER] ?? null;
        $recurring          = $entry[Batch\Header::UPI_ONBOARDED_TERMINAL_EDIT_RECURRING] ?? null;

        // To store if no input has been passed at all
        $emptyInputFlag = true;

        // To store if instruments on upi input has been passed
        $instrumentsOnUpiFlag = false;

        // Stores the count of fields OTHER than instruments on upi fields
        $individualFieldEditFieldsCount = 0;

        $identifiers = [
            Terminal\Entity::VPA                  => $vpa,
            Terminal\Entity::GATEWAY_TERMINAL_ID  => $gatewayTerminalId,
            Terminal\Entity::GATEWAY_ACCESS_CODE  => $gatewayAccessCode,
            'vpa_handle'                          => $vpaHandle,
        ];

        $features = [];
        $otherInputs = [];

        if ($allowCC !== null and
            $allowCC !== '')
        {
            $emptyInputFlag = false;
            $instrumentsOnUpiFlag = true;
            $features[Terminal\Entity::CC_ON_UPI] = (boolval($allowCC) === true) ? '1' : '0';
        }

        if ($allowWallet !== null and
            $allowWallet !== '')
        {
            $emptyInputFlag = false;
            $instrumentsOnUpiFlag = true;
            $features[Terminal\Entity::WALLET_ON_UPI] = (boolval($allowWallet) === true) ? '1' : '0';
        }

        if ($allowCreditLine !== null and
            $allowCreditLine !== '')
        {
            $emptyInputFlag = false;
            $instrumentsOnUpiFlag = true;
            $features[Terminal\Entity::CREDIT_LINE_ON_UPI] = (boolval($allowCreditLine) === true) ? '1' : '0';
        }

        if ($online !== null and
            $online !== '')
        {
            if ($instrumentsOnUpiFlag === true and
                $gateway !== Gateway::UPI_YESBANK)
            {
                throw new BadRequestValidationFailureException(
                    'Updating Online type/Merchant Genre with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.'
                );
            }

            $individualFieldEditFieldsCount++;

            $emptyInputFlag = false;
            $features['online'] = (boolval($online) === true) ? '1' : '0';
        }

        $features['recurring'] = '0';

        if (!empty($recurring))
        {
            $features['recurring'] = (boolval($recurring) === true) ? '1' : '0';
        }

        if (empty($mcc) === false)
        {
            if ($instrumentsOnUpiFlag === true)
            {
                throw new BadRequestValidationFailureException(
                    'Updating MCC with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.'
                );
            }

            $individualFieldEditFieldsCount++;

            $emptyInputFlag = false;
            $otherInputs[Terminal\Entity::CATEGORY] = (string)$mcc;
        }

        // We don't have boolval check for merchant size as we plan to map more merchant sizes to numbers in the future.
        // This mapping shall be gateway dependant
        // For example, if merchant size is 0, then we expect to pass 'SMALL' to a particular gateway,
        // if merchant size is 1, then we expect to pass 'LARGE'.
        // If we want to pass a size called 'MEDIUM' in the future, then we can assign one more number and maintain an
        // enum.
        // This mapping between number and actual size string should be handled at the gateway module in Mozart only.
        // This decision was taken to make it simpler for Banking Ops to pass a size without worrying about the casing
        // of the actual size (They should not have to worry about passing "Large"/"LARGE"/"large"/"lARGE" etc.)
        if ($merchantSize !== null and
            $merchantSize !== '')
        {
            // Since merchant size is compulsory for UPI Yesbank, we have added a gateway check
            if ($instrumentsOnUpiFlag === true and
                $gateway !== Gateway::UPI_YESBANK)
            {
                throw new BadRequestValidationFailureException(
                    'Updating Merchant Size with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.'
                );
            }

            // We are not incrementing this counter for upi yesbank
            // This is because merchant size is mandatory for UPI Yesbank Edits
            if ($gateway !== Gateway::UPI_YESBANK)
            {
                $individualFieldEditFieldsCount++;
            }

            $emptyInputFlag = false;
            $features['merchant_size'] = (string)$merchantSize;
        }

        if ($editBillingLabel !== null and
            $editBillingLabel !== '')
        {
            // Added special clause for yesbank as it mandatorily wants cc flag
            if ($instrumentsOnUpiFlag === true and
                $gateway !== Gateway::UPI_YESBANK)
            {
                throw new BadRequestValidationFailureException(
                    'Updating other fields with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.'
                );
            }

            $individualFieldEditFieldsCount++;

            $emptyInputFlag = false;
            $features['edit_billing_label'] = (boolval($editBillingLabel) === true) ? '1' : '0';
        }

        if ($editMobileNumber !== null and
            $editMobileNumber !== '')
        {
            // Added special clause for yesbank as it mandatorily wants cc flag
            if ($instrumentsOnUpiFlag === true and
                $gateway !== Gateway::UPI_YESBANK)
            {
                throw new BadRequestValidationFailureException(
                    'Updating other fields with cc_on_upi, wallet_on_upi or credit_line_on_upi is not allowed.'
                );
            }

            $individualFieldEditFieldsCount++;

            $emptyInputFlag = false;
            $features['edit_mobile_number'] = (boolval($editMobileNumber) === true) ? '1' : '0';
        }

        if ($emptyInputFlag === true)
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_EMPTY_ROW_UPLOADED
            );
        }

        if ($individualFieldEditFieldsCount > 1)
        {
            throw new BadRequestValidationFailureException(
                'Updating too many categories for a single terminal is not allowed in one go.'
            );
        }

        $response = $this->app['terminals_service']
            ->EditOnboardedTerminal(
                $terminalId,
                $gateway,
                $identifiers,
                $features,
                [], // no currency data
                $otherInputs
            );

        if (isset($response['terminal'][Terminal\Entity::ID]) === true)
        {
            $entry[Batch\Header::STATUS]            = Batch\Status::SUCCESS;
        }
    }
}

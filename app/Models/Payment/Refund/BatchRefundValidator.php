<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Models\Batch\Limit;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Entity as ME;
use RZP\Exception\BadRequestException;
use RZP\Models\Batch\Validator as BatchValidatorBase;

class BatchValidator extends BatchValidatorBase
{
    /**
     * Validates entries(array) of batch input file before
     * creating the batch entity.
     *
     * @param array $entries
     * @param array $params
     * @param ME $merchant
     */

    public function validateBatchRefundEntries(array & $entries, array $params, ME $merchant, $razorX)
    {
        $rules = $this->getRuleNames();

        // Limit validations
        Limit::validate($rules['limit_rule'], count($entries));

        // Header validations
        $this->validateHeaders($rules['header_rule'], array_keys(current($entries)), $razorX);

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

    /**
    * @throws BadRequestException
    */
    public function validateHeaders(string $type, array $actualHeaders, $razorX)
    {
        $expectedHeaders = Header::getInputHeadersForType($type);

        if ($razorX === false)
        {
            $index = array_search('Speed', $expectedHeaders);

            if ($index !== false)
            {
                unset($expectedHeaders[$index]);
            }
        }

        //
        // Notes is optional header in file. Currently optional headers are not supported and so this quick workaround
        // to get validation passing. Soon we will have support for optional headers.
        //
        if ((in_array(Header::NOTES, $expectedHeaders, true) === true) and
            (in_array(Header::NOTES, $actualHeaders, true) === false))
        {
            $actualHeaders[] = Header::NOTES;
        }

        //
        // Speed is also optional. See ^above comments about Notes;
        //
        if ((in_array(Header::SPEED, $expectedHeaders, true) === true) and
            (in_array(Header::SPEED, $actualHeaders, true) === false))
        {
            $actualHeaders[] = Header::SPEED;
        }

        //
        // For PL batch, we want to optionally accept the FIRST_PAYMENT_MIN_AMOUNT
        // headers. This is temporary until we have support for optional headers.
        //
        if (($type === Batch\Type::PAYMENT_LINK or $type === Batch\Type::PAYMENT_LINK_V2) and
            ((in_array(Header::FIRST_PAYMENT_MIN_AMOUNT, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = Header::FIRST_PAYMENT_MIN_AMOUNT;
        }

        if ($type === Batch\Type::AUTH_LINK)
        {
            if (in_array(Header::AUTH_LINK_NACH_REFERENCE1, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = Header::AUTH_LINK_NACH_REFERENCE1;
            }
            if (in_array(Header::AUTH_LINK_NACH_REFERENCE2, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = Header::AUTH_LINK_NACH_REFERENCE2;
            }
            if (in_array(Header::AUTH_LINK_NACH_CREATE_FORM, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = Header::AUTH_LINK_NACH_CREATE_FORM;
            }
        }

        if (($type === Batch\Type::PAYMENT_LINK or $type === Batch\Type::PAYMENT_LINK_V2) and
            ((in_array(Header::CURRENCY, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = Header::CURRENCY;
        }

        //
        // For Pricing Rule batch, we want to optionally accept the PRICING_RULE_UPDATE
        // headers.
        //
        if (($type === Batch\Type::PRICING_RULE) and
            ((in_array(Header::PRICING_RULE_UPDATE, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = Header::PRICING_RULE_UPDATE;
        }

        if ($type === Batch\Type::IIN_NPCI_RUPAY)
        {
            // header is dynamic for these dat files, adding hack to ignore
            $actualHeaders = [];
        }

        if ($type === Batch\Type::MPAN)
        {
            // header can contain empty columns when input file is CSV
            // this is due to trailing comma in the given input file
            $actualHeaders = array_filter($actualHeaders);
        }


        //
        // In case of subMerchant batch adding support of optional header merchant_id
        // With this data support team will be able to fix issue by their own and we can move this batch to new service .
        //
        if (($type === Batch\Type::SUB_MERCHANT) and
            ((in_array(Header::MERCHANT_ID, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = Header::MERCHANT_ID;
        }

        $valid = Header::areTwoHeadersSame($expectedHeaders, $actualHeaders);

        // Todo: Fix this hack!
        if (($valid === false) and ($type === Batch\Type::PAYMENT_LINK or $type === Batch\Type::PAYMENT_LINK_V2))
        {
            $expectedHeaders = array_replace($expectedHeaders, [4 => Header::AMOUNT_IN_PAISE]);

            $valid = Header::areTwoHeadersSame($expectedHeaders, $actualHeaders);
        }

        if ($valid === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                null,
                [
                    'expected_headers' => $expectedHeaders,
                    'input_headers'    => $actualHeaders,
                ]);
        }
    }
}

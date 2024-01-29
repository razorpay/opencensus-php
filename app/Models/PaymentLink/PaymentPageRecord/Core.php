<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\PaymentLink\Entity as PaymentLink;
use RZP\Models\PaymentLink\Service as Service;
use RZP\Models\PaymentLink\Template\UdfSchema;
use RZP\Models\Settings\Repository as Settings;
use RZP\Trace\TraceCode;
use RZP\Models\Batch\Entity as Batch;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception;

class Core extends Base\Core
{

    public function createRecord(
        Base\Entity $paymentPage,
        string $batchId,
        array $input
    ): Entity
    {

        if (isset($input[Entity::SMS_NOTIFY]))
        {
            $smsNotify = $input[Entity::SMS_NOTIFY];

            unset($input[Entity::SMS_NOTIFY]);
        }

        if (isset($input[Entity::EMAIL_NOTIFY]))
        {
            $emailNotify = $input[Entity::EMAIL_NOTIFY];

            unset($input[Entity::EMAIL_NOTIFY]);
        }

        $errors = [];

        try
        {
            $modifiedInput = $this->modifyInputForPaymentPageRecord($paymentPage, $batchId, $input, $errors);

            if (count($errors) > 0)
            {
                throw new BadRequestValidationFailureException(implode("\n", $errors));
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::PAYMENT_PAGE_CREATE_RECORD_EXCEPTION,
                [
                    'input'      => $input,
                ]
            );

            throw $ex;
        }

        $paymentPageRecord = (new Entity)->generateId();

        $paymentPageRecord->paymentLink()->associate($paymentPage);

        $paymentPageRecord->build($modifiedInput);

        $this->repo->saveOrFail($paymentPageRecord);

        //notify call
        $notify = [];

        if ((isset($smsNotify)) and
            ($smsNotify === true) and
            (isset($paymentPageRecord[Entity::CONTACT])) and
            ($paymentPageRecord[Entity::CONTACT] !== null))
            {
                $notify[Entity::CONTACTS] = [$paymentPageRecord[Entity::CONTACT]] ;
            }

        if ((isset($emailNotify)) and
            ($emailNotify === true) and
            (isset($paymentPageRecord[Entity::EMAIL])) and
            ($paymentPageRecord[Entity::EMAIL] !== null))
        {
            $notify[Entity::EMAILS] = [$paymentPageRecord[Entity::EMAIL]] ;
        }


        $merchant = $paymentPage->merchant;

        if (!empty($notify))
        {
            (new Service)->sendNotification($paymentPage->getPublicId(), $notify, $merchant);
        }

        return $paymentPageRecord;
    }

    public function uniqueRefIdValidations(array $input, array &$errors): array
    {
        $secondaryRefId1 = $input[Entity::SECONDARY_1];

        unset($input[Entity::SECONDARY_1]);

        $primaryRefId = $input[Entity::PRIMARY_REFERENCE_ID];

        if ($primaryRefId === $secondaryRefId1)
        {
            array_push($errors,
                'Secondary reference id cannot be same as primary reference id');
        }

        $rowCount =  $this->repo->payment_page_record->getMatchingRecordsCount($input[Entity::PAYMENT_LINK_ID], $secondaryRefId1);

        if ($rowCount !== 0)
        {
            array_push($errors,
                'Secondary reference id should be unique, duplicate value for '. $secondaryRefId1);
        }

        return $input;
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws BadRequestException
     */
    public function modifyInputForPaymentPageRecord(Base\Entity $paymentPage, string $batch_id, array $input, array &$errors)
    {
        $optionalBlankUDFFields = [];

        $id = PaymentLink::stripDefaultSign($paymentPage->getId());

        $response = $this->setUdfParameters($paymentPage, $input, $errors, $optionalBlankUDFFields);

        $this->validateUDFWithRegex($paymentPage, $input, $errors);

        $response = $this->setAmountParameters($paymentPage, $response, $input, $errors);

        $response[Entity::PAYMENT_LINK_ID] = $id;

        $response[Entity::MERCHANT_ID] = $paymentPage->getMerchantId();

        $response = $this->populateCustomFieldSchema($id, $paymentPage->getMerchantId(), $response, $optionalBlankUDFFields);

        $response = $this->uniqueRefIdValidations($response, $errors);

        $batch_id = Batch::silentlyStripSign($batch_id);
        $response[Entity::BATCH_ID] = $batch_id;

        $response[Entity::STATUS] = Status::UNPAID;

        return $response;
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function validateUDFWithRegex(Base\Entity $paymentPage, array $input, array &$errors)
    {
        $id = PaymentLink::stripDefaultSign($paymentPage->getId());

        $udfSchema = $paymentPage->getSettingsAccessor()->get(PaymentLink::UDF_SCHEMA);

        $udfSchema = json_decode($udfSchema, true);

        // build {name: value} array

        $keys = array_keys($input);
        $allUdfEntries = [];

        foreach ($udfSchema as $udf)
        {
            if (in_array($udf[PaymentLink::TITLE],$keys) === true)
            {
                // if the input field is empty and field is not required, skip the validation for that field
                if ((empty($input[$udf[PaymentLink::TITLE]]) === true) and ($udf['required'] === false))
                {
                    continue;
                }
                $name = $udf[PaymentLink::NAME];

                $allUdfEntries[$name] = $input[$udf[PaymentLink::TITLE]];
            }
        }

        $udfSchemaEntity = new UdfSchema($paymentPage);

        $validationErrors = $udfSchemaEntity->validate($allUdfEntries, true);

        if($validationErrors !== null)
        {
            $data = array_unique(array_column($validationErrors, 'property'));

            array_push($errors,"The validation failed for ".implode(',',$data));
        }
    }

    public function setUdfParameters(Base\Entity $paymentPage, array &$input, array &$errors, array &$optionalBlankUDFFields)
    {
        $id = PaymentLink::stripDefaultSign($paymentPage->getId());

        $udf_schema = (new Settings())->getSettings($id, 'payment_link', PaymentLink::UDF_SCHEMA);

        $udf_schema = json_decode($udf_schema[PaymentLink::VALUE], true);

        $response = [];
        $other_details = [];
        $isUnique = false;

        $keys = array_keys($input);

        foreach ($udf_schema as $udf)
        {
            if (($udf[Entity::REQUIRED] === true) and
                (!in_array($udf[PaymentLink::TITLE],$keys)))
            {
                array_push($errors, 'Mandatory field entry missing for '.$udf[PaymentLink::TITLE]);
            }

            // if a field is optional and blank, remove it
            if (($udf[Entity::REQUIRED] === false) and
                (isset($input[$udf[PaymentLink::TITLE]])) and
                ($input[$udf[PaymentLink::TITLE]] === ''))
            {
                // Record this field for adding it to custom_field_schema later
                array_push($optionalBlankUDFFields, $udf[PaymentLink::TITLE]);
                unset($input[$udf[PaymentLink::TITLE]]);
                continue;
            }

            if (($udf['pattern'] === 'date') and (isset($input[$udf[PaymentLink::TITLE]]) == true))
            {
                $input[$udf[PaymentLink::TITLE]] = $this->validateAndConvertDateFormat($input[$udf[PaymentLink::TITLE]], $errors);
            }

            // check if late fee due date has already passed
            if (($udf[PaymentLink::NAME] === Entity::LATE_FEE_DUE_DATE))
            {
               $lateFeeDueDate = $input[$udf[PaymentLink::TITLE]];

               if ($lateFeeDueDate !== null)
               {
                   if ($this->hasDatePassed($lateFeeDueDate) === true)
                   {
                       array_push($errors,
                           'Due date  '. $lateFeeDueDate . ' has already passed');
                   }

                   // check if corresponding late_fee_price_field is present
                   $this->checkLateFeePriceFieldForDueDate($paymentPage, $input, $errors);
               }

            }

            // storing all secondary_ref_id's also in the form of name: value mapping,
            // so that if their title changes we can still validate using name
            if (Entity::isSecondaryRefId($udf[PaymentLink::NAME]))
            {
                $other_details[$udf[PaymentLink::NAME]] = $input[$udf[PaymentLink::TITLE]];
            }

            // store secondary_reference_id_1 temporarily in input for security validation, this will be unsetted later
            if ($udf[PaymentLink::NAME] === Entity::SECONDARY_1)
            {
                $response[Entity::SECONDARY_1] = $input[$udf[PaymentLink::TITLE]];
            }

            if ($udf[PaymentLink::NAME] === Entity::PRIMARY_REF_ID)
            {
                try
                {
                    $this->repo->payment_page_record->findByPaymentPageAndPrimaryRefIdOrFail($id, $input[$udf[PaymentLink::TITLE]]);
                }
                catch (\Throwable $e)
                {
                    $isUnique = true;
                }

                if($isUnique === false)
                {
                    array_push($errors, 'Primary Reference ID should be unique');
                }

                $response[Entity::PRIMARY_REFERENCE_ID] = $input[$udf[PaymentLink::TITLE]];
            }
            elseif ($udf[PaymentLink::TITLE] === Entity::EMAIL_TITLE)
            {
                $response[Entity::EMAIL] = $input[$udf[PaymentLink::TITLE]];
            }
            elseif ($udf[PaymentLink::TITLE] === Entity::PHONE_TITLE)
            {
                $response[Entity::CONTACT] = $input[$udf[PaymentLink::TITLE]];
            }
            else
            {
                $other_details[$udf[PaymentLink::TITLE]] = $input[$udf[PaymentLink::TITLE]];
            }

        }

        $response[Entity::OTHER_DETAILS] = $other_details ?? '';

        return $response;
    }

    // Since batch doesn't support d M, Y date format, we take input as dd-mm-yyyy and convert it
    function validateAndConvertDateFormat(string $dateString, array &$errors)
    {
        $pattern = '/^(0[1-9]|[12][0-9]|3[01])-(0[1-9]|1[0-2])-\d{4}$/';

        // Use preg_match to check if the date string matches the pattern
        if (preg_match($pattern, $dateString) !== 1)
        {
            array_push($errors, "Invalid date format ". $dateString);

            return $dateString;
        }

        $timestamp = strtotime($dateString);

        return date('d M, Y', $timestamp);
    }

    public function setAmountParameters(Base\Entity $paymentLink, array $resp, array $input, array &$errors)
    {
        $payment_page_items = $this->repo->payment_page_item->fetchByPaymentLinkIdAndMerchant($paymentLink->getId(), $paymentLink->getMerchantId());

        $other_details = [];
        $resp[Entity::AMOUNT] = 0;
        $resp[Entity::TOTAL_AMOUNT] = 0;

        $keys = array_keys($input);

        foreach ($payment_page_items as $paymentPageItem)
        {
            $item = $paymentPageItem->item;

            if (($paymentPageItem[Entity::MANDATORY] === true) and
                ((!in_array($item[PaymentLink::NAME],$keys)) or 
                (strlen($input[$item[PaymentLink::NAME]]) == 0)))
            {
                array_push($errors,
                    'Mandatory field entry missing for '.$item[PaymentLink::NAME]);
            }

            if ($paymentPageItem->isLateFeePriceField() === true)
            {
                $lateFeeRate = $input[$item[PaymentLink::NAME]];

                if (($lateFeeRate !== null) and ($lateFeeRate !== ''))
                {
                    // currently only one late_fee_rate will be present
                    $other_details[Entity::LATE_FEE_PRICES] = [
                        Entity::LATE_FEE_RATE_1 =>  $input[$item[PaymentLink::NAME]]
                    ];

                    // check if the due date is present for corresponding late_fee_price_field
                    $this->checkLateFeeDueDateForPriceField($paymentLink, $input, $errors);
                }

                continue;
            }


            // add all price fields in amount
            if ((isset($input[$item[PaymentLink::NAME]]) === true) and
               (strlen($input[$item[PaymentLink::NAME]]) > 0))
            {
                $resp[Entity::AMOUNT] = $resp[Entity::AMOUNT] + $input[$item[PaymentLink::NAME]];
                
                $other_details[$item[PaymentLink::NAME]] = $input[$item[PaymentLink::NAME]];
            }
        }

        // total_amount will be populated when a payment is captured for this record
        $resp[Entity::TOTAL_AMOUNT] = 0;

        if ($resp[Entity::AMOUNT] < 100)
        {
            array_push($errors, 'Payment amount is lesser than the minimum amount allowed');
        }

        $other_details = array_merge($resp[Entity::OTHER_DETAILS],$other_details);
        $other_details = json_encode($other_details);

        $resp[Entity::OTHER_DETAILS] = $other_details;

        return $resp;
    }

    public function getTotalLateFeeForRecord(String $lateFeeType, String $lateFeeDueDateTitle, array $paymentPageRecord)
    {
        $otherDetails = json_decode($paymentPageRecord[Entity::OTHER_DETAILS],true);

        $lateFeeDueDate = $otherDetails[$lateFeeDueDateTitle];

        $lateFeeRate = $otherDetails[Entity::LATE_FEE_PRICES][Entity::LATE_FEE_RATE_1];

        if (($lateFeeDueDate === null) or ($lateFeeRate === null))
        {
            return null;
        }

        if ($this->hasDatePassed($lateFeeDueDate) === false)
        {
            return null;
        }

        if ($lateFeeType === Entity::FLAT_LATE_FEE)
        {
            return $lateFeeRate;
        }
        else
        {
            $numberOfDays = $this->calculateDaysDifference($lateFeeDueDate);

            return $numberOfDays * $lateFeeRate;
        }
    }

    public function calculateDaysDifference(string $lateFeeDueDate): int
    {
        // Parse the input date
        $lateFeeDueDate = Carbon::createFromFormat('d M, Y', $lateFeeDueDate);

        // Get the current date
        $currentDate = Carbon::now();

        // Calculate the difference in days and round up
        return ceil($currentDate->diffInDays($lateFeeDueDate, true));
    }

    public function hasDatePassed(string $inputDate)
    {
        // Parse the input date using the defined pattern
        $parsedDate = Carbon::createFromFormat('d M, Y', $inputDate, 'Asia/Kolkata');

        // Compare the dates
        return $parsedDate->isPast();
    }

    // check if a price field is present for late_fee_due_Date
    public function checkLateFeePriceFieldForDueDate(Base\Entity $paymentPage, array $input, array &$errors)
    {
        // currently only 1 late fee price field and due date can be presnt,
        $payment_page_items = $this->repo->payment_page_item->fetchByPaymentLinkIdAndMerchant($paymentPage->getId(), $paymentPage->getMerchantId());

        foreach ($payment_page_items as $paymentPageItem)
        {
            $item = $paymentPageItem->item;

            if ($paymentPageItem->isLateFeePriceField() === true)
            {
                if (array_key_exists($item[PaymentLink::NAME],$input) === true)
                {
                    return;
                }
            }
        }

        array_push($errors,
            'Late fee price field should be present if due date is passed');
    }

    // check if due_date is present for a corresponding late_fee_price_field
    public function checkLateFeeDueDateForPriceField(Base\Entity $paymentPage, array $input, array &$errors)
    {
        $udfSchema = $paymentPage->getSettingsAccessor()->get(PaymentLink::UDF_SCHEMA);

        $udfSchema = json_decode($udfSchema, true);

        $lateFeeDueDate = array_first($udfSchema, function($json) {
            return $json['name'] === Entity::LATE_FEE_DUE_DATE;
        });

        if ($lateFeeDueDate === null)
        {
            array_push($errors,
                'Due date not set for payment page');
        }

        if (isset($input[$lateFeeDueDate['title']]) === false)
        {
            array_push($errors,
                'Due date should be passed if late fee price field is passed');
        }

    }

    // to generate next field title for custom_field_schema, eg: if input is field_8, output would be field_9
    private function generateNextFieldTitle(string $title)
    {
        // Extract the numeric part from the input string
        preg_match('/(\d+)$/', $title, $matches);
        $numericPart = $matches[1] ?? 0;

        // Increment the numeric part
        $nextNumericPart = (int)$numericPart + 1;

        // Combine it back with the original prefix
        $nextTitle = preg_replace('/(\d+)$/', $nextNumericPart, $title);

        return $nextTitle;
    }

    public function populateCustomFieldSchema(string $id, string $merchantId, array $response, array &$optionalBlankUDFFields): array
    {
        $allFields = (new Settings())->getSettings($id, 'payment_link', PaymentLink::ALL_FIELDS);

        $allFields = json_decode($allFields['value'], true);
        $otherDetails = json_decode($response[Entity::OTHER_DETAILS], true);

        $custom_field_schema = [];

        $lastFieldTitle = '';

        foreach ($otherDetails as $title => $value)
        {
            if (array_key_exists($title, $allFields) === true)
            {
                $fieldTitle = $allFields[$title];

                $custom_field_schema[$fieldTitle] = ['key' => $title, 'value' => $value, 'dataType' => Constants::STRING];

                $lastFieldTitle = $fieldTitle;
            }
        }

        // add missing udf fields in other details to custom_field_schema
        foreach ($optionalBlankUDFFields as $key)
        {
            if (array_key_exists($key, $allFields) === true)
            {
                $fieldTitle = $allFields[$key];

                $custom_field_schema[$fieldTitle] = ['key' => $key, 'value' => '' ,'dataType' => Constants::STRING];

                $lastFieldTitle = $fieldTitle;
            }

        }

        // for all optional fields that were skipped, add blank values
        $payment_page_items = $this->repo->payment_page_item->fetchByPaymentLinkIdAndMerchant($id, $merchantId);

        foreach ($payment_page_items as $paymentPageItem)
        {
            $item = $paymentPageItem->item;

            $title = $item[PaymentLink::NAME];

            if (isset($otherDetails[$title]) === false)
            {
                if ($paymentPageItem->isLateFeePriceField() === false)
                {
                    $fieldTitle = $allFields[$title];

                    $custom_field_schema[$fieldTitle] = ['key' => $title, 'value' => '', 'dataType' => Constants::STRING];

                    $lastFieldTitle = $fieldTitle;
                }
            }

        }

        // if other_details has late_fee_config, then it should its value in custom_field_schema so that it appears in report
        if (isset($otherDetails[Entity::LATE_FEE_PRICES]) === true)
        {
            $lateFeeRate = $otherDetails[Entity::LATE_FEE_CONFIG][Entity::LATE_FEE_RATE_1];

            $nextFieldTitle = $this->generateNextFieldTitle($lastFieldTitle);

            $custom_field_schema[$nextFieldTitle] = ['key' => 'Late Fee Rate', 'value' => $lateFeeRate ,'dataType' => Constants::STRING];
        }

        $response[Entity::CUSTOM_FIELD_SCHEMA] = json_encode($custom_field_schema);

        return $response;
    }
}

<?php

namespace RZP\Models\PaymentLink\PaymentPageRecord;

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

        try
        {
            $modifiedInput = $this->modifyInputForPaymentPageRecord($paymentPage, $batchId, $input);
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

    public function uniqueRefIdValidations(array $input): array
    {
        $secondaryRefId1 = $input[Entity::SECONDARY_1];

        unset($input[Entity::SECONDARY_1]);

        $primaryRefId = $input[Entity::PRIMARY_REFERENCE_ID];

        if ($primaryRefId === $secondaryRefId1)
        {
            throw new BadRequestValidationFailureException(
                'Secondary reference id cannot be same as primary reference id');
        }

        $rowCount =  $this->repo->payment_page_record->getMatchingRecordsCount($input[Entity::PAYMENT_LINK_ID], $secondaryRefId1);

        if ($rowCount !== 0)
        {
            throw new BadRequestValidationFailureException(
                'Secondary reference id should be unique, duplicate value for '. $secondaryRefId1);
        }

        return $input;
    }

    public function modifyInputForPaymentPageRecord(Base\Entity $paymentPage, string $batch_id, array $input)
    {
        $id = PaymentLink::stripDefaultSign($paymentPage->getId());

        $response = $this->setUdfParameters($id, $input);

        $this->validateUDFWithRegex($paymentPage, $input);

        $response = $this->setAmountParameters($paymentPage, $response, $input);

        $response[Entity::PAYMENT_LINK_ID] = $id;

        $response[Entity::MERCHANT_ID] = $paymentPage->getMerchantId();

        $response = $this->populateCustomFieldSchema($id, $response);

        $response = $this->uniqueRefIdValidations($response);

        $batch_id = Batch::silentlyStripSign($batch_id);
        $response[Entity::BATCH_ID] = $batch_id;

        $response[Entity::STATUS] = Status::UNPAID;

        return $response;
    }

    public function validateUDFWithRegex(Base\Entity $paymentPage, array $input)
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
                $name = $udf[PaymentLink::NAME];

                $allUdfEntries[$name] = $input[$udf[PaymentLink::TITLE]];
            }
        }

        $udfSchemaEntity = new UdfSchema($paymentPage);

        $udfSchemaEntity->validate($allUdfEntries);
    }

    public function setUdfParameters(string $id, array $input)
    {

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
                throw new BadRequestValidationFailureException(
                    'Mandatory field entry missing for '.$udf[PaymentLink::TITLE]);
            }

            // storing all secondary_ref_id's also in the form of name: value mapping,
            // so that if their title changes we can still validate using name
            if (Entity::isSecondaryRefId($udf[PaymentLink::NAME]))
            {
                $other_details[$udf[PaymentLink::NAME]] = $input[$udf[PaymentLink::TITLE]];
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
                    throw new BadRequestValidationFailureException(
                        'Primary Reference ID should be unique');
                }

                $response[Entity::PRIMARY_REFERENCE_ID] = $input[$udf[PaymentLink::TITLE]];
            }
            elseif ($udf[PaymentLink::NAME] === Entity::EMAIL)
            {
                $response[Entity::EMAIL] = $input[$udf[PaymentLink::TITLE]];
            }
            elseif ($udf[PaymentLink::NAME] === Entity::PHONE)
            {
                $response[Entity::CONTACT] = $input[$udf[PaymentLink::TITLE]];
            }
            else
            {

                if (Entity::isSecondaryRefId($udf[PaymentLink::NAME]) === true)
                {
                    if (($udf[Entity::PATTERN] === Entity::EMAIL) and
                        (isset($response[Entity::EMAIL]) === false))
                    {
                        $response[Entity::EMAIL] = $input[$udf[PaymentLink::TITLE]];
                    }

                    if (($udf[Entity::PATTERN] === Entity::PHONE) and
                        (isset($response[Entity::CONTACT]) === false))
                    {
                        $response[Entity::CONTACT] = $input[$udf[PaymentLink::TITLE]];
                    }

                }
                $other_details[$udf[PaymentLink::TITLE]] = $input[$udf[PaymentLink::TITLE]];
            }

            // store secondary_reference_id_1 temporarily in input for security validation, this will be unsetted later
            if ($udf[PaymentLink::NAME] === Entity::SECONDARY_1)
            {
                $response[Entity::SECONDARY_1] = $input[$udf[PaymentLink::TITLE]];
            }
        }

        $response[Entity::OTHER_DETAILS] = $other_details ?? '';

        return $response;
    }

    public function setAmountParameters(Base\Entity $paymentLink, array $resp, array $input)
    {
        $payment_page_items = $this->repo->payment_page_item->fetchByPaymentLinkIdAndMerchant($paymentLink->getId(), $paymentLink->getMerchantId());

        $other_details = [];
        $resp[Entity::AMOUNT] = 0;
        $resp[Entity::TOTAL_AMOUNT] = 0;

        $keys = array_keys($input);

        foreach ($payment_page_items as $paymentPageItem) {

            $item = $paymentPageItem->item;
            if (($paymentPageItem[Entity::MANDATORY] === true) and
                (!in_array($item[PaymentLink::NAME],$keys)))
            {
                throw new BadRequestValidationFailureException(
                    'Mandatory field entry missing for '.$item[PaymentLink::NAME]);
            }

            $other_details[$item[PaymentLink::NAME]] = $input[$item[PaymentLink::NAME]];

            //will add only mandatory price fields in amount
                if($paymentPageItem[Entity::MANDATORY] === true)
                {
                    $resp[Entity::AMOUNT] = $resp[Entity::AMOUNT] + $input[$item[PaymentLink::NAME]];
                }

            $resp[Entity::TOTAL_AMOUNT] = $resp[Entity::TOTAL_AMOUNT] + $input[$item[PaymentLink::NAME]];

        }

        if ($resp[Entity::AMOUNT] < 100)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MINIMUM_ALLOWED_AMOUNT);
        }

        $other_details = array_merge($resp[Entity::OTHER_DETAILS],$other_details);
        $other_details = json_encode($other_details);

        $resp[Entity::OTHER_DETAILS] = $other_details;

        return $resp;
    }

    public function populateCustomFieldSchema(string $id, array $response): array
    {
        $allFields = (new Settings())->getSettings($id, 'payment_link', PaymentLink::ALL_FIELDS);

        $allFields = json_decode($allFields['value'], true);

        $otherDetails = json_decode($response[Entity::OTHER_DETAILS], true);

        $custom_field_schema = [];

        foreach ($otherDetails as $title => $value)
        {
            if (array_key_exists($title, $allFields) === true)
            {
                $fieldTitle = $allFields[$title];

                $custom_field_schema[$fieldTitle] = ['key' => $title, 'value' => $value, 'dataType' => Constants::STRING];
            }
        }

        $response[Entity::CUSTOM_FIELD_SCHEMA] = json_encode($custom_field_schema);

        return $response;
    }
}

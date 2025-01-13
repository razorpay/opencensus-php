<?php

namespace RZP\Models\ClarificationDetail;

use RZP\Base;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\Detail\Status;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Repository as MerchantRepo;

class Validator extends Base\Validator
{
    protected static $adminCreateRules = [
        'clarification_reasons'     => 'required|array',
        'old_clarification_reasons' => 'required|array',
    ];

    protected static $createRules      = [
        Entity::ID            => 'sometimes|string|size:14',
        Entity::MERCHANT_ID   => 'required|string|size:14',
        Entity::STATUS        => 'required|string|in:submitted,under_review,verified,rejected,needs_clarification',
        Entity::COMMENT_DATA  => 'sometimes|array|nullable',
        Entity::MESSAGE_FROM  => 'required|string|in:system,admin,merchant',
        Entity::GROUP_NAME    => 'required|string',
        Entity::FIELD_DETAILS => 'sometimes|array|nullable',
        Entity::METADATA      => 'sometimes|array|nullable',
    ];

    public function validateAdminClarificationReasons($merchantId, $input)
    {
        $this->validateInput('admin_create', $input);

        if (empty($input[Constants::CLARIFICATION_REASONS]) === false)
        {
            $merchant = (new MerchantRepo())->getMerchant($merchantId);

            $merchantDetails = $merchant->merchantDetail;

            $merchantStatus = $merchantDetails->getActivationStatus();

            if ($merchantStatus === Status::NEEDS_CLARIFICATION)
            {
                throw new BadRequestValidationFailureException(
                    PublicErrorDescription::BAD_REQUEST_INVALID_INPUT_FOR_NC
                );
            }
        }
    }

    protected static $editRules = [
        Entity::STATUS => 'required|string|in:submitted,under_review,verified,rejected,needs_clarification',
    ];

    public function validateClarificationExists($merchantId)
    {
        if ((new Service())->isEligibleForRevampNC($merchantId) === true)
        {
            $result =
                (new Repository)->getByMerchantIdAndStatus($merchantId, Constants::NEEDS_CLARIFICATION)
                                ->toArray();

            if (empty($result) === true)
            {
                throw new BadRequestValidationFailureException(
                    PublicErrorDescription::INVALID_STATUS_CHANGE_NC);
            }
        }
    }

    public function validateAndRemoveDocumentFields($fieldsInput): array
    {
        foreach ($fieldsInput as $fieldName => $fieldValue)
        {
            //validate when the field is document if the document exists or not
            // and document id is same as in the input
            if (in_array($fieldName, Type::VALID_DOCUMENTS))
            {
                $document = (new \RZP\Models\Merchant\Document\Repository())->findDocumentById($fieldValue);

                if (empty($document) === true or $document->getDocumentType() != $fieldName)
                {
                    throw new BadRequestValidationFailureException(
                        PublicErrorDescription::BAD_REQUEST_INVALID_DOCUMENT_FOR_NC . ": " . $fieldName
                    );
                }

                unset($fieldsInput[$fieldName]);

            }
        }

        return $fieldsInput;
    }

    public function validateClarificationDetails($merchantId, $input)
    {
        $merchant = (new MerchantRepo())->getMerchant($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $merchantStatus = $merchantDetails->getActivationStatus();

        if ($merchantStatus !== Status::NEEDS_CLARIFICATION)
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_MERCHANT_STATUS_NC
            );
        }

        foreach ($input as $groupName => $details)
        {
            $groupClarificationDetails =
                (new Repository())->getLatestByMerchantIdAndGroup($merchantId, $groupName);

            //validate field details if the input has fields information
            if (isset($details[Constants::FIELD_DETAILS]) === true)
            {
                //validate if the all the fields in group are in the input or not
                foreach ($groupClarificationDetails->getFieldDetails() as $fieldName => $fieldValue)
                {
                    if (array_key_exists($fieldName, $details[Constants::FIELD_DETAILS]) === false)
                    {
                        $errorValues = [
                            'fieldName'    => $fieldName,
                            'fieldDetails' => $details[Constants::FIELD_DETAILS]
                        ];

                        $this->getTrace()->info(TraceCode::NC_REVAMP_MISSING_FIELDS,[
                            $errorValues
                        ]);

                        throw new BadRequestValidationFailureException(
                            PublicErrorDescription::BAD_REQUEST_REQUIRED_FIELDS_FOR_NC,
                            null,
                            $errorValues
                        );
                    }
                }
            }
        }
    }

    // validate of all the groups were submitted before nc form is submitted
    public function validateNCSubmission($merchantId)
    {
        $result =
            (new Repository())->getByMerchantIdAndStatus($merchantId, Constants::NEEDS_CLARIFICATION)
                              ->toArray();

        if (empty($result) === false)
        {
            throw new BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_CLARIFICATIONS_PENDING_NC
            );
        }
    }

    public function validateFieldDetails($merchantId, $input)
    {
        $merchant = (new MerchantRepo())->getMerchant($merchantId);

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails->edit($input);

    }
}

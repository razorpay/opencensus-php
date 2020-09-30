<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\PaperMandate;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const MAX_UPLOADED_FILE_SIZE = 6144000; // ~ 6 MB

    protected static $createRules = [
        Entity::UPLOADED_FILE_ID => 'required|string',
    ];

    protected static $editRules = [
        Entity::UPLOADED_FILE_ID            => 'sometimes',
        Entity::EMAIL_ID                    => 'sometimes',
        Entity::AMOUNT_IN_WORDS             => 'sometimes',
        Entity::UTILITY_CODE                => 'sometimes',
        Entity::REFERENCE_1                 => 'sometimes',
        Entity::BANK_NAME                   => 'sometimes',
        Entity::DEBIT_TYPE                  => 'sometimes',
        Entity::MICR                        => 'sometimes',
        Entity::FREQUENCY                   => 'sometimes',
        Entity::SIGNATURE_PRESENT_TERTIARY  => 'sometimes',
        Entity::UNTIL_CANCELLED             => 'sometimes',
        Entity::SIGNATURE_PRESENT_SECONDARY => 'sometimes',
        Entity::NACH_TYPE                   => 'sometimes',
        Entity::ACCOUNT_NUMBER              => 'sometimes',
        Entity::NACH_DATE                   => 'sometimes',
        Entity::PHONE_NUMBER                => 'sometimes',
        Entity::TERTIARY_ACCOUNT_HOLDER     => 'sometimes',
        Entity::UMRN                        => 'sometimes',
        Entity::COMPANY_NAME                => 'sometimes',
        Entity::IFSC_CODE                   => 'sometimes',
        Entity::REFERENCE_2                 => 'sometimes',
        Entity::ACCOUNT_TYPE                => 'sometimes',
        Entity::AMOUNT_IN_NUMBER            => 'sometimes',
        Entity::END_DATE                    => 'sometimes',
        Entity::SPONSOR_CODE                => 'sometimes',
        Entity::SIGNATURE_PRESENT_PRIMARY   => 'sometimes',
        Entity::SECONDARY_ACCOUNT_HOLDER    => 'sometimes',
        Entity::START_DATE                  => 'sometimes',
        Entity::PRIMARY_ACCOUNT_HOLDER      => 'sometimes',
        Entity::FORM_CHECKSUM               => 'sometimes',
        Entity::EXTRACTED_RAW_DATA          => 'sometimes',
        Entity::ENHANCED_IMAGE              => 'sometimes',
    ];

    protected static $createForPaymentRules = [
        PaperMandate\Entity::FORM_UPLOADED => 'required|custom',
    ];

    protected static $supportedUploadFileMimeTypes = [
        'image/jpeg'      => true,
        'image/png'       => true,
        'application/pdf' => false,
    ];

    protected function validateFormUploaded($attribute, $file)
    {
        if ((empty($file) === true) or
            (array_get(self::$supportedUploadFileMimeTypes, $file->getMimeType(), false) === false))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_UNKNOWN_FILE_TYPE);
        }

        if ($file->getSize() > self::MAX_UPLOADED_FILE_SIZE)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NACH_FILE_SIZE_EXCEEDS_LIMIT);
        }
    }
}

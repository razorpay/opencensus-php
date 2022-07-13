<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const TDS_DETAILS = 'tds_details';

    const ATTACHMENT = 'attachment';

    protected static $createRules = [
        Entity::PAYOUT_ID                   => 'required|string|size:14',
        Entity::QUEUE_IF_LOW_BALANCE_FLAG   => 'sometimes|bool',
        Entity::TDS_CATEGORY_ID             => 'sometimes|int',
        Entity::ADDITIONAL_INFO             => 'sometimes|json',
    ];

    protected static $tdsDetailsRules = [
        Entity::CATEGORY_ID  => 'required|integer',
        Entity::TDS_AMOUNT   => 'required|integer'
    ];

    protected static $attachmentRules = [
        Entity::ATTACHMENTS_FILE_ID    => 'required|string',
        Entity::ATTACHMENTS_FILE_NAME  => 'required|string',
        Entity::ATTACHMENTS_FILE_HASH  => 'sometimes|string'
    ];

    public function validateAttachmentFileIdHash(array $attachmentInput)
    {
        $inputFileId = $attachmentInput[Entity::ATTACHMENTS_FILE_ID];

        if (array_key_exists(Entity::ATTACHMENTS_FILE_HASH, $attachmentInput) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FILE_HASH_MISSING_FOR_ATTACHMENT,
                null,
                [
                    'file_id' => $inputFileId,
                ]);
        }

        $inputFileIdHash = $attachmentInput[Entity::ATTACHMENTS_FILE_HASH];

        $computedFileIdHash = Utils::generateAttachmentFileIdHash($inputFileId);

        if ($inputFileIdHash !== $computedFileIdHash)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_FILE_HASH_FOR_ATTACHMENT,
                null,
                [
                    'file_id' => $inputFileId,
                ]);
        }
    }
}


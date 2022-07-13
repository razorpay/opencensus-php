<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Constants\HashAlgo;

class Utils
{
    public static function generateAttachmentFileIdHash(string $fileId): string
    {
        $secret = getenv('PAYOUTS_ATTACHMENTS_HASH_SECRET');

        return hash_hmac(HashAlgo::SHA256, $fileId, $secret, false);
    }

    public static function prepareAttachmentInfoFromInput(array $input): array
    {
        $attachmentsInfo = array();

        if (array_key_exists(Entity::ATTACHMENTS, $input) === true)
        {
            $attachmentsInInput = $input[Entity::ATTACHMENTS_KEY];

            foreach ($attachmentsInInput as $attachmentItem)
            {
                $attachmentInfo = [
                    Entity::ATTACHMENTS_FILE_ID   => $attachmentItem[Entity::ATTACHMENTS_FILE_ID],
                    Entity::ATTACHMENTS_FILE_NAME => $attachmentItem[Entity::ATTACHMENTS_FILE_NAME],
                ];

                array_push($attachmentsInfo, $attachmentInfo);
            }
        }

        return $attachmentsInfo;
    }
}

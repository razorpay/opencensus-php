<?php

namespace RZP\Models\Merchant\Document;

class ResponseHelper
{
    const UPLOAD_FILE_RESPONSE_KEY_MAPPER = [
        Constants::MIME    => Constants::MIME_TYPE,
        Constants::TYPE    => Constants::PURPOSE,
        Entity::CREATED_AT => Entity::CREATED_AT,
        Constants::SIZE    => Constants::SIZE,
        Entity::ID         => Entity::ID
    ];

    public static function getUploadFileResponse(array $data): array
    {
        $response = [];

        foreach ($data as $type => $fileData)
        {
            foreach ($fileData as $key => $value)
            {
                if (array_key_exists($key, self::UPLOAD_FILE_RESPONSE_KEY_MAPPER))
                {
                    $newKey = self::UPLOAD_FILE_RESPONSE_KEY_MAPPER[$key];

                    $response[$newKey] = $value;
                }
            }
        }

        return $response;
    }


}
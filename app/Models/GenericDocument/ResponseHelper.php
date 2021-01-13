<?php

namespace RZP\Models\GenericDocument;

class ResponseHelper
{
    const DOWNLOAD_FILE_RESPONSE_KEY_MAPPER = [
        Constants::SIGNED_URL => Constants::URL,
        Constants::CREATED_AT => Constants::CREATED_AT
    ];

    const UPLOAD_FILE_RESPONSE_KEY_MAPPER = [
        Constants::MIME       => Constants::MIME_TYPE,
        Constants::TYPE       => Constants::PURPOSE,
        Constants::CREATED_AT => Constants::CREATED_AT,
        Constants::SIZE       => Constants::SIZE,
        Constants::ID         => Constants::ID
    ];

    public static function getDownloadFileResponse(array $data): array
    {

        $response = [];

        foreach ($data as $key => $value)
        {
            if (array_key_exists($key, self::DOWNLOAD_FILE_RESPONSE_KEY_MAPPER))
            {
                $newKey = self::DOWNLOAD_FILE_RESPONSE_KEY_MAPPER[$key];

                $response[$newKey] = $value;
            }
        }

        return $response;
    }

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
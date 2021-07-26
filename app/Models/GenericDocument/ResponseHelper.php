<?php

namespace RZP\Models\GenericDocument;

class ResponseHelper
{
    const DOWNLOAD_FILE_RESPONSE_KEY_MAPPER = [
        Constants::SIGNED_URL   => Constants::URL,
        Constants::CREATED_AT   => Constants::CREATED_AT,
        Constants::MIME         => Constants::MIME_TYPE,
        Constants::TYPE         => Constants::PURPOSE,
        Constants::SIZE         => Constants::SIZE,
        Constants::ID           => Constants::ID,
        Constants::DISPLAY_NAME => Constants::DISPLAY_NAME,
    ];

    const UPLOAD_FILE_RESPONSE_KEY_MAPPER = [
        Constants::MIME         => Constants::MIME_TYPE,
        Constants::TYPE         => Constants::PURPOSE,
        Constants::CREATED_AT   => Constants::CREATED_AT,
        Constants::SIZE         => Constants::SIZE,
        Constants::ID           => Constants::ID,
        Constants::DISPLAY_NAME => Constants::DISPLAY_NAME,
    ];

    public static function getDownloadFileResponse(array $data): array
    {

        $response = [];

        $response[Constants::ENTITY] = Constants::DOCUMENT_ENTITY;

        foreach ($data as $key => $value)
        {
            if (array_key_exists($key, self::DOWNLOAD_FILE_RESPONSE_KEY_MAPPER))
            {
                $newKey = self::DOWNLOAD_FILE_RESPONSE_KEY_MAPPER[$key];

                $response[$newKey] = $value;
            }
        }

        $response[Constants::ID] = self::getDocumentId($response[Constants::ID], Constants::FILE_ID_SIGN, Constants::DOCUMENT_ID_SIGN);

        return $response;
    }

    public static function getUploadFileResponse(array $data): array
    {
        $response = [];

        $response[Constants::ENTITY] = Constants::DOCUMENT_ENTITY;

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

        $response[Constants::ID] = self::getDocumentId($response[Constants::ID], Constants::FILE_ID_SIGN, Constants::DOCUMENT_ID_SIGN);

        return $response;
    }

    public static function getDocumentId(string $fileStoreId, string $searchKey, string $replaceKey): string
    {
        $documentIds = self::getDocumentIds([$fileStoreId], $searchKey, $replaceKey);

        return $documentIds[0];
    }

    public static function getDocumentIds(array $fileStoreIds, string $searchKey, string $replaceKey): array
    {
        $documentIds = [];

        if (empty($fileStoreIds) === false)
        {
            foreach ($fileStoreIds as $fileStoreId)
            {
                $documentId = str_replace($searchKey, $replaceKey, $fileStoreId);
                array_push($documentIds, $documentId);
            }
        }

        return $documentIds;
    }
}

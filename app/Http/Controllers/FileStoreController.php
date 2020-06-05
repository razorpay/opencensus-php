<?php

namespace RZP\Http\Controllers;

use ApiResponse;

class FileStoreController extends Controller
{
    /**
     * This gets a signed url for the given fileId.
     *
     * @param string $fileId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getFile(string $fileId)
    {
        $signedUrl = $this->service()->fetchFileSignedUrlById($fileId);

        $data = ['url' => $signedUrl];

        return ApiResponse::json($data);
    }

    /**
     * Gets signed id for entity's file
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getSignedUrlForEntity(string $entity, string $entityId)
    {
        $data = $this->service()->fetchSignedUrlForEntityFile($entity, $entityId);

        return ApiResponse::json($data);
    }
}

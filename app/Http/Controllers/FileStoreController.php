<?php

namespace RZP\Http\Controllers;

use Redirect;
use ApiResponse;
use RZP\Models\FileStore;

class FileStoreController extends Controller
{
    /**
     * This gets a signed url for the given fileId.
     * @param string $fileId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getFile(string $fileId)
    {
        $signedUrl = (new FileStore\Service)->fetchFileSignedUrlById($fileId);

        return Redirect::to($signedUrl);
    }

    /**
     * 'file_get_signed_url' : GET /files/{entity}/{entityId}/signed-url
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getSignedUrlForEntity(string $entity, string $entityId)
    {
        $signedUrl = (new FileStore\Service)->fetchFileSignedUrlForEntity($entity, $entityId);

        return Redirect::to($signedUrl);
    }
}

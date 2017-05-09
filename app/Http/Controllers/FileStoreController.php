<?php

namespace RZP\Http\Controllers;

use Redirect;
use ApiResponse;
use RZP\Models\FileStore;

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
        $signedUrl = (new FileStore\Service)->fetchFileSignedUrlById($fileId);

        return Redirect::to($signedUrl);
    }

    /**
     * Gets signed id for entity's file
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getSignedUrlForEntity(string $entity, string $entityId)
    {
        $signedUrl = (new FileStore\Service)->fetchSignedUrlForEntityFile($entity, $entityId);

        return Redirect::to($signedUrl);
    }
}

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
}

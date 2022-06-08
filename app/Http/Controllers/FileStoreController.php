<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;

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

    /**
     * This update bucket name and region for the given fileIds.
     */
    public function updateFileBucketAndRegion()
    {
        $request = Request::all();

        $input = $request['input'];

        $data = $this->service()->updateFileBucketAndRegion($input);

        return ApiResponse::json($data);
    }
}

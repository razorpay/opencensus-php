<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    /**
     * Fetches single activation file signed url
     * by given file store id
     *
     * @param string $fileId
     *
     * @return string (url)
     * @throws Exception\BadRequestException
     */
    public function fetchFileSignedUrlById(string $fileId)
    {
        $file = $this->repo->file_store->findByPublicId($fileId);

        $signedUrl = (new Accessor)->getSignedUrlOfFile($file);

        return $signedUrl;
    }
}

<?php

namespace RZP\Tests\Functional\Helpers;

use Illuminate\Http\UploadedFile;

trait PayoutAttachmentTrait
{
    /**
     * create new file in Storage
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function createNewFile(string $fileName)
    {
        $localFilePath = __DIR__ . '/../Storage/' . $fileName;
        $file = fopen($localFilePath, 'w');
        fwrite($file, '');
        fclose($file);

        return $localFilePath;
    }

    /**
     * create upload payout attachment upload request
     *
     * @param string $fileName
     * @param string $localFilePath
     *
     * @return array
     */
    protected function createUploadFileRequest(string $fileName, string $localFilePath)
    {
        return [
            'url'    => '/payouts/attachment',
            'method' => 'POST',
            'files'  => [
                'file' => new UploadedFile(
                    $localFilePath,
                    $fileName,
                    'image/png',
                    null,
                    true)
            ]
        ];
    }
}

<?php

namespace RZP\Tests\Functional\Helpers;

use Illuminate\Http\UploadedFile;

trait FileUploadTrait
{
    /**
     * @param string $file
     *
     * @return UploadedFile
     */
    public function createUploadedFile(string $file): UploadedFile
    {
        $this->assertFileExists($file);

        $mimeType = 'application/pdf';
        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }
}

<?php

namespace RZP\Models\Dispute\File;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\FileStore;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Service extends Base\Service
{
    use FileHandlerTrait;

    public function getDynamicFileName(string $file_name)
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y_H:i:s');

        return $file_name . '_' . $this->mode . '_' . $time;
    }

    /**
     * Generates XLSX file base on file data and stores in file store as type bulk_disputes_file
     *
     * @param array $fileData
     * @param string $fileName
     * @return mixed
     * @throws Exception\LogicException
     */
    public function generateFile(array $fileData, string $fileName)
    {
        $extension = FileStore\Format::XLSX;

        $creator = new FileStore\Creator;

        $newFileName = $this->getDynamicFileName($fileName);

        $creator->extension($extension)
                ->content($fileData)
                ->name($newFileName)
                ->store(FileStore\Store::S3)
                ->type(FileStore\Type::BULK_DISPUTES_FILE)
                ->save();

        $signedFileUrl = $creator->getSignedUrl();

        return $signedFileUrl['url'];
    }

    /**
     * @param string $filePath
     * @param string $extension
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function parseFile(string $filePath, string $extension) : array
    {
        $data = [];

        switch ($extension)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
                // Dispute service expects headers as the first array
                $data = $this->parseExcelSheets($filePath, 0);
                break;

            case FileStore\Format::CSV:
                $data = $this->parseCsvFile($filePath, ',');
                break;

            default:
                throw new Exception\BadRequestValidationFailureException(
                    'File is neither an Excel nor a CSV type.',
                    [
                        'Extension' => $extension,
                    ]);
        }

        if (empty($data) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'File is Empty'
            );
        }

        return $data;
    }

    public function getFileData($file) : array
    {
        $filepath = $file->getRealPath();

        $extension = $file->getClientOriginalExtension();

        if (ends_with($filepath, $extension) === false)
        {
            $filepathWExt = $filepath . '.' . $extension;

            rename($filepath, $filepathWExt);

            $filepath = $filepathWExt;
        }

        $data = $this->parseFile($filepath, $extension);

        // Delete Local File
        unlink($filepath); // nosemgrep : php.lang.security.unlink-use.unlink-use

        return $data;
    }
}

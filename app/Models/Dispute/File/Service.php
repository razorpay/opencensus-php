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

    protected function parseFile(string $filePath, string $extension) : array
    {
        switch ($extension)
        {
            case FileStore\Format::XLSX:
            case FileStore\Format::XLS:
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

        if (empty($data))
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

        $data = $this->parseFile($filepath, $extension);

        // Delete Local File
        unlink($filepath);

        return $data;
    }
}

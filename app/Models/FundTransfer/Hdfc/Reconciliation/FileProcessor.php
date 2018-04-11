<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use Carbon\Carbon;

use RZP\Exception\LogicException;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class FileProcessor extends BaseProcessor
{
    protected static $fileToReadName  = 'Hdfc_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Hdfc_Settlement_Reconciliation';

    protected static $channel = Channel::HDFC;

    protected static $delimiter = ',';

    protected function storeFile($reconcileFile)
    {
        $this->storeReconciledFile($reconcileFile);
    }

    protected function getRowProcessorNamespace($row)
    {
        return __NAMESPACE__ . '\\RowProcessor';
    }

    protected function setDate($data)
    {
        $date = Carbon::createFromFormat('m/d/Y', $data[0][Headings::TRANSACTION_DATE]);

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date->format('d-m-Y');
    }

    public static function getHeadings(): array
    {
        return Headings::getResponseFileHeadings();
    }

    /**
     * Checks the reverse file extension is same as specified by the bank
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getFileExtensionForParsing(string $filePath): string
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Sample extension format of reverse file is `R715`.
        // RegEx below matched 1 char followed by 2 or 3 digits
        if (preg_match('/^[a-zA-Z]\d{2,3}$/', $extension) === 1)
        {
            return $extension;
        }

        throw new LogicException(
            "Extension not handled: {$extension}",
            null,
            [
                'file_path' => $filePath
            ]);
    }
}

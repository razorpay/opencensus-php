<?php

namespace RZP\Models\FundTransfer\Axis2\Reconciliation;

use RZP\Models\FileStore;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Axis2\Headings;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Base\Reconciliation\FileProcessor as BaseProcessor;

class Processor extends BaseProcessor
{
    protected static $fileToReadName = 'Axis_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Axis_Settlement_Reconciliation';

    protected static $channel = Channel::AXIS2;

    protected static $fileExtensions = [
        FileStore\Format::TXT,
    ];

    protected static $delimiter = '^';

    public static function getHeadings()
    {
        return Headings::getResponseFileHeadings();
    }

    protected function getRowProcessorNamespace($row)
    {
        return __NAMESPACE__ . '\\RowProcessor';
    }

    protected function setDate($data)
    {
        // Date in the excel file are formatted to get the proper date from the integer value use the below method
        $date = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($data[0][Headings::PAYMENT_RUN_DATE], 'DD-MM-YYYY');

        //update the format so that recon mail is appended to settlement mail
        $this->date = $date;
    }

    protected function storeFile($reconFile)
    {
        $this->storeReconciledFile($reconFile);
    }

    /**
     * Power Access confirms extension to be txt if not specified
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getFileExtensionForParsing(string $filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array($extension, static::$fileExtensions, true) === true)
        {
            return $extension;
        }
        else
        {
            return FileStore\Format::TXT;
        }

    }

    /**
     * Notify file transfer errors in Power Access
     *
     * @param array $input
     * @return array
     */
    public function notifyH2HErrors(array $input)
    {
        $fileExtensions = [
            FileStore\Format::TXT,
            FileStore\Format::XLSX,
        ];

        $response = ['message' => 'No Transfer Error Found'];

        if ((isset($input['source']) === true) and
            ($input['source'] === 'lambda'))
        {

            $fileInfo = explode('/', $input['key']);

            $fileName = is_array($fileInfo) ? $fileInfo[count($fileInfo) - 1] : '';

            $extension = pathinfo($fileName, PATHINFO_EXTENSION);

            if (in_array($extension, $fileExtensions, true) === true)
            {
                $response['message'] = ' Error processing file ' . $fileName;

                (new SlackNotification)->send(
                    'File Processing Error',
                    [
                        'channel'   => Channel::AXIS2,
                        'file_name' => $fileName
                    ],
                    null,
                    1
                );
            }
        }

        return $response;
    }
}

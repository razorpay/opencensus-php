<?php

namespace RZP\Models\Payout\Bulk;

use Excel;
use PHPExcel_Style_Fill;
use PHPExcel_Style_Font;
use PHPExcel_Style_Border;
use PHPExcel_Style_Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use RZP\Exception\LogicException;

use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Models\Batch\Header;
use RZP\Models\Payout\Entity;
use RZP\Excel\PayoutExportSheet;
use RZP\Excel\Export as ExcelExport;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Base
{
    use FileHandlerTrait;

    const FILE_ID       = 'file_id';
    const SIGNED_URL    = 'signed_url';
    const FILE_NAME     = 'sample_batch_payouts';


    // Constants used for styling
    const START_COLUMN = 'A';
    const END_COLUMN = 'V';

    // This column number has to be changed if any new columns are added
    // before FUND_ACCOUNT_PHONE_NUMBER
    const FUND_ACCOUNT_PHONE_NUMBER_COLUMN = 'L';

    const FORMAT_LEADING_PLUS_SIGN = '+0';

    const EXCEL_HEADERS_FOR_PAYOUT_FILE = [
            'Mandatory Fields',
            'Mandatory Fields',
            'Mandatory Fields',
            'Mandatory Fields',
            'Mandatory Fields',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            '(Conditionally Mandatory) If you want to make a payout to an existing fund account you can just add their Fund Account Id.',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
            'Optional Fields',
    ];

    public function createAndSaveSampleFile($extension, Merchant\Entity $merchant)
    {
        [$name, $outputFileLocalPath] = $this->createSampleFileWithExtension($extension, $merchant);

        $ufhFile = $this->saveFile($name, $outputFileLocalPath, $extension, $merchant);

        $ufhSignedUrl = $ufhFile->getSignedUrl();

        return [
            self::FILE_ID    => FileStore\Entity::getSignedId($ufhSignedUrl['id']),
            self::SIGNED_URL => $ufhSignedUrl['url'],
        ];
    }

    protected function createSampleFileWithExtension($ext, Merchant\Entity $merchant)
    {
        $entries = $this->getInputEntries($merchant);

        $name = Entity::generateUniqueId();

        $fullName = $name . '.' . $ext;

        $dir = storage_path('files/filestore') . '/payouts';

        switch ($ext)
        {
            case FileStore\Format::CSV:
                $txt = $this->generateTextWithHeadings($entries, ',', false, array_keys(current($entries)));
                return [$name, $this->createTxtFile($fullName, $txt, $dir)];

            case FileStore\Format::XLSX:
                $fileMetadata = $this->createExcelObject($entries, $dir, $name, $ext, []);

                return [$name, $fileMetadata['full']];

            default:
                throw new LogicException("Extension not handled: {$ext}");
        }
    }

    /**
     * @param string $filePath
     * @param string $ext
     * @param Merchant\Entity $merchant
     *
     * @return FileStore\Creator
     *
     * @throws LogicException
     */
    protected function saveFile(string $uniqueId, string $filePath, string $ext, Merchant\Entity $merchant): FileStore\Creator
    {
        $filePrefix = 'payouts/' . $uniqueId . '/';

        $name = $filePrefix . self::FILE_NAME;

        $ufh = new FileStore\Creator;

        $ufh->localFilePath($filePath)
            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[$ext][0])
            ->name($name)
            ->extension($ext)
            ->merchant($merchant)
            ->type(FileStore\Type::PAYOUT_SAMPLE);

        return $ufh->save();
    }

    protected function getInputEntries(Merchant\Entity $merchant)
    {
        return [];
    }

    public function createExcelObject($data, $dir, $name, $extension, $columnFormat = [], $sheetNames = ['Sheet 1'])
    {
        // The extra space in the end is being added so that the number doesn't get converted to scientific notation
        foreach ($data as &$rows)
        {
            $rows[Header::RAZORPAYX_ACCOUNT_NUMBER] = $rows[Header::RAZORPAYX_ACCOUNT_NUMBER] . ' ';
            $rows[Header::FUND_ACCOUNT_NUMBER]      = $rows[Header::FUND_ACCOUNT_NUMBER] . ' ';
            $rows[Header::CONTACT_MOBILE_2]         = $rows[Header::CONTACT_MOBILE_2] . ' ';
        }

        // Forcing all columns to store data as text.
        for ($columnNumber = self::START_COLUMN; $columnNumber <= self::END_COLUMN; $columnNumber++)
        {
            $columnFormat[$columnNumber] = NumberFormat::FORMAT_TEXT;

            if ($columnNumber === self::FUND_ACCOUNT_PHONE_NUMBER_COLUMN)
            {
                // This format has been defined in this file because current library doesn't
                // support leading plus sign for numbers
                $columnFormat[$columnNumber] = self::FORMAT_LEADING_PLUS_SIGN;
            }
        }

        $sheetNames = (is_array($sheetNames) === false) ? [$sheetNames] : $sheetNames;

        // todo: Update custom export for this
        $path = $dir . DIRECTORY_SEPARATOR . $name . '.' . $extension;

        $excel = (new ExcelExport)->setSheets(function() use ($sheetNames, $data, $columnFormat) {
            $sheetsInfo = [];
            foreach ($sheetNames as $sheetName)
            {
                $sheetsInfo[$sheetName] = (new PayoutExportSheet(($data[$sheetName] ?? $data)))->setTitle($sheetName)->setColumnFormat($columnFormat);
            }

            return $sheetsInfo;
        })->store($path, 'local_storage');

        return [
            'full'  => $path,
            'path'  => $dir,
            'file'  => $name . '.' . $extension,
            'title' => $name,
            'ext'   => $extension
        ];
    }
}

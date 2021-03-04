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
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Base
{
    use FileHandlerTrait;

    const FILE_ID       = 'file_id';
    const SIGNED_URL    = 'signed_url';
    const FILE_NAME     = 'sample_batch_payouts';

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
                $fileMeta = $this->createExcelObject($entries, $name, [])
                                 ->store($ext, $dir, true);

                return [$name, $fileMeta['full']];

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

    public function createExcelObject($data, $name, $columnFormat = [], $sheetNames = ['Sheet 1'])
    {
        // Forcing all columns to store data as text.
        for ($columnNumber = 'A'; $columnNumber <= 'T'; $columnNumber++)
        {
            $columnFormat[$columnNumber] = NumberFormat::FORMAT_TEXT;
        }

        $sheetNames = (is_array($sheetNames) === false) ? [$sheetNames] : $sheetNames;

        $excel = Excel::create($name, function($excel) use ($data, $columnFormat, $sheetNames)
        {
            foreach ($sheetNames as $sheetName)
            {
                $excel->sheet($sheetName, function($sheet) use ($data, $columnFormat, $sheetName)
                {
                    $sheet->setFontSize(14);
                    $sheet->setFontFamily('Ubuntu Mono');

                    // If a columnFormat variable is specified.
                    // Use it.
                    if (empty($columnFormat) === false)
                    {
                        $sheet->setColumnFormat($columnFormat);
                    }

                    $sheet->fromArray(($data[$sheetName] ?? $data), null, 'A1', true, true);

                    $sheet->prependRowExplicit(1, self::EXCEL_HEADERS_FOR_PAYOUT_FILE);

                    // merge header cells
                    $sheet->mergeCells('A1:E1');
                    $sheet->mergeCells('F1:L1');
                    $sheet->mergeCells('M1:T1');

                    $sheet->getStyle('A1:E1')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_HAIR,
                            ),
                        ),
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'D9EAD3')
                        )
                    ));

                    $sheet->getStyle('A2:E2')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'D9EAD3')
                        )
                    ));

                    $sheet->getStyle('F1:L1')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        ),
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FFF2CC')
                        ),
                        'font' => array(
                            'color' => array('rgb' => '0000EE')
                        ),
                    ));

                    $sheet->getStyle('F2:L2')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FFF2CC')
                        )
                    ));

                    $sheet->getStyle('M1:T1')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        ),
                        'alignment' => array(
                            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FCE5CD')
                        )
                    ));

                    $sheet->getStyle('M2:T2')->applyFromArray(array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            ),
                        ),
                        'fill' => array(
                            'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                            'color' => array('rgb' => 'FCE5CD')
                        )
                    ));

                    $sheet->getCell('F1')->getHyperlink()->setUrl("https://razorpay.com/docs/razorpayx/bulk-payouts/");

                    $sheet->getStyle('F1')->getFont()->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);

                    $sheet->freezePane('A3');
                });
            }
        });

        $this->excel = $excel;

        return $excel;
    }
}

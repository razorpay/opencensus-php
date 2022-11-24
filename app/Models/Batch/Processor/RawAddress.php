<?php

namespace RZP\Models\Batch\Processor;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Batch\Entity;
use RZP\Reconciliator\Converter;
use RZP\Trace\TraceCode;
use RZP\Models\Batch;

class RawAddress extends Base
{
    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

    }

    /**
     * ufh + batch upload
     * Header validation
     * @param array $input
     * @return array
     */
    public function storeAndValidateInputFile(array $input): array
    {
        $this->trace->info(TraceCode::RAW_ADDRESS_BATCH_VALIDATOR_TRACE,[
            'message' => 'validating batch file for raw_address',
        ]);

        list($inputUfhFile, $entries) = $this->saveInputFileAndValidateEntries($input);

        $response = $this->getValidatedEntriesStatsAndPreview($entries);

        $response += $this->getFileIdAndSignedUrlFromFileEntity($inputUfhFile);

        if ($response[Batch\Constants::ERROR_COUNT] > 0)
        {
            $this->trace->info(TraceCode::ERROR_IN_VALIDATING_BATCH_FILE,
                [
                    self::FILE_ID                => $response[self::FILE_ID],
                    Batch\Constants::ERROR_COUNT => $response[Batch\Constants::ERROR_COUNT],
                ]
            );
        }

        $this->deleteLocalFiles();

        return $response;
    }

    /**
     * adding limit while loading
     * @param  string $filePath
     * @param int $numRowsToSkip
     * @return array
     */
    protected function parseExcelSheetsUsingPhpSpreadSheet($filePath, $numRowsToSkip = 0): array
    {
        $this->trace->info(TraceCode::RAW_ADDRESS_BATCH_VALIDATOR_TRACE,[
            'message' => 'parsing excel using raw address validator',
        ]);

        $rows =  $this->getRowsFromExcelSheetsSpout($filePath,5);

        return $rows['sheet0'];
    }

    protected function getRowsFromExcelSheetsSpout($filePath, int $endRow = 1): array
    {
        $reader = ReaderFactory::createFromType(Type::XLSX);
        $reader->open($filePath);

        return $this->getRowsFromExcelSheetsWithIndicesSpout($reader, $endRow);
    }

    protected function getRowsFromExcelSheetsWithIndicesSpout($reader,int $endRow = 1): array
    {
        $allSheetsContent = [];

        foreach ($reader->getSheetIterator() as $sheet)
        {
            $index = $sheet->getIndex();

            $sheetName = 'sheet' . $index;

            $this->setExcelSheetContentForSpout($allSheetsContent, $sheet, $sheetName, $endRow);
        }

        return $allSheetsContent;
    }

    protected function setExcelSheetContentForSpout(array & $allSheetsContent, $sheet, string $sheetName, int $endRow = 1)
    {
        $sheetHeaders = [];

        $allSheetsContent[$sheetName] = [];

        $rowIterator = $sheet->getRowIterator();

        $convertor = new Converter();

        foreach ($rowIterator as $row)
        {
            $row = $row->toArray();

            if ($rowIterator->key()  === 1)
            {
                $sheetHeaders = array_filter($convertor->normalizeHeaders($row));
            }
            if (($rowIterator->key() > $endRow && count($allSheetsContent[$sheetName]) > 1) ||
                $rowIterator->key() > 100)
            {
                break;
            }
        // this deals with the empty rows
            if (count(array_filter($row)) === 0)
            {
                continue;
            }
            $allSheetsContent[$sheetName][] = array_combine_pad($sheetHeaders, $row);
        }
        if ( count($allSheetsContent[$sheetName]) < 2 )
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, "invalid file, no values to preview");

        }
    }
}


<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Icici;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\XLSXHeaders;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\AccountOwnerInfo;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\StatementSummary;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\TransactionLineItem;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants\AccountStatementData;

class Xlsx extends Generator
{
    const HEADER_TO_CELL_MAP =
        [
            XLSXHeaders::SHEET_TITLE          => 'A2',
            XLSXHeaders::SHEET_SUB_TITLE      => 'A3',
            XLSXHeaders::ACCOUNT_NAME         => 'A4',
            XLSXHeaders::ACCOUNT_NUMBER       => 'A5',
            XLSXHeaders::STATEMENT_PERIOD     => 'A6',

            XLSXHeaders::NO                      => 'A11',
            XLSXHeaders::TRANSACTION_ID          => 'B11',
            XLSXHeaders::VALUE_DATE              => 'C11',
            XLSXHeaders::TRANSACTION_POSTED_DATE => 'D11',
            XLSXHeaders::CHEQUE_NO               => 'E11',
            XLSXHeaders::DESCRIPTION             => 'F11',
            XLSXHeaders::CR_DR                   => 'G11',
            XLSXHeaders::TRANSACTION_AMOUNT      => 'H11',
            XLSXHeaders::AVAILABLE_BALANCE       => 'I11',
        ];

    const ACCOUNT_OWNER_INFO_CELL_MAP =
        [
            AccountOwnerInfo::ACCOUNT_NAME     => 'B4',
            AccountOwnerInfo::ACCOUNT_NUMBER   => 'B5',
            AccountOwnerInfo::STATEMENT_PERIOD => 'B6',
        ];

    const TRANSACTION_DATA_TO_COLUMN =
        [
            TransactionLineItem::NO                      => 'A',
            TransactionLineItem::TRANSACTION_ID          => 'B',
            TransactionLineItem::VALUE_DATE              => 'C',
            TransactionLineItem::TRANSACTION_POSTED_DATE => 'D',
            TransactionLineItem::CHEQUE_NO               => 'E',
            TransactionLineItem::DESCRIPTION             => 'F',
            TransactionLineItem::CR_DR                   => 'G',
            TransactionLineItem::TRANSACTION_AMOUNT      => 'H',
            TransactionLineItem::AVAILABLE_BALANCE       => 'I',
        ];

    const XLSX                          = 'xlsx';

    const LOGO_CELL_RANGE               = 'A1:E1';

    const LOGO_PATH                     = '/img/icici_logo.jpg';


    const LOGO_POSTITION                = 'D1';

    const HEADER_CELL_RANGE             = 'A2:E2';

    const WORKING_COLUMN_LIST           = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

    const TRANSACTION_TITLE_CELL        = 'A10';

    const TRANSACTION_HEADER_CELL_RANGE = 'A11:I11';

    const TRANSACTION_CELL_FILL_COLOR   = 'ccccff';

    const TRANSACTION_DATA_START_ROW    = 12;

    const DEFAULT_XLSX_FORMAT           = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    // this will be determined after we know the transaction counts
    protected $summaryKeyMap  =
        [
            XLSXHeaders::STATEMENT_SUMMARY        => '',
            XLSXHeaders::OPENING_BALANCE          => '',
            XLSXHeaders::CLOSING_BALANCE          => '',
            XLSXHeaders::EFFECTIVE_BALANCE        => '',
            XLSXHeaders::STATEMENT_GENERATED_DATE => '',
            XLSXHeaders::DEBIT_COUNT              => '',
            XLSXHeaders::CREDIT_COUNT             => '',
        ];

    // this will be determined after we know the transaction counts
    protected $summaryDataMap =
        [
            StatementSummary::OPENING_BALANCE          => '',
            StatementSummary::CLOSING_BALANCE          => '',
            StatementSummary::EFFECTIVE_BALANCE        => '',
            StatementSummary::STATEMENT_GENERATED_DATE => '',
            StatementSummary::DEBIT_COUNT              => '',
            StatementSummary::CREDIT_COUNT             => '',
        ];

    const COLUMNS_WITH_CURRENCY = [
        StatementSummary::OPENING_BALANCE,
        StatementSummary::CLOSING_BALANCE,
        StatementSummary::EFFECTIVE_BALANCE,
    ];

    const EXPLICIT_STRING_COLUMNS = [
        AccountOwnerInfo::ACCOUNT_NUMBER,
    ];

    const COLUMNS_TO_NOT_BOLD = [
        self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::ACCOUNT_NUMBER],
        self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::STATEMENT_PERIOD],
    ];


    public function __construct($accountNumber, $channel, $fromDate, $toDate)
    {
        parent::__construct($accountNumber, $channel, $fromDate, $toDate);

        $this->calculateStatementSummaryCellValues();
    }

    public function getStatement()
    {
        $spreadsheet = $this->createTableView();

        $tmpFileName = $this->generateFileName(self::XLSX);

        $tmpFileFullPath =  self::TEMP_STORAGE_DIR . $tmpFileName;

        $writer = new XlsxWriter($spreadsheet);

        $writer->save($tmpFileFullPath);

        return $tmpFileFullPath;
    }

    protected function calculateStatementSummaryCellValues()
    {
        $tCount = $this->getTotalTransactionCount() + 1;

        $this->summaryKeyMap[XLSXHeaders::STATEMENT_SUMMARY] =
            'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->summaryKeyMap[XLSXHeaders::OPENING_BALANCE] =
            'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->summaryKeyMap[XLSXHeaders::CLOSING_BALANCE] =
            'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);

        $this->summaryKeyMap[XLSXHeaders::EFFECTIVE_BALANCE] =
            'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);

        $this->summaryKeyMap[XLSXHeaders::STATEMENT_GENERATED_DATE] =
            'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 4);

        $this->summaryKeyMap[XLSXHeaders::DEBIT_COUNT] =
            'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->summaryKeyMap[XLSXHeaders::CREDIT_COUNT] =
            'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);

        $this->summaryDataMap[StatementSummary::OPENING_BALANCE] =
            'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->summaryDataMap[StatementSummary::CLOSING_BALANCE] =
            'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);

        $this->summaryDataMap[StatementSummary::EFFECTIVE_BALANCE] =
            'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);

        $this->summaryDataMap[StatementSummary::STATEMENT_GENERATED_DATE] =
            'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 4);

        $this->summaryDataMap[StatementSummary::DEBIT_COUNT] =
            'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->summaryDataMap[StatementSummary::CREDIT_COUNT] =
            'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);
    }

    protected function createTableView(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $this->addLogo($sheet);

        $this->addHeaders($sheet);

        $this->addOwnerInfo($sheet);

        $this->addStatementSummary($sheet);

        $this->addTransactionTitle($sheet);

        $this->addTransactions($sheet);

        $this->addStyling($sheet);

        return $spreadsheet;
    }

    protected function addHeaders(Worksheet & $sheet)
    {
        foreach (self::HEADER_TO_CELL_MAP as $header => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $header);
        }

        foreach ($this->summaryKeyMap as $header => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $header);
        }
    }

    protected function addStatementSummary(Worksheet & $sheet)
    {
        $statementSummary = $this->data[AccountStatementData::STATEMENT_SUMMARY];

        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];

        foreach ($this->summaryDataMap as $dataPoint => $columnNumber)
        {
            $value = $statementSummary[$dataPoint];

            if (in_array($dataPoint, self::COLUMNS_WITH_CURRENCY, true) === true)
            {
                $value = $basicInfo[AccountOwnerInfo::CURRENCY] . ' ' . $value;
            }

            $sheet->setCellValue($columnNumber, $value);

        }
    }

    protected function addOwnerInfo(Worksheet & $sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];

        foreach (self::ACCOUNT_OWNER_INFO_CELL_MAP as $dataPoint => $columnNumber)
        {
            if (in_array($dataPoint, self::COLUMNS_WITH_CURRENCY, true) === true)
            {
                $sheet->setCellValue($columnNumber,
                                     $basicInfo[AccountOwnerInfo::CURRENCY] . ' ' . $basicInfo[$dataPoint]);
            }
            else if (in_array($dataPoint, self::EXPLICIT_STRING_COLUMNS, true) === true)
            {
                $sheet->setCellValueExplicit($columnNumber,
                                             $basicInfo[$dataPoint],
                                             DataType::TYPE_STRING2);
            }
            else
            {
                $sheet->setCellValue($columnNumber, $basicInfo[$dataPoint]);
            }
        }
    }

    protected function addTransactionTitle(Worksheet & $sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];

        // Ex: Transactions List - INTERNET BANK (INR) - 409000000083
        $transactionTitle = 'Transactions List - ' .
                            "{$basicInfo[AccountOwnerInfo::ACCOUNT_NAME]} ({$basicInfo[AccountOwnerInfo::CURRENCY]})" .
                            ' - ' . "{$basicInfo[AccountOwnerInfo::ACCOUNT_NUMBER]}";

        $sheet->setCellValue(self::TRANSACTION_TITLE_CELL, $transactionTitle);
    }

    protected function addLogo(Worksheet & $sheet)
    {
        $drawing = new Drawing();

        $drawing->setPath(public_path(). self::LOGO_PATH);

        $drawing->setCoordinates(self::LOGO_POSTITION);

        $drawing->setResizeProportional(false);

        $drawing->setWidthAndHeight(250,90);

        $drawing->setOffsetY(18);

        $drawing->setOffsetX(110);

        $drawing->setWorksheet($sheet);
    }

    protected function addTransactions(Worksheet & $sheet)
    {
        // loop over the statements and put in the transactions
        $transactions = $this->data[AccountStatementData::TRANSACTIONS];

        $currentRow   = self::TRANSACTION_DATA_START_ROW;

        foreach ($transactions as $lineItem)
        {
            foreach ($lineItem as $transactionKey => $transactionValue)
            {
                $cell = self::TRANSACTION_DATA_TO_COLUMN[$transactionKey] . (string) $currentRow;

                $sheet->setCellValue($cell, $transactionValue);
            }

            $currentRow++;
        }
    }

    /**
     * @param Worksheet $sheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function addStyling(Worksheet & $sheet)
    {
        $this->makeCellsBold($sheet);

        // merge the 5 cells for logo
        $sheet->mergeCells(self::LOGO_CELL_RANGE);

        // merge the 5 cells for header
        $sheet->mergeCells(self::HEADER_CELL_RANGE);

        // make the default width of all the columns a bit wider
        foreach (self::WORKING_COLUMN_LIST as $col)
        {
            $sheet->getColumnDimension($col)->setWidth(30);
        }

        $sheet->getRowDimension('1')->setRowHeight(90);

        $sheet->getStyle(self::HEADER_TO_CELL_MAP[XLSXHeaders::ACCOUNT_NUMBER])
              ->getAlignment()
              ->setWrapText(true);

        // give light-blue fill color to the transaction header
        $sheet->getStyle(self::TRANSACTION_HEADER_CELL_RANGE)
              ->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()
              ->setRGB(self::TRANSACTION_CELL_FILL_COLOR);

        // give all the cells of the transaction table blue border
        $lastTransactionIndex = 'I' . (string) (self::TRANSACTION_DATA_START_ROW +
                                                $this->getTotalTransactionCount() - 1);

        $transactionRange = 'A' .
                            (string) self::TRANSACTION_DATA_START_ROW .
                            ':' .
                            $lastTransactionIndex;

        $sheet->getStyle($transactionRange)->getBorders()
              ->applyFromArray(
                  [
                      'allBorders' =>
                          [
                              'borderStyle' => Border::BORDER_MEDIUM,
                              'color'       =>
                                  [
                                      'rgb' => self::TRANSACTION_CELL_FILL_COLOR
                                  ]
                          ]
                  ]);

        // sub-title styling
        $sheet->getStyle(self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_TITLE])
              ->getAlignment()
              ->setVertical(Alignment::VERTICAL_TOP)
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle(self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_TITLE])->getFont()->setSize(20);
        $sheet->getRowDimension('2')->setRowHeight(40);
    }

    protected function getTotalTransactionCount(): int
    {
        $transaction = $this->data[AccountStatementData::TRANSACTIONS];

        return count($transaction);
    }

    protected function makeCellsBold(Worksheet & $sheet)
    {
        $columnsToBold = array_merge(
            array_values(self::ACCOUNT_OWNER_INFO_CELL_MAP),
            array_values($this->summaryDataMap)
        );

        $columnsToBold =  array_diff($columnsToBold, self::COLUMNS_TO_NOT_BOLD);

        array_push($columnsToBold, self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_TITLE]);

        array_push($columnsToBold, self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_SUB_TITLE]);

        array_push($columnsToBold, self::TRANSACTION_TITLE_CELL);

        array_push($columnsToBold, $this->summaryKeyMap[XLSXHeaders::STATEMENT_SUMMARY]);

        foreach ($columnsToBold as $column)
        {
            $sheet->getStyle($column)->getFont()->setBold(1);
        }
    }
}

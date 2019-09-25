<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use RZP\Models\FileStore;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class Xlsx extends Generator
{
    const HEADER_TO_CELL_MAP =
        [
            XLSXHeaders::SHEET_TITLE          => 'A2',
            XLSXHeaders::SHEET_SUB_TITLE      => 'A3',
            XLSXHeaders::ACCOUNT_NAME         => 'A4',
            XLSXHeaders::HOME_BRANCH_NAME     => 'C4',
            XLSXHeaders::CUSTOMER_ADDRESS     => 'A5',
            XLSXHeaders::CUSTOMER_MOBILE      => 'A18',
            XLSXHeaders::CUSTOMER_EMAIL       => 'A19',
            XLSXHeaders::CUSTOMER_CIF_ID      => 'A21',
            XLSXHeaders::CURRENCY             => 'A22',
            XLSXHeaders::ACCOUNT_OPENING_DATE => 'A24',
            XLSXHeaders::ACCOUNT_TYPE         => 'A25',
            XLSXHeaders::ACCOUNT_STATUS       => 'A26',
            XLSXHeaders::ACCOUNT_NUMBER       => 'A27',
            XLSXHeaders::STATEMENT_PERIOD     => 'A28',
            XLSXHeaders::HOME_BRANCH_ADDRESS  => 'C5',
            XLSXHeaders::IFSC_CODE            => 'C17',
            XLSXHeaders::SANCTION_LIMIT       => 'C20',
            XLSXHeaders::DRAWING_POWER        => 'C21',
            XLSXHeaders::BRANCH_TIMINGS       => 'C22',
            XLSXHeaders::CALL_CENTER          => 'C25',
            XLSXHeaders::BRANCH_PHONE_NUMBER  => 'C26',
            XLSXHeaders::TRANSACTION_DATE     => 'A30',
            XLSXHeaders::TRANSACTION_DETAILS  => 'B30',
            XLSXHeaders::CHEQUE_ID            => 'C30',
            XLSXHeaders::VALUE_DATE           => 'D30',
            XLSXHeaders::WITHDRAWL_AMT        => 'E30',
            XLSXHeaders::DEPOSIT_AMT          => 'F30',
            XLSXHeaders::BALANCE              => 'G30',
        ];

    const ACCOUNT_OWNER_INFO_CELL_MAP =
        [
            AccountOwnerInfo::ACCOUNT_NAME         => 'B4',
            AccountOwnerInfo::CUSTOMER_ADDRESS     => 'B5',
            AccountOwnerInfo::CUSTOMER_ADDRESS_L2  => 'B7',
            AccountOwnerInfo::CUSTOMER_CITY        => 'B9',
            AccountOwnerInfo::CUSTOMER_STATE       => 'B11',
            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => 'B13',
            AccountOwnerInfo::CUSTOMER_MOBILE      => 'B18',
            AccountOwnerInfo::CUSTOMER_EMAIL       => 'B19',
            AccountOwnerInfo::CUSTOMER_CIF_ID      => 'B21',
            AccountOwnerInfo::CURRENCY             => 'B22',
            AccountOwnerInfo::ACCOUNT_OPENING_DATE => 'B24',
            AccountOwnerInfo::ACCOUNT_TYPE         => 'B25',
            AccountOwnerInfo::ACCOUNT_STATUS       => 'B26',
            AccountOwnerInfo::ACCOUNT_NUMBER       => 'B27',
            AccountOwnerInfo::STATEMENT_PERIOD     => 'B28',
            AccountOwnerInfo::HOME_BRANCH_NAME     => 'D4',
            AccountOwnerInfo::HOME_BRANCH_ADDRESS  => 'D5',
            AccountOwnerInfo::IFSC_CODE            => 'D17',
            AccountOwnerInfo::SANCTION_LIMIT       => 'D20',
            AccountOwnerInfo::DRAWING_POWER        => 'D21',
            AccountOwnerInfo::BRANCH_TIMINGS       => 'D22',
            AccountOwnerInfo::CALL_CENTER          => 'D25',
            AccountOwnerInfo::BRANCH_PHONE_NUMBER  => 'D26',
            AccountOwnerInfo::BRANCH_CITY          => 'D9',
            AccountOwnerInfo::BRANCH_STATE         => 'D11',
        ];

    # this will be determined after we know the transaction counts
    protected $SUMMARY_KEY_MAP =
        [
            XLSXHeaders::STATEMENT_SUMMARY        => '',
            XLSXHeaders::OPENING_BALANCE          => '',
            XLSXHeaders::CLOSING_BALANCE          => '',
            XLSXHeaders::EFFECTIVE_BALANCE        => '',
            XLSXHeaders::STATEMENT_GENERATED_DATE => '',
            XLSXHeaders::DEBIT_COUNT              => '',
            XLSXHeaders::CREDIT_COUNT             => '',
            XLSXHeaders::LIEN_AMOUNT              => '',
        ];

    protected $SUMMARY_DATA_MAP =
        [
            StatementSummary::OPENING_BALANCE          => '',
            StatementSummary::CLOSING_BALANCE          => '',
            StatementSummary::EFFECTIVE_BALANCE        => '',
            StatementSummary::STATEMENT_GENERATED_DATE => '',
            StatementSummary::DEBIT_COUNT              => '',
            StatementSummary::CREDIT_COUNT             => '',
            StatementSummary::LIEN_AMOUNT              => '',
        ];

    const TRANSACTION_DATA_TO_COLUMN =
        [
            TransactionLineItem::TRANSACTION_DATE    => 'A',
            TransactionLineItem::TRANSACTION_DETAILS => 'B',
            TransactionLineItem::CHEQUE_ID           => 'C',
            TransactionLineItem::VALUE_DATE          => 'D',
            TransactionLineItem::WITHDRAWAL_AMOUNT   => 'E',
            TransactionLineItem::DEPOSIT_AMOUNT      => 'F',
            TransactionLineItem::BALANCE             => 'G',
        ];

    const LOGO_CELL_RANGE               = 'A1:E1';

    const LOGO_PATH                     = 'views/bank_account_statement/RBL/rbllogo.png';

    const LOGO_POSTITION                = 'E1';

    const HEADER_CELL_RANGE             = 'A2:E2';

    const WORKING_COLUMN_LIST           = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    const TRANSACTION_TITLE_CELL        = 'A29';

    const TRANSACTION_HEADER_CELL_RANGE = 'A30:G30';

    const TRANSACTION_CELL_FILL_COLOR   = 'b19cd9';

    const TRANSACTION_DATA_START_ROW    = 31;

    const DEFAULT_XLSX_FORMAT           = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct($accountNumber, $channel, $fromDate, $toDate)
    {
        parent::__construct($accountNumber, $channel, $fromDate, $toDate);

        $this->calculateStatementSummaryCellValues();
    }

    protected function calculateStatementSummaryCellValues()
    {
        $tCount = $this->getTotalTransactionCount();

        $this->SUMMARY_KEY_MAP[XLSXHeaders::STATEMENT_SUMMARY]
            = 'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::OPENING_BALANCE]
            = 'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::CLOSING_BALANCE]
            = 'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::EFFECTIVE_BALANCE]
            = 'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::STATEMENT_GENERATED_DATE]
            = 'A' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 4);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::DEBIT_COUNT]
            = 'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::CREDIT_COUNT]
            = 'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_KEY_MAP[XLSXHeaders::LIEN_AMOUNT]
            = 'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_DATA_MAP[StatementSummary::OPENING_BALANCE]
            = 'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 1);

        $this->SUMMARY_DATA_MAP[StatementSummary::CLOSING_BALANCE]
            = 'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 2);

        $this->SUMMARY_DATA_MAP[StatementSummary::EFFECTIVE_BALANCE]
            = 'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);

        $this->SUMMARY_DATA_MAP[StatementSummary::STATEMENT_GENERATED_DATE]
            = 'B' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 4);

        $this->SUMMARY_DATA_MAP[StatementSummary::DEBIT_COUNT]
            = 'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_DATA_MAP[StatementSummary::CREDIT_COUNT]
            = 'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);

        $this->SUMMARY_DATA_MAP[StatementSummary::LIEN_AMOUNT]
            = 'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount);
    }

    function getStatement()
    {
        $spreadsheet = $this->createTableView($this->data);

        $tmpFileName = $this->accountNumber . '_' . $this->fromDate . '_' . $this->toDate;

        $tmpFileFullPath = storage_path('tmp/' . $tmpFileName);

        $writer = new XlsxWriter($spreadsheet);

        $writer->save($tmpFileFullPath);

        $fileStoreHandle = (new FileStore\Creator())->localFilePath($tmpFileFullPath)
                                                    ->name($tmpFileName)
                                                    ->mime(self::DEFAULT_XLSX_FORMAT)
                                                    ->extension(FileStore\Format::XLSX)
                                                    ->type(FileStore\Type::RBL_STATEMENT)
                                                    ->save()
                                                    ->getFileInstance();

        return $fileStoreHandle;
    }

    protected function createTableView($statementDate): Spreadsheet
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

    protected function addHeaders(& $sheet)
    {
        foreach (self::HEADER_TO_CELL_MAP as $header => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $header);
        }

        foreach ($this->SUMMARY_KEY_MAP as $header => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $header);
        }
    }

    protected function addStatementSummary(&$sheet)
    {
        $statementSummary = $this->data[AccountStatementData::STATEMENT_SUMMARY];

        foreach ($this->SUMMARY_DATA_MAP as $dataPoint => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $statementSummary[$dataPoint]);
        }
    }

    protected function addOwnerInfo(&$sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];

        foreach (self::ACCOUNT_OWNER_INFO_CELL_MAP as $dataPoint => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $basicInfo[$dataPoint]);
        }
    }

    protected function addTransactionTitle(& $sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];

        # Ex: Transactions List - INTERNET BANK (INR) - 409000000083
        $transactionTitle = 'Transactions List - ' .
                            "{$basicInfo[AccountOwnerInfo::ACCOUNT_NAME]} ({$basicInfo[AccountOwnerInfo::CURRENCY]})" .
                            "{$basicInfo[AccountOwnerInfo::ACCOUNT_NUMBER]}";

        $sheet->setCellValue(self::TRANSACTION_TITLE_CELL, $transactionTitle);
    }

    protected function addLogo(& $sheet)
    {
        $drawing = new Drawing();

        $drawing->setPath(resource_path(self::LOGO_PATH));

        $drawing->setCoordinates(self::LOGO_POSTITION);

        $drawing->setWorksheet($sheet);
    }

    protected function addTransactions(& $sheet)
    {
        # loop over the statements and put in the transactions
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

    protected function addStyling(& $sheet)
    {
        $this->makeCellsBold($sheet);

        # merge the 5 cells for logo
        $sheet->mergeCells(self::LOGO_CELL_RANGE);

        # merge the 5 cells for header
        $sheet->mergeCells(self::HEADER_CELL_RANGE);

        # make the default width of all the columns a bit wider
        foreach (self::WORKING_COLUMN_LIST as $col)
        {
            $sheet->getColumnDimension($col)->setWidth(30);
        }

        $sheet->getRowDimension('1')->setRowHeight(60);

        # give light-blue fill color to the transaction header
        $sheet->getStyle(self::TRANSACTION_HEADER_CELL_RANGE)->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB(self::TRANSACTION_CELL_FILL_COLOR);

        # give all the cells of the transaction table blue border
        $lastTransactionIndex = 'G' . (string) (self::TRANSACTION_DATA_START_ROW +
                                                $this->getTotalTransactionCount() - 1);

        $transactionRange     = 'A' . (string) self::TRANSACTION_DATA_START_ROW . ':'
                                . $lastTransactionIndex;

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
    }

    protected function getTotalTransactionCount(): int
    {
        $transaction = $this->data[AccountStatementData::TRANSACTIONS];

        return count($transaction);
    }

    protected function makeCellsBold(&$sheet)
    {
        $columnsToBold = array_merge(
            array_values(self::ACCOUNT_OWNER_INFO_CELL_MAP),
            array_values($this->SUMMARY_DATA_MAP)
        );

        array_push($columnsToBold, self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_TITLE]);

        array_push($columnsToBold, self::HEADER_TO_CELL_MAP[XLSXHeaders::SHEET_SUB_TITLE]);

        array_push($columnsToBold, self::TRANSACTION_TITLE_CELL);

        array_push($columnsToBold, $this->SUMMARY_KEY_MAP[XLSXHeaders::STATEMENT_SUMMARY]);

        foreach ($columnsToBold as $column)
        {
            $sheet->getStyle($column)->getFont()->setBold(1);
        }
    }
}

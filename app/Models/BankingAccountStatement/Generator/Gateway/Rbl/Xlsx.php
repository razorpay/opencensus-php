<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\XLSXHeaders;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\AccountOwnerInfo;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\StatementSummary;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\TransactionLineItem;
use RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants\AccountStatementData;

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
            XLSXHeaders::IFSC_CODE            => 'C18',
            XLSXHeaders::SANCTION_LIMIT       => 'C20',
            XLSXHeaders::DRAWING_POWER        => 'C21',
            XLSXHeaders::BRANCH_TIMINGS       => 'C22',
            XLSXHeaders::CALL_CENTER          => 'C25',
            XLSXHeaders::BRANCH_PHONE_NUMBER  => 'C26',
            XLSXHeaders::TRANSACTION_DATE     => 'A31',
            XLSXHeaders::TRANSACTION_DETAILS  => 'B31',
            XLSXHeaders::CHEQUE_ID            => 'C31',
            XLSXHeaders::VALUE_DATE           => 'D31',
            XLSXHeaders::WITHDRAWL_AMT        => 'E31',
            XLSXHeaders::DEPOSIT_AMT          => 'F31',
            XLSXHeaders::BALANCE              => 'G31',
        ];

    const ACCOUNT_OWNER_INFO_CELL_MAP =
        [
            AccountOwnerInfo::ACCOUNT_NAME         => 'B4',
            AccountOwnerInfo::CUSTOMER_ADDRESS     => 'B5',
            AccountOwnerInfo::CUSTOMER_ADDRESS_L2  => 'B7',
            AccountOwnerInfo::CUSTOMER_CITY        => 'B9',
            AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => 'B11',
            AccountOwnerInfo::CUSTOMER_STATE       => 'B13',
            AccountOwnerInfo::CUSTOMER_COUNTRY     => 'B15',
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
            AccountOwnerInfo::IFSC_CODE            => 'D18',
            AccountOwnerInfo::SANCTION_LIMIT       => 'D20',
            AccountOwnerInfo::DRAWING_POWER        => 'D21',
            AccountOwnerInfo::BRANCH_TIMINGS       => 'D22',
            AccountOwnerInfo::CALL_CENTER          => 'D25',
            AccountOwnerInfo::BRANCH_PHONE_NUMBER  => 'D26',
            AccountOwnerInfo::BRANCH_CITY          => 'D9',
            AccountOwnerInfo::BRANCH_STATE         => 'D11',
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

    const XLSX                          = 'xlsx';

    const LOGO_CELL_RANGE               = 'A1:E1';

    const LOGO_PATH                     = '/img/rbllogo.png';


    const LOGO_POSTITION                = 'E1';

    const HEADER_CELL_RANGE             = 'A2:E2';

    const WORKING_COLUMN_LIST           = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

    const TRANSACTION_TITLE_CELL        = 'A30';

    const TRANSACTION_HEADER_CELL_RANGE = 'A31:G31';

    const TRANSACTION_CELL_FILL_COLOR   = 'ccccff';

    const TRANSACTION_DATA_START_ROW    = 32;

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
            XLSXHeaders::LIEN_AMOUNT              => '',
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
            StatementSummary::LIEN_AMOUNT              => '',
        ];

    const COLUMNS_WITH_CURRENCY = [
        AccountOwnerInfo::SANCTION_LIMIT,
        AccountOwnerInfo::DRAWING_POWER,
        StatementSummary::OPENING_BALANCE,
        StatementSummary::CLOSING_BALANCE,
        StatementSummary::EFFECTIVE_BALANCE,
        StatementSummary::LIEN_AMOUNT,
    ];

    const EXPLICIT_STRING_COLUMNS = [
        AccountOwnerInfo::ACCOUNT_NUMBER,
    ];

    const COLUMNS_TO_NOT_BOLD = [
        self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::ACCOUNT_NUMBER],
        self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::STATEMENT_PERIOD],
        self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::CALL_CENTER],
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

        $this->summaryKeyMap[XLSXHeaders::LIEN_AMOUNT] =
            'C' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);

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

        $this->summaryDataMap[StatementSummary::LIEN_AMOUNT] =
            'D' . (string) (self::TRANSACTION_DATA_START_ROW + $tCount + 3);
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

        $drawing->setOffsetY(18);

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

        $sheet->getStyle(self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::HOME_BRANCH_ADDRESS])
              ->getAlignment()
              ->setWrapText(true);

        $sheet->getStyle(self::ACCOUNT_OWNER_INFO_CELL_MAP[AccountOwnerInfo::BRANCH_TIMINGS])
              ->getAlignment()
              ->setWrapText(true);

        // give light-blue fill color to the transaction header
        $sheet->getStyle(self::TRANSACTION_HEADER_CELL_RANGE)
              ->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()
              ->setRGB(self::TRANSACTION_CELL_FILL_COLOR);

        // give all the cells of the transaction table blue border
        $lastTransactionIndex = 'G' . (string) (self::TRANSACTION_DATA_START_ROW +
                                                $this->getTotalTransactionCount() - 1);

        $transactionRange     = 'A' .
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

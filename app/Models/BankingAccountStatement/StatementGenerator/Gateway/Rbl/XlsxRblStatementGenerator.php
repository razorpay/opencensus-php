<?php


namespace RZP\Models\BankingAccountStatement\StatementGenerator\Gateway\Rbl;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxRblStatementGenerator extends RBLStatementGenerator
{
    const HEADERS = [
        XLSXHeaders::HEADER => 'A2',
        XLSXHeaders::SUB_HEADER => 'A3',
        XLSXHeaders::ACCOUNT_NAME => 'A4',
        XLSXHeaders::HOME_BRANCH_NAME => 'C4',
        XLSXHeaders::CUSTOMER_ADDRESS => 'A5',
        XLSXHeaders::CUSTOMER_MOBILE => 'A18',
        XLSXHeaders::CUSTOMER_EMAIL => 'A19',
        XLSXHeaders::CUSTOMER_CIF_ID => 'A21',
        XLSXHeaders::CURRENCY => 'A22',
        XLSXHeaders::ACCOUNT_OPENING_DATE => 'A24',
        XLSXHeaders::ACCOUNT_TYPE => 'A25',
        XLSXHeaders::ACCOUNT_STATUS => 'A26',
        XLSXHeaders::ACCOUNT_NUMBER => 'A27',
        XLSXHeaders::STATEMENT_PERIOD => 'A28',
        XLSXHeaders::HOME_BRANCH_ADDRESS => 'C5',
        XLSXHeaders::IFSC_CODE => 'C17',
        XLSXHeaders::SANCTION_LIMIT => 'C20',
        XLSXHeaders::DRAWING_POWER => 'C21',
        XLSXHeaders::BRANCH_TIMINGS => 'C22',
        XLSXHeaders::CALL_CENTER => 'C25',
        XLSXHeaders::BRANCH_PHONE_NUMBER => 'C26',
        XLSXHeaders::TRANSACTION_DATE => 'A30',
        XLSXHeaders::TRANSACTION_DETAILS => 'B30',
        XLSXHeaders::CHEQUE_ID => 'C30',
        XLSXHeaders::VALUE_DATE => 'D30',
        XLSXHeaders::WITHDRAWL_AMT => 'E30',
        XLSXHeaders::DEPOSIT_AMT => 'F30',
        XLSXHeaders::BALANCE => 'G30',
    ];

    const DATA_COLUMNS = [
        AccountOwnerInfo::ACCOUNT_NAME => 'B4',
        AccountOwnerInfo::CUSTOMER_ADDRESS => 'B5',
        AccountOwnerInfo::CUSTOMER_ADDRESS_L2 => 'B7',
        AccountOwnerInfo::CUSTOMER_CITY => 'B9',
        AccountOwnerInfo::CUSTOMER_STATE => 'B11',
        AccountOwnerInfo::CUSTOMER_ADDRESS_PIN => 'B13',
        AccountOwnerInfo::CUSTOMER_MOBILE => 'B18',
        AccountOwnerInfo::CUSTOMER_EMAIL => 'B19',
        AccountOwnerInfo::CUSTOMER_CIF_ID => 'B21',
        AccountOwnerInfo::CURRENCY => 'B22',
        AccountOwnerInfo::ACCOUNT_OPENING_DATE => 'B24',
        AccountOwnerInfo::ACCOUNT_TYPE => 'B25',
        AccountOwnerInfo::ACCOUNT_STATUS => 'B26',
        AccountOwnerInfo::ACCOUNT_NUMBER => 'B27',
        AccountOwnerInfo::STATEMENT_PERIOD => 'B28',
        AccountOwnerInfo::HOME_BRANCH_NAME => 'D4',
        AccountOwnerInfo::HOME_BRANCH_ADDRESS => 'D5',
        AccountOwnerInfo::IFSC_CODE => 'D17',
        AccountOwnerInfo::SANCTION_LIMIT => 'D20',
        AccountOwnerInfo::DRAWING_POWER => 'D21',
        AccountOwnerInfo::BRANCH_TIMINGS => 'D22',
        AccountOwnerInfo::CALL_CENTER => 'D25',
        AccountOwnerInfo::BRANCH_PHONE_NUMBER => 'D26',
        AccountOwnerInfo::BRANCH_CITY => 'D9',
        AccountOwnerInfo::BRANCH_STATE => 'D11',
        AccountOwnerInfo::BRANCH_PINCODE => 'D13',
    ];

    const LOGO_CELL = 'A1';
    const LOGO_CELL_RANGE = 'A1:E1';
    const HEADER_CELL_RANGE = 'A2:E2';
    const WORKING_COLUMN_LIST = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    const TRANSACTION_TITLE_CELL = 'A29';
    const TRANSACTION_START_ROW = 'A40';
    const TRANSACTION_HEADER_CELL_RANGE = 'A30:G30';
    const TRANSACTION_CELL_FILL_COLOR = 'b19cd9';


    function getStatement()
    {
        $statementData = $this->accountStatementData();
        $spreadsheet = $this->createTableView($statementData);

        $writer = new Xlsx($spreadsheet);
        $writer->save('/Users/anubhavshrivastava/code/api/storage/files/filestore/lol.xlsx');
        return 'Excel File Written';
    }

    protected function createTableView($statementDate): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->addLogo($sheet);
        $this->addHeaders($sheet);
        $this->addBasicData($sheet);
        $this->addTransactionTitle($sheet);
        $this->addTransactions($sheet);
        $this->addStyling($sheet);

        return $spreadsheet;
    }

    protected function addHeaders(&$sheet)
    {
        foreach (self::HEADERS as $header => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $header);
        }
    }

    protected function addBasicData(&$sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];
        foreach (self::DATA_COLUMNS as $dataPoint => $columnNumber)
        {
            $sheet->setCellValue($columnNumber, $basicInfo[$dataPoint]);
        }
    }

    protected function addTransactionTitle(&$sheet)
    {
        $basicInfo = $this->data[AccountStatementData::ACCOUNT_OWNER_INFO];
        # Ex: Transactions List - INTERNETBA (INR) - 409000000083
        $transactionTitle = 'Transactions List - ' .
            "{$basicInfo[AccountOwnerInfo::ACCOUNT_NAME]} ({$basicInfo[AccountOwnerInfo::CURRENCY]})" .
            "{$basicInfo[AccountOwnerInfo::ACCOUNT_NUMBER]}";
        $sheet->setCellValue(self::TRANSACTION_TITLE_CELL, $transactionTitle);
    }


    protected function addLogo(&$sheet)
    {
        $sheet->mergeCells(self::LOGO_CELL_RANGE);
        $sheet->setCellValue(self::LOGO_CELL, 'Here there will be a logo');
        # have to add an image here
    }

    protected function addTransactions(&$sheet)
    {
        # loop over the statements and put in the transactions
        $transactions = $this->data[AccountStatementData::TRANSACTIONS];
        foreach ($transactions as $lineItem)
        {
            $x = 1;
        }
    }

    protected function addStyling(&$sheet)
    {
        # make all the Basic Info columns as bold
        $dataColumns = array_values(self::DATA_COLUMNS);
        foreach ($dataColumns as $column)
        {
            $sheet->getStyle($column)->getFont()->setBold(1);
        }

        # merge the 5 cells for logo
        $sheet->mergeCells(self::LOGO_CELL_RANGE);

        # merge the 5 cells for header
        $sheet->mergeCells(self::HEADER_CELL_RANGE);

        # make header bold
        $sheet->getStyle(self::HEADERS[XLSXHeaders::HEADER])->getFont()->setBold(1);

        # make sub-header bold
        $sheet->getStyle(self::HEADERS[XLSXHeaders::SUB_HEADER])->getFont()->setBold(1);

        # make transaction title bold
        $sheet->getStyle(self::TRANSACTION_TITLE_CELL)->getFont()->setBold(1);

        # make the default width of all the columns a bit wider
        foreach (self::WORKING_COLUMN_LIST as $col)
        {
            $sheet->getColumnDimension($col)->setWidth(30);
        }

        # give light-blue fill color to the transaction header
        $sheet->getStyle(self::TRANSACTION_HEADER_CELL_RANGE)->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB(self::TRANSACTION_CELL_FILL_COLOR);

        # give all the cells of the transaction table blue border

    }

}




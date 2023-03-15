<?php

namespace RZP\Gateway\Mozart\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\WalletPhonepe;
use RZP\Gateway\Mozart\NetbankingScb;
use RZP\Gateway\Mozart\NetbankingSib;
use RZP\Gateway\Mozart\NetbankingCbi;
use RZP\Gateway\Mozart\NetbankingYesb;
use RZP\Gateway\Mozart\NetbankingKvb;
use RZP\Gateway\Mozart\NetbankingIbk;
use RZP\Gateway\Mozart\NetbankingCub;
use RZP\Gateway\Mozart\NetbankingJsb;
use RZP\Models\Payment\Gateway as PaymentGateway;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $payment = $data['payment'];

        if (($this->gateway === PaymentGateway::UPI_JUSPAY) or
            ($this->gateway === PaymentGateway::UPI_YESBANK))
        {
            return;
        }

        $data['mozart'] = $this->repo->mozart->findByPaymentIdAndAction($payment['id'], 'authorize')->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $gateway = $this->gateway;

        $data = $this->$gateway($input);

        return $data;
    }

    protected function netbanking_yesb($input)
    {
        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        for ($i = 0; $i < 4; $i++)
        {
            // Adding array inside array as laravel excel collapse()
            // removes all the empty array as array_merge() cannot
            // merge empty array
            $data[] = [[]];
        }

        $data[] = NetbankingYesb\ReconFields::RECON_FIELDS;

        $data[] = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $col = [
                NetbankingYesb\ReconFields::MERCHANT_CODE      => 'test_merchant',
                NetbankingYesb\ReconFields::CLIENT_CODE        => 'RAZORPAY',
                NetbankingYesb\ReconFields::PAYMENT_ID         => $row['payment']['id'],
                NetbankingYesb\ReconFields::TRANSACTION_DATE   => $date,
                NetbankingYesb\ReconFields::AMOUNT             => $row['payment']['amount'] / 100,
                NetbankingYesb\ReconFields::SERVICE_CHARGES    => '0',
                NetbankingYesb\ReconFields::BANK_REFERENCE_ID  => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
                NetbankingYesb\ReconFields::TRANSACTION_STATUS => NetbankingYesb\Constants::RECON_STATUS_SUCCESS,
            ];

            $this->content($col, 'col_payment_yesb_nb_recon');

            $data[] = $col;
        }

        return $data;
    }

    protected function netbanking_kvb($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        for ($i = 0; $i < 2; $i++)
        {
            $data[] = [];
        }

        $data[] = NetbankingKvb\ReconFields::RECON_FIELDS;

        $count = 1;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-Y');

            $date = strtoupper($date);

            $col = [
                NetbankingKvb\ReconFields::SR_NO                 => $count++,
                NetbankingKvb\ReconFields::MERCHANT_CODE         => 'RAZORPAY',
                NetbankingKvb\ReconFields::TRANSACTION_DATE      => $date,
                NetbankingKvb\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingKvb\ReconFields::ACCOUNT_NUMBER        => '12345',
                NetbankingKvb\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingKvb\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                    $row['mozart']['raw'],
                    'bank_payment_id'),
            ];

            $this->content($col, 'col_payment_kvb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    protected function netbanking_ibk($input)
    {
        $this->fileExtension = FileStore\Format::TXT;
        $this->fileToWriteName = 'RAZORPAY_2019May';
        $data = [];
        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/y');
            $col = [
                NetbankingIbk\ReconFields::PID                  => $row['payment']['id'],
                NetbankingIbk\ReconFields::BILLER_NAME          => 'xyz',
                NetbankingIbk\ReconFields::DATE_TIME            => $date,
                NetbankingIbk\ReconFields::MERCHANT_REF_NO      => $date,
                NetbankingIbk\ReconFields::AMOUNT               => $row['payment']['amount'] / 100,
                NetbankingIbk\ReconFields::CUSTOMER_NO          => $date,
                NetbankingIbk\ReconFields::DATE_BANK            => $date,
                NetbankingIbk\ReconFields::BANK_REF_NO          => $this->fetchFieldFromJsonData($row['mozart']['raw'],'bank_payment_id'),
                NetbankingIbk\ReconFields::JOURNAL_NO           => "900322626",
                NetbankingIbk\ReconFields::PAID_STATUS          => "Y",
            ];
            $this->content($col, 'col_payment_ibk_nb_recon');
            $data[] = $col;
        }
        $formattedData = $this->generateText($data, '^');
        return $formattedData;
    }

    protected function netbanking_sib($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'trn-' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                                $row['payment']['created_at'],
                                Timezone::IST)
                                ->format('d/m/Y');

            $col = [
                NetbankingSib\ReconFields::TRANSACTION_DATE      => $date,
                NetbankingSib\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingSib\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingSib\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
            ];

            $this->content($col, 'col_payment_sib_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }


    protected function netbanking_scb($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'trn-' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                                $row['payment']['created_at'],
                                Timezone::IST)
                                ->format('d/m/Y');

            $col = [
                NetbankingScb\ReconFields::BANK_TRANSACTION_ID          => $this->fetchFieldFromJsonData(
                                                                           $row['mozart']['raw'],
                                                                     'bank_payment_id'),
                NetbankingScb\ReconFields::PAYMENT_ID                   => $row['payment']['id'],
                NetbankingScb\ReconFields::BANK_PAYMENT_ID              => $this->fetchFieldFromJsonData(
                                                                           $row['mozart']['raw'],
                                                                     'bank_payment_id'),
                NetbankingScb\ReconFields::NORTHAKROSS_TRANSACTION_ID   => '1234',
                NetbankingScb\ReconFields::AMOUNT                       => $row['payment']['amount'] / 100,
                NetbankingScb\ReconFields::DATE                         => $date,

            ];

            $this->content($col, 'col_payment_scb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    protected function paylater_icici($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'razorpayreports';

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-m-Y');
            $col = [
                'ITC'       => $row['payment']['id'],
                'PRN'       => $row['payment']['id'],
                'BID'       => 99999,
                'amount'    => $row['payment']['amount'] / 100,
                'Date'      => $date,
            ];

            $this->content($col, 'col_payment_icici_paylater_recon');

            $data[] = $col;
        }

    $formattedData = $this->generateText($data, ',');

    return $formattedData;
    }

    protected function netbanking_cbi($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'DailyRecon-' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('Ymd');

            $col = [
                NetbankingCbi\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingCbi\ReconFields::BANK_REFERENCE_NUMBER =>
                    $this->fetchFieldFromJsonData(
                        $row['mozart']['raw'],
                        'bank_payment_id'),
                NetbankingCbi\ReconFields::AMOUNT                => $row['payment']['amount'] / 100,
                NetbankingCbi\ReconFields::STATUS                => 'Y',
                NetbankingCbi\ReconFields::DATE                  => $date,
                NetbankingCbi\ReconFields::ACCOUNT_NUMBER        => 'HS-123456789',
                NetbankingCbi\ReconFields::ACCOUNT_TYPE          => '01',
            ];

            $this->content($col, 'col_payment_cbi_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '^');

        return $formattedData;
    }

    protected function netbanking_cub($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'RAZORPAY_2019May';

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y');

            $col = [
                NetbankingCub\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingCub\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingCub\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'bank_payment_id'),
                NetbankingCub\ReconFields::PAYMENT_DATE          => $date,
            ];

            $this->content($col, 'col_payment_cub_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, ',');

        return $formattedData;
    }

    protected function bajajfinserv($input)
    {
        $dt = Carbon::now()->format('dM_Y');

        $this->fileToWriteName = 'Payment_MIS_Razorpay_' . $dt;

        $data = [];

        foreach ($input as $row)
        {
            $col = [
                'dealer_id'                         => '567674',
                'type_of_txn'                       => 'Sale',
                'rrn'                               => 'CS008112079346',
                'transaction_date'                  => '21-Mar-20',
                'disbursement_date'                 => '23-Mar-20',
                'amount_financed_rs'                => (string)ceil($row['payment']['amount'] / 100),
                'scheme_desc'                       => 141137,
                'interest_subsidy_rs_including_gst' => 1321,
                'interest_subsidy_gst'              => '0.0826',
                'net_disb_amount_rs'                => 14669,
                'utr_no'                            => 'N083201101515575',
                'asset_serial_numberimei'           => $row['payment']['id']
            ];

            $this->content($col, 'col_payment_bfl_recon');

            if (empty($col) === true)
            {
                continue;
            }

            $data[] = $col;
        }

        return $data;
    }

    protected function hdfc_debit_emi($input)
    {
        $this->fileToWriteName = 'Merchant Reconciliation Report';

        $data = [];

        $i = 1;

        foreach ($input as $row)
        {
            $row = [
                'Sr No'                   => $i++,
                'BankReferenceNumber'     => 'abc123456',
                'Amount'                  => ($row['payment']['amount'] / 100),
                'CustomerName'            => 'John Doe',
                'MerchantReferenceNumber' => $row['payment']['id'],
                'Remarks'                 => 'Payout Done',
            ];

            $this->content($row, 'row_payment_hdfc_dc_emi_recon');

            if (empty($row) === true)
            {
                continue;
            }

            $data[] = $row;
        }

        $refunds = $this->repo->refund->fetch([
            'gateway' => 'hdfc_debit_emi',
        ]);

        foreach ($refunds as $row)
        {
            $row = [
                'Sr No'                   => $i++,
                'BankReferenceNumber'     => 'abc123456',
                'Amount'                  => ($row['amount'] / 100),
                'CustomerName'            => 'John Doe',
                'MerchantReferenceNumber' => $row['payment_id'],
                'Remarks'                 => 'Cancellation',
            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function upi_juspay($input)
    {
        $this->fileToWriteName = 'upi_juspay_mis';

        $this->fileExtension = FileStore\Format::TXT;

        $data = [];

        // Check refunds first
        $refunds = $this->repo->refund->fetch([
            'gateway' => 'upi_juspay',
        ]);

        foreach ($refunds as $row)
        {
            $row = [
                'RRN'                   => '007516641634',
                'REFUNDID'              => $row['id'],
                'TXNID'                 => 'BJJdcf478fff4b9a8ae78fb40b3384c2d01',
                'ORDER_ID'              => $row['payment_id'],
                'AMOUNT'                => ($row['amount'] / 100),
                'MOBILE_NO'             => '',
                'VPA'                   => 'xyz@abfspay',
                'BANKNAME'              => '',
                'FLAG'                  => '',
                'ACCOUNTNUMBER'         => '',
                'IFSC'                  => '',
                'ACNT_CUSTNAME'         => 'ROSS GELLER',
                'RESPCODE'              => '00',
                'RESPONSE'              => 'Refund accepted successfully',
                'TRANSACTION_DATE'      => '15-03-20 16=>27',
                'REFUND_AMOUNT'         => ($row['amount'] / 100),
                'TXN_REF_DATE'          => '30-03-20 11=>12',
                'MERCHANT_ID'           => 'BAJAJBILLPAYMENTS',
                'CREDITVPA'             => 'billpayments@abfspay',
                'REFUND_TYPE'           => '',
                'UNQ_CUST_ID'           => '',
                'ONLINE_REFUND_REFID'   => ''
            ];

            $data[] = $row;
        }

        // Put payment rows only if refunds are not there.
        // so make it Refund MIS file. Just return the data.
        if (empty($data) === false)
        {
            $formattedData = $this->generateText($data, '|');

            $formattedData = implode("|", array_keys($data[0])) . PHP_EOL . $formattedData;

            return $formattedData;
        }

        // If no refunds, then it is payment MIS file
        // put payment rows in data
        foreach ($input as $row)
        {
            $row = [
                'RRN'                   => '009007125383',
                'TXNID'                 => 'BJJ08df8cc33c68435988aafa54de908913',
                'ORDERID'               => $row['payment']['id'],
                'AMOUNT'                => ($row['payment']['amount'] / 100),
                'MOBILE_NO'             => '',
                'BANKNAME'              => '',
                'MASKEDACCOUNTNUMBER'   => '',
                'IFSC'                  => '',
                'VPA'                   => 'john.miller@juspay',
                'ACCOUNT_CUST_NAME'     => 'JOHN MILLER',
                'RESPCODE'              => '0',
                'RESPONSE'              => 'SUCCESS',
                'TXN_DATE'              => '30-03-2020 07=>45',
                'CREDITVPA'             => 'xyz@random',
                'REMARKS'               => 'Collect Request from Bajaj UPI',
                'SURCHARGE'             => 0,
                'TAX'                   => 0,
                'DEBIT_AMOUNT'          => 2711,
                'MDR_TAX'               => '0',
                'MERCHANT_ID'           => 'BAJAJALLIANZ',
                'UNQ_CUST_ID'           => ''
            ];

            if (empty($row) === true)
            {
                continue;
            }

            $data[] = $row;
        }

        $this->content($data, 'juspay_recon');

        $formattedData = $this->generateText($data, '|');

        $formattedData = implode("|", array_keys($data[0])) . PHP_EOL . $formattedData;

        return $formattedData;
    }

    protected function upi_yesbank($input)
    {
        $this->fileToWriteName = 'upi_yesbank_mis';

        $data = [];

        // Check refunds first
        $refunds = $this->repo->refund->fetch([
            'gateway' => 'upi_yesbank',
        ]);

        foreach ($refunds as $row)
        {
            $row = [
                'PG Merchant ID'         => 'YES0000000000001',
                'Legal Name'             => 'ABC Group PVT LTD',
                'Store Name'             => 'DEF',
                'MCC'                    => '1234',
                'Order No'               => $row['id'],
                'Trans Ref No.'          => '2000000000',
                'Customer Ref No.'       => '25700000000',
                'NPCI Response Code'     => '0',
                'Trans Type'             => 'DEBIT',
                'DR/CR'                  => 'Debit',
                'Transaction Status'     => 'SUCCESS',
                'Transaction Remarks'    => 'MIC2000000000000000007A11111120T081829E0360',
                'Transaction Date'       => '11/9/2020 8:25',
                'Transaction Amount'     => ($row['amount'] / 100),
                'Payer A/c No.'          => '1.00E+15',
                'Payer Virtual Address'  => '1234567890@yesbank',
                'Payer A/C Name'         => 'Pramod Kumar',
                'Payer IFSC Code'        => 'YESB0129700',
                'Payee A/C No'           => '',
                'Payee Virtual Address'  => 'abc@yesbank',
                'Payee A/C Name'         => '',
                'Payee IFSC Code'        => 'YESB0000001',
                'Pay Type'               => 'P2M',
                'Device Type'            => '',
                'App'                    => '',
                'Device OS'              => '',
                'Device Mobile No'       => '4.57E+09',
                'Device Location'        => '',
                'Ip Address'             => '',
                'Settlement Status'      => 'Unreconcilied',
                'Settlement Date'        => '',
                'MSF Amount'             => '0',
                'MSF Tax Amount'         => '0',
                'Payout Status'          => 'Payout Completed'
            ];

            $data[] = $row;
        }

        // Put payment rows only if refunds are not there.
        // so make it Refund MIS file. Just return the data.
        if (empty($data) === false)
        {
            return $data;
        }

        // If no refunds, then it is payment MIS file
        // put payment rows in data
        foreach ($input as $row)
        {
            $row = [
                'PG Merchant ID'         => 'YES0000000000001',
                'Legal Name'             => 'ABC Group PVT LTD',
                'Store Name'             => 'DEF',
                'MCC'                    => '1234',
                'Order No'               => $row['payment']['id'],
                'Trans Ref No.'          => '2000000000',
                'Customer Ref No.'       => '25700000000',
                'NPCI Response Code'     => '0',
                'Trans Type'             => 'CREDIT',
                'DR/CR'                  => 'Credit',
                'Transaction Status'     => 'SUCCESS',
                'Transaction Remarks'    => 'MIC2000000000000000007A11111120T081829E0360',
                'Transaction Date'       => '11/9/2020 8:25',
                'Transaction Amount'     => ($row['payment']['amount'] / 100),
                'Payer A/c No.'          => '1.00E+15',
                'Payer Virtual Address'  => '1234567890@yesbank',
                'Payer A/C Name'         => 'Pramod Kumar',
                'Payer IFSC Code'        => 'YESB0129700',
                'Payee A/C No'           => '',
                'Payee Virtual Address'  => 'abc@yesbank',
                'Payee A/C Name'         => '',
                'Payee IFSC Code'        => 'YESB0000001',
                'Pay Type'               => 'P2M',
                'Device Type'            => '',
                'App'                    => '',
                'Device OS'              => '',
                'Device Mobile No'       => '4.57E+09',
                'Device Location'        => '',
                'Ip Address'             => '',
                'Settlement Status'      => 'Unreconcilied',
                'Settlement Date'        => '',
                'MSF Amount'             => '0',
                'MSF Tax Amount'         => '0',
                'Payout Status'          => 'Payout Completed'
            ];

            if (empty($row) === true)
            {
                continue;
            }

            $data[] = $row;
        }

        $this->content($data, 'yesbank_recon');

        return $data;
    }

    protected function cred($input)
    {
        return;
    }

    protected function upi_airtel($input)
    {
        $this->fileToWriteName = 'TXN_REPORT';

        $data = [];

        // Check refunds first
        $refunds = $this->repo->refund->fetch([
            'gateway' => 'upi_airtel',
        ]);

        foreach ($refunds as $row)
        {
            $row = [
                'SNO'                       => '1',
                'Date and Time'             => '14-08-2020 12:41',
                'Transaction Id'            => 'FT2022712537204137',
                'Customer Mobile No'        => '',
                'Customer Category'         => '',
                'PARTNER_TXN_ID'            => '22712135190',
                'Original Input Amt'        => ($row['amount'] / 100),
                'Commision(DR)'             => '0',
                'Commision(CR)'             => '0',
                'UGST(DR)'                  => '',
                'UGST(CR)'                  => '',
                'IGST(DR)'                  => '',
                'IGST(CR)'                  => '',
                'CGST(DR)'                  => '0',
                'CGST(CR)'                  => '0',
                'SGST(DR)'                  => '0',
                'SGST(CR)'                  => '',
                'TDS(DR)'                   => '0',
                'TDS(CR)'                   => '0',
                'GDS(DR)'                   => '0',
                'GDS(CR)'                   => '0',
                'Net Amount Payable(DR)'    => '',
                'Net Amount Payable(CR)'    => '500',
                'MERCHANT_STATE'            => 'KARNATAKA',
                'COUNTERPARTY_STATE'        => '',
                'Store Id'                  => '',
                'Till ID'                   => $row['id'],
                'REF_TXN_NO_ORG'            => '',
                'Transaction Status'        => 'Refund',
                'Merchant MSISDN'           => '1000012114',
                'Merchant ID'               => '69637659',
                'Merchant Account Number'   => '1045576778',
                'Merchant Name'             => 'RAZORPAY SORTWARE PVT LTD',
                'Merchant Settlement Type'  => '2'
            ];

            $data[] = $row;
        }

        // Put payment rows only if refunds are not there.
        // so make it Refund MIS file. Just return the data.
        if (empty($data) === false)
        {
            return $data;
        }

        // If no refunds, then it is payment MIS file
        // put payment rows in data
        foreach ($input as $row)
        {
            $row = [
                'SNO'                       => '1',
                'Date and Time'             => '14-08-2020 12:41',
                'Transaction Id'            => 'FT2022712537204137',
                'Customer Mobile No'        => '',
                'Customer Category'         => '',
                'PARTNER_TXN_ID'            => '227121351902',
                'Original Input Amt'        => (string)ceil($row['payment']['amount'] / 100),
                'Commision(DR)'             => '0',
                'Commision(CR)'             => '0',
                'UGST(DR)'                  => '',
                'UGST(CR)'                  => '',
                'IGST(DR)'                  => '',
                'IGST(CR)'                  => '',
                'CGST(DR)'                  => '0',
                'CGST(CR)'                  => '0',
                'SGST(DR)'                  => '0',
                'SGST(CR)'                  => '',
                'TDS(DR)'                   => '0',
                'TDS(CR)'                   => '0',
                'GDS(DR)'                   => '0',
                'GDS(CR)'                   => '0',
                'Net Amount Payable(DR)'    => '',
                'Net Amount Payable(CR)'    => '500',
                'MERCHANT_STATE'            => 'KARNATAKA',
                'COUNTERPARTY_STATE'        => '',
                'Store Id'                  => '',
                'Till ID'                   => $row['payment']['id'],
                'REF_TXN_NO_ORG'            => '',
                'Transaction Status'        => 'Misc Cr',
                'Merchant MSISDN'           => '1000012114',
                'Merchant ID'               => '69637659',
                'Merchant Account Number'   => '1045576778',
                'Merchant Name'             => 'RAZORPAY SORTWARE PVT LTD',
                'Merchant Settlement Type'  => '2'
            ];

            if (empty($row) === true)
            {
                continue;
            }

            $this->content($row, 'airtel_recon');

            $data[] = $row;

        }

        return $data;
    }

    protected function wallet_phonepe($input)
    {
        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-m-Y');

            $col = [
                WalletPhonepe\ReconFields::PAYMENT_TYPE         => 'PAYMENT',
                WalletPhonepe\ReconFields::RZP_ID               => $row['payment']['id'],
                WalletPhonepe\ReconFields::ORDER_ID             => $row['payment']['id'],
                WalletPhonepe\ReconFields::PHONEPE_ID           => $this->fetchFieldFromJsonData(
                                                                            $row['mozart']['raw'],
                                                                            'providerReferenceId'),
                WalletPhonepe\ReconFields::FROM                 => $date,
                WalletPhonepe\ReconFields::CREATION_DATE        => $date,
                WalletPhonepe\ReconFields::TRANSACTION_DATE     => $date,
                WalletPhonepe\ReconFields::SETTLEMENT_DATE      => $date,
                WalletPhonepe\ReconFields::BANK_REFERENCE_NO    => 'N0000012345',
                WalletPhonepe\ReconFields::AMOUNT               => $row['payment']['amount']/100,
                WalletPhonepe\ReconFields::FEE                  => '0',
                WalletPhonepe\ReconFields::IGST                 => '0',
                WalletPhonepe\ReconFields::CGST                 => '0',
                WalletPhonepe\ReconFields::SGST                 => '0',
            ];

            $this->content($col, 'col_payment_wallet_phonepe_recon');

            $data[] = $col;
        }

        return $data;
    }
    protected function wallet_phonepeswitch($input)
    {
        $this->fileExtension = FileStore\Format::CSV;

        $this->fileToWriteName = 'Recon_' . Carbon::now(Timezone::IST)->format('dmY');

        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-m-Y');

            $col = [
                WalletPhonepe\ReconFields::PAYMENT_TYPE         => 'PAYMENT',
                WalletPhonepe\ReconFields::RZP_ID               => $row['payment']['id'],
                WalletPhonepe\ReconFields::ORDER_ID             => $row['payment']['id'],
                WalletPhonepe\ReconFields::PHONEPE_ID           => $this->fetchFieldFromJsonData(
                    $row['mozart']['raw'],
                    'providerReferenceId'),
                WalletPhonepe\ReconFields::FROM                 => $date,
                WalletPhonepe\ReconFields::CREATION_DATE        => $date,
                WalletPhonepe\ReconFields::TRANSACTION_DATE     => $date,
                WalletPhonepe\ReconFields::SETTLEMENT_DATE      => $date,
                WalletPhonepe\ReconFields::BANK_REFERENCE_NO    => 'N0000012345',
                WalletPhonepe\ReconFields::AMOUNT               => $row['payment']['amount']/100,
                WalletPhonepe\ReconFields::FEE                  => '0',
                WalletPhonepe\ReconFields::IGST                 => '0',
                WalletPhonepe\ReconFields::CGST                 => '0',
                WalletPhonepe\ReconFields::SGST                 => '0',
            ];

            $this->content($col, 'col_payment_wallet_phonepeswitch_recon');

            $data[] = $col;
        }

        return $data;
    }

    protected function netbanking_jsb($input)
    {
        $this->fileExtension = FileStore\Format::TXT;

        $this->fileToWriteName = 'Payment' . Carbon::now(Timezone::IST)->format('Ymdis');

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('Y-M-d H:i:s');

            $date = strtoupper($date);

            $status = 'Failed';

            if(($row['payment']['status']) === 'authorized')
            {
                $status = 'Success';
            }

            $col = [
                NetbankingJsb\ReconFields::PAYMENT_ID            => $row['payment']['id'],
                NetbankingJsb\ReconFields::BANK_REFERENCE_NUMBER => $this->fetchFieldFromJsonData($row['mozart']['raw'], 'bank_payment_id'),
                NetbankingJsb\ReconFields::CURRENCY              => 'INR',
                NetbankingJsb\ReconFields::PAYMENT_AMOUNT        => $row['payment']['amount'] / 100,
                NetbankingJsb\ReconFields::STATUS                => $status,
                NetbankingJsb\ReconFields::TRANSACTION_DATE      => $date,
                NetbankingJsb\ReconFields::MERCHANT_CODE         => $row['payment']['merchant_id'],
                NetbankingJsb\ClaimFields::MERCHANT_NAME         => 'RAZORPAY',
            ];

            $this->content($col, 'col_payment_jsb_nb_recon');

            $data[] = $col;
        }

        $formattedData = $this->generateText($data, '|');

        return $formattedData;
    }

    public function generateReconciliation(array $input)
    {
        $this->gateway = $input['gateway'];

        return parent::generateReconciliation($input);
    }

    protected function fetchFieldFromJsonData($data, $field)
    {
        $dataArray = json_decode($data, true);

        return $dataArray[$field];
    }

    //Overriding this base class create file as it creates and excel file
    protected function createFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        if (($this->gateway === PaymentGateway::NETBANKING_SIB) ||
            ($this->gateway === PaymentGateway::UPI_JUSPAY))
        {
            return $this->createTxtFile($content, $type, $store);
        }
        else
        {
             return parent::createFile($content, $type, $store);
        }
    }

    protected function createTxtFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($this->fileExtension)
                ->content($content)
                ->name($this->fileToWriteName)
                ->store($store)
                ->type($type)
                ->save();

        return $creator;
    }

    protected function getEntitiesToReconcile()
    {
        return $this->repo
                    ->payment
                    ->fetch(['gateway' => $this->gateway]);
    }
}

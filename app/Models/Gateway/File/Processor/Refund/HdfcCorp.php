<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Reconciliator\NetbankingHdfcCorp\Constants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class HdfcCorp extends Base
{
    use FileHandler;

    const FILE_NAME              = 'HDFC_Refund';
    const EXTENSION              = FileStore\Format::XLS;
    const FILE_TYPE              = FileStore\Type::HDFC_CORP_NETBANKING_REFUNDS;
    const GATEWAY                = Payment\Gateway::NETBANKING_HDFC;
    const GATEWAY_CODE           = 'HDFC_C';
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    protected $type              = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'HDFC_Corp/Refund/Netbanking/';
    /**
     * @var string
     */
    private $refundDate;

    public function __construct()
    {
        parent::__construct();

        $this->refundDate = Carbon::now(Timezone::IST)->format('d M Y');
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = $this->getFileHeadersData();

        $count = 1;
        foreach ($data as $row)
        {
            $refundDate = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
                Timezone::IST)
                ->format('d/m/Y H:i:s');

            $paymentDate = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d/m/Y H:i:s');

            $fields  = [
                'Sr.No'                 => $count++,
                'Refund Id'             => $row['refund']['id'],
                'Bank Id'               => 'CH2',
                'Merchant Name'         => $row['merchant']['name'],
                'Txn Date'              => $paymentDate,
                'Refund Date'           => $refundDate,
                'Bank Merchant Code'    => $row['terminal']['gateway_merchant_id'],
                'Bank Ref No.'          => $row['gateway']['bank_transaction_id'],
                'PGI Reference No.'     => $row['payment']['id'],
                'Txn Amount (Rs Ps)'    => $this->getFormattedAmount($row['payment']['amount']),
                'Refund Amount (Rs Ps)' => $this->getFormattedAmount($row['refund']['amount']),
                'Bank Account No.'      => 'NA',
                'Bank Pay Type'         => 'CITNEFT',
                'PGI Bank Id'           => 'PGI Bank Id',
                'Txn Currency Code'     => 'INR',
            ];

            $formattedData[] = $fields;

        }

        return $formattedData;
    }

    /**
     * Fetches required data to be sent as part of the mail to HDFC
     */
    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => basename($file->getLocation()),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        return self::BASE_STORAGE_DIRECTORY . self::FILE_NAME . '_' . $this->refundDate;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getAccountNo($row)
    {
        return $row['gateway'][Netbanking::BANK_ACCOUNT_NUMBER];
    }

    protected function getFileHeadersData()
    {
        $todayDate = Carbon::now(Timezone::IST)->format('d M Y');

        $formattedData[] = ['RAZORPAY PAYMENT GATEWAY',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = [
            'Daily Refund Report',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = [
            'Date',
            $todayDate,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = [];

        $formattedData[] = [
            'HDFC Bank Corporate',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',];

        $formattedData[] = Constants::REFUND_FILE_HEADERS;

        return $formattedData;
    }

}

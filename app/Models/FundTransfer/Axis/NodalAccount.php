<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Carbon\Carbon;
use PHPExcel_Shared_Date;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

use RZP\Models\FundTransfer\Base as NodalBase;
use RZP\Encryption\AESEncryption;
use phpseclib\Crypt\AES;
use RZP\Mail\Settlement\AxisSettlement;
use RZP\Models\FundTransfer\Mode;

class NodalAccount extends NodalBase\NodalAccount
{
    const SIGNED_URL_DURATION = '1440';

    // To be changed
    const IV = 'aai_wee';
    const SECRET = 'kissi_ko_pata_nhi_chalega';

    const HEADINGS = [
        'Record Identifier',
        'Reference Number',
        'Debit Account',
        'Amount',
        'Transaction',
        'Cr Date',
    ];

    const MODE_MAPPING = [
        Mode::NEFT    => 'N',
        Mode::RTGS    => 'R',
        Mode::IMPS    => 'I',
    ];

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $id = null;

    public function __construct()
    {
        parent::__construct();

        $this->date = Carbon::today(Timezone::IST)->format('n/j/y');

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    public function generateTransferFile(string $amount): array
    {
        $rows = $this->getRows($amount);

        $fileData = $this->createFile($rows);

        $this->sendAxisTransferMail($fileData);

        return ['file' => $fileData['file_path']];
    }

    protected function createFile(array $values): array
    {
        $fileName = 'axis/outgoing/' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($values)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->columnFormat(['C3' => 'dd/mm/yy', 'E3' => 'dd/mm/yy', 'F3' => 'dd/mm/yy'])
                        ->encrypt(Type::AES_ENCRYPTION, [
                            AESEncryption::MODE   => AES::MODE_CBC,
                            AESEncryption::IV     => self::IV,
                            AESEncryption::SECRET => self::SECRET,])
                        ->save();

        $fileInstance = $file->get();

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $fileInstance['local_file_path'],
            'file_name'  => basename($fileInstance['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData;
    }

    protected function getH2HMetadata()
    {
        // To be changed perhaps
        return [
            'gid'   => '10000',
            'uid'   => '10003',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }

    protected function getRows(string $amount): array
    {
        $mode = $this->getTransferMode($amount);

        $this->mode = self::MODE_MAPPING[$mode];

        $formattedAmount = (float) sprintf('%0.2f', $amount);

        // Record Identifier is set as 'D' for Axis bank always in first row
        $headerValues = [
            'D',
            $this->id,
            917020041206002,
            $formattedAmount,
            1,
            '',
        ];

        // Mode is set as I for Axis bank always in non-header rows
        $excelDate = PHPExcel_Shared_Date::PHPToExcel(strtotime($this->date));

        $transactionValues = [
            $this->mode,
            'RZRNAXISCARD',
            $excelDate,
            $formattedAmount,
            $excelDate,
            $excelDate,
        ];

        $values = [self::HEADINGS, $headerValues, $transactionValues];

        return $values;
    }

    protected function sendAxisTransferMail(array $fileData)
    {
        $data['body'] = json_encode($this->data, JSON_PRETTY_PRINT);

        $data['file_data'] = $fileData;

        $axisSettlementMail = new AxisSettlement($data);

        Mail::queue($axisSettlementMail);
    }
}

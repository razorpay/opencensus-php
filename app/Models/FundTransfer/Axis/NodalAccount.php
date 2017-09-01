<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Settlement\AxisSettlement;

class NodalAccount extends Base\Core
{
    const SIGNED_URL_DURATION = '1440';

    const HEADINGS = [
        'Record Identifier',
        'Reference Number',
        'Debit Account',
        'Amount',
        'Transaction',
        'Cr Date',
    ];

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $id = null;

    public function __construct()
    {
        parent::__construct();

        $this->date = Carbon::today(Timezone::IST)->format('m-d-Y');

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    public function generateTransferFile(string $amount): array
    {
        $rows = $this->getRows($amount);

        $fileData = $this->createFile($rows);

        // $this->sendAxisTransferMail($fileData);

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
                        ->id($this->id)
                        ->metadata($metadata)
                        ->headers(false)
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
        // Deciding on based of amount to choose mode as R or N
        $mode = ($amount >= 200000) ? 'R' : 'N';

        $formattedAmount = sprintf('%0.2f', $amount);

        // Record Identifier is set as 'D' for Axis bank always in first row
        $headerValues = [
            'D',
            $this->id,
            '917020041206002',
            $formattedAmount,
            '1',
            '',
        ];

        // Mode is set as I for Axis bank always in non-header rows
        $transactionValues = [
            $mode,
            'RZRNAXISCARD',
            $this->date,
            $formattedAmount,
            $this->date,
            $this->date,
        ];

        $values = [self::HEADINGS, $headerValues, $transactionValues,];

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

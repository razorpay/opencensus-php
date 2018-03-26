<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Config;
use Carbon\Carbon;
use phpseclib\Crypt\AES;
use PHPExcel_Shared_Date;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;
use RZP\Encryption\AESEncryption;
use RZP\Mail\Settlement\AxisSettlement;
use RZP\Models\FundTransfer\Mode;

class NodalAccount extends NodalBase\FileProcessor
{
    const SIGNED_URL_DURATION = '1440';

    const MODE_MAPPING = [
        Mode::NEFT    => 'N',
        Mode::RTGS    => 'R',
        Mode::IMPS    => 'M',
        Mode::IFT     => 'I',
    ];

    protected $secret = null;

    protected $iv = null;

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $id = null;

    public function __construct()
    {
        parent::__construct();

        $this->date = Carbon::today(Timezone::IST)->format('n/j/y');

        $this->id = Base\UniqueIdEntity::generateUniqueId();

        $this->secret = Config::get('nodal.axis.secret');

        $this->iv = base64_decode(Config::get('nodal.axis.iv'));
    }

    public function generateFundTransferFile($entities, $h2h = true): FileStore\Creator
    {
        $rows = $this->getRows($entities);

        list($excelFile, $rzpFile) = $this->createFile($rows);

        $fileData = $this->getFileData($rzpFile);

        $this->sendAxisTransferMail($fileData);

        return $excelFile;
    }

    protected function createFile(array $values): array
    {
        $fileName = 'axis/outgoing/' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $rowCount = count($values);

        //
        // We need to set column format of columns C, E and F
        // from 3rd row onwards – these rows represent
        // transaction details
        //
        $colFormat = [
            'C3:C' . $rowCount => 'dd/mm/yy',
            'E3:E' . $rowCount => 'dd/mm/yy',
            'F3:F' . $rowCount => 'dd/mm/yy',
        ];

        $rzpFile = $creator->extension(FileStore\Format::XLSX)
                           ->content($values)
                           ->name($fileName)
                           ->store(FileStore\Store::S3)
                           ->type(FileStore\Type::FUND_TRANSFER_DEFAULT)
                           ->metadata($metadata)
                           ->headers(false)
                           ->columnFormat($colFormat)
                           ->save();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($values)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->columnFormat($colFormat)
                        ->encrypt(Type::AES_ENCRYPTION, [
                            AESEncryption::MODE   => AES::MODE_CBC,
                            AESEncryption::IV     => $this->iv,
                            AESEncryption::SECRET => $this->secret,])
                        ->encode()
                        ->save();

        return [$file, $rzpFile];
    }

    protected function getFileData($file)
    {
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

    protected function getRows($entities): array
    {
        $totalAmount = 0;

        foreach ($entities as $entity)
        {
            $source = $entity->source;

            $amount = ($source->getAmount() / 100);

            $totalAmount += $amount;

            $ba = $entity->bankAccount;

            $beneCode = $entity->bankAccount->getId();

            // currently kotak is registered with below benecode, so we override
            // the benecode until the new one gets registered.
            if ($beneCode === '9KnioczXfED3wz')
            {
                $beneCode = 'RZRNAXISCARD';
            }

            $rows[] = $this->getTrasactionRow($amount, $beneCode, $ba, $entity);
        }

        $count = count($entities);

        $formattedAmount = (float) sprintf('%0.2f', $totalAmount);

        $headerValues = $this->getHeaderRow($formattedAmount, $count);

        $headings     = Headings::getRequestFileHeadings();

        $values = [$headings, $headerValues];

        $values = array_merge($values, $rows);

        return $values;
    }

    protected function getTrasactionRow($amount, $accountId, BankAccount\Entity $ba, Base\Entity $entity): array
    {
        $mode = $this->getPaymentType($amount, $ba);

        $mode = self::MODE_MAPPING[$mode];

        $formattedAmount = (float) sprintf('%0.2f', $amount);

        $settlementId = $entity->source->getId();

        $attemptId    = $entity->getId();

        // Mode is set as I for Axis bank always in non-header rows
        $excelDate = PHPExcel_Shared_Date::PHPToExcel(strtotime($this->date));

        $transactionValues = [
            $mode,
            $accountId,
            $excelDate,
            $formattedAmount,
            $excelDate,
            $excelDate,
            $attemptId,
            $settlementId
        ];

        return $transactionValues;
    }

    protected function getPaymentType($amount, BankAccount\Entity $ba)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === 'UTIB')
        {
            return Mode::IFT;
        }

        $mode = $this->getTransferMode($amount);

        return $mode;
    }

    protected function getHeaderRow($formattedAmount, $count): array
    {
        // Record Identifier is set as 'D' for Axis bank always in first row
        $headerValues = [
            'D',
            $this->id,
            917020041206002,
            $formattedAmount,
            $count,
            '',
        ];

        return $headerValues;
    }

    protected function sendAxisTransferMail(array $fileData)
    {
        $data['body'] = json_encode($this->data, JSON_PRETTY_PRINT);

        $data['file_data'] = $fileData;

        $axisSettlementMail = new AxisSettlement($data);

        Mail::queue($axisSettlementMail);
    }
}

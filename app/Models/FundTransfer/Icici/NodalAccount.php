<?php

namespace RZP\Models\FundTransfer\Icici;

use Mail;
use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Mode;
use RZP\Encryption\AESEncryption;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Base as NodalBase;

class NodalAccount extends NodalBase\NodalAccount
{
    // used in icici AES encrypter tool
    const ENCRYPTION_KEY = "1836204826394167";

    const SIGNED_URL_DURATION = '1440';

    const DEBIT_ACCOUNT_NO = '000205025290';

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

        $this->date = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->id = Base\UniqueIdEntity::generateUniqueId();
    }

    public function generateSettlementFile($entities, $h2h = true): array
    {
        $rows = $this->getSettlementRows($entities);

        $txt = $this->getTxtFromRows($rows);

        $file = $this->createFile($txt);

        $fileData = $this->getFileData($file);

        $this->sendIciciTransferMail($fileData);

        return [$file, $file];
    }

    protected function getTxtFromRows(array $rows): string
    {
        $txt = '';

        $totalElements = count($rows);

        foreach ($rows as $index => $row)
        {
            $txt .= implode(',', $row);

            // Don't add newline for the last line
            if ($index < $totalElements - 1)
            {
                //
                // Double quote is required to suggest new line
                // Single quote will NOT work
                //
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    protected function getSettlementRows(Base\PublicCollection $entities): array
    {
        $rows = [];

        foreach ($entities as $entity)
        {
            $amount = $entity->source->getAmount() / 100;

            $ba = $entity->bankAccount;

            $mode = $this->getPaymentType($ba, $amount);

            $mode = self::MODE_MAPPING[$mode];

            $rows[] = [
                Headings::PAYMENT_MODE              => $mode,
                Headings::BENEFICIARY_NAME          => $ba->getBeneficiaryName(),
                Headings::BENEFICIARY_ACCOUNT_NO    => $ba->getAccountNumber(),
                Headings::BENEFICIARY_IFSC          => $ba->getIfscCode(),
                Headings::AMOUNT                    => $this->formatAmount($amount),
                Headings::PAYMENT_DATE              => $this->date,
                Headings::DEBIT_ACCOUNT_NO          => self::DEBIT_ACCOUNT_NO,
                Headings::CREDIT_NARRATION          => 'RAZORPAY SETTLEMENT',
                Headings::INSTRUMENT_REFERENCE      => $entity->getId(),
                Headings::DUMMY                     => '',
                Headings::DUMMY2                    => '',
                Headings::BENEFICIARY_CODE          => $ba->getId(),
            ];
        }

        return $rows;
    }

    protected function getPaymentType(BankAccount\Entity $ba, $amount)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === 'ICIC')
        {
            return Mode::IFT;
        }

        $mode = $this->getTransferMode($amount);

        return $mode;
    }

    protected function createFile($txt): FileStore\Creator
    {
        $fileName = 'icici/outgoing/NRPSS_NRPSSUPLDNEW_' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::ENC)
                        ->content($txt)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->id($this->id)
                        ->headers(false)
                        ->metadata($metadata)
                        ->encrypt(
                            Type::AES_ENCRYPTION,
                            [
                                AESEncryption::MODE   => AES::MODE_ECB,
                                AESEncryption::SECRET => self::ENCRYPTION_KEY
                            ])
                        ->save();

        return $file;
    }

    protected function getFileData(FileStore\Creator $file): array
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
        return [
            'gid'   => '10000',
            'uid'   => '10002',
            'mtime' => Carbon::now()->getTimestamp(),
            'mode'  => '33188'
        ];
    }

    protected function sendIciciTransferMail(array $fileData, array $rows = null)
    {
        $data['body'] = 'PFA ICICI Settlement file';
        
        if ($rows !== null)
        {
            $data['body'] = json_encode($rows, JSON_PRETTY_PRINT);
        }

        $data['file_data'] = $fileData;

        $iciciSettlementMail = new SettlementMail\IciciSettlement($data);

        Mail::queue($iciciSettlementMail);
    }

    protected function formatAmount($amount)
    {
        return sprintf('%0.2f', $amount);
    }
}

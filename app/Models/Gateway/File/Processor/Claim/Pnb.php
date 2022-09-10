<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Encryption\PGPEncryption;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Netbanking\Pnb\ClaimFields;

class Pnb extends NetbankingBase
{
    const FILE_NAME              = 'PNB_CLAIMS_';
    const EXTENSION              = FileStore\Format::XLSX;
    const FILE_TYPE              = FileStore\Type::PNB_NETBANKING_CLAIMS;
    const GATEWAY                = Payment\Gateway::NETBANKING_PNB;
    const BASE_STORAGE_DIRECTORY = 'Pnb/Claims/Netbanking/';

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $config = $this->config['gateway.netbanking_pnb'];

            $pgpConfig = [
                PGPEncryption::PUBLIC_KEY  => trim(str_replace('\n', "\n", $config['recon_key'])),
                PGPEncryption::PASSPHRASE  => $config['recon_passphrase'],
            ];

            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile)
                ->encrypt(Encryption\Type::PGP_ENCRYPTION, $pgpConfig)
                ->save();

            $file = $creator->getFileInstance();

            $creator->name($fileName.'.xlsx')
                 ->extension(FileStore\Format::GPG)
                 ->save();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end   = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment
                ->fetchReconciledPaymentsForGatewayUsingReportingReplica($begin,
                $end,
                static::GATEWAY,
                $statuses);
        return $claims;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $formattedData[] = [
                ClaimFields::BANK_PAYMENT_ID    => $this->fetchBankPaymentId($row),
                ClaimFields::AMOUNT             => $this->getFormattedAmount($row['payment']['amount']),
                ClaimFields::DATE               => $this->fetchDate($row),
                ClaimFields::PAYMENT_ID         => $row['payment']['id'],
                ClaimFields::PID                => $row['terminal']['gateway_merchant_id'],
                ClaimFields::ACCOUNT_NO         => $this->fetchBankAccountNumber($row),
                ClaimFields::STATUS             => 'successful',
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return static::BASE_STORAGE_DIRECTORY . static::FILE_NAME . $dateTime;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway'][Netbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }

    protected function fetchBankAccountNumber($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway'][Netbanking::BANK_ACCOUNT_NUMBER]; // payment through nbplus service
        }

        return $data['gateway']['account_number'];
    }

    protected function fetchDate($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['payment']['created_at']; // payment through nbplus service
        }

        return $data['gateway']['date'];
    }
}

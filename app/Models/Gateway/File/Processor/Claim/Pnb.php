<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Encryption;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Encryption\PGPEncryption;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Netbanking\Pnb\ClaimFields;

class Pnb extends Base
{
    const FILE_NAME = 'PNB_CLAIMS_';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::PNB_NETBANKING_CLAIMS;
    const GATEWAY   = Payment\Gateway::NETBANKING_PNB;

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
                ClaimFields::BANK_PAYMENT_ID    => $row['gateway']['bank_payment_id'],
                ClaimFields::AMOUNT             => $this->getFormattedAmount($row['payment']['amount']),
                ClaimFields::DATE               => $row['gateway']['date'],
                ClaimFields::PAYMENT_ID         => $row['payment']['id'],
                ClaimFields::PID                => $row['terminal']['gateway_merchant_id'],
                ClaimFields::ACCOUNT_NO         => $row['gateway']['account_number'],
                ClaimFields::STATUS             => 'successful',
            ];
        }

        return $formattedData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $dateTime = Carbon::now(Timezone::IST)->format('YdmHis');

        return static::FILE_NAME . $dateTime;
    }

    protected function getFormattedAmount($amount): String
    {
        return number_format($amount / 100, 2, '.', '');
    }
}

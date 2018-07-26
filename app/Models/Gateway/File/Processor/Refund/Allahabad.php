<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Constants\Mode as RZPMode;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;

class Allahabad extends Base
{
    use FileHandler;

    const FILE_NAME                   = 'Allahabad_REFUND';
    const EXTENSION                   = FileStore\Format::TXT;
    const FILE_TYPE                   = FileStore\Type::ALLAHABAD_NETBANKING_REFUND;
    const GATEWAY                     = Payment\Gateway::NETBANKING_ALLAHABAD;
    const GATEWAY_CODE                = IFSC::ALLA;
    const PAYMENT_TYPE_ATTRIBUTE      = Payment\Entity::BANK;

    protected $config;

    const PAYEE_ID   = 'Razor';
    const BANK_CODE  = '027';

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $this->loadGatewayConfig();

        foreach ($data as $index => $row)
        {
            $txnDate = $this->createDateFormat($row['payment']['created_at']);

            $refundDate = $this->createDateFormat($row['refund']['created_at']);

            $formattedData[] = [
                'PID'                   => self::PAYEE_ID,
                'Bank Id'               => self::BANK_CODE,
                'Merchant Name'         => self::PAYEE_ID,
                'Txn Date'              => $txnDate,
                'Refund Date'           => $refundDate,
                'Bank Merchant Code'    => $this->getMerchantId($row[ConstantsEntity::TERMINAL]),
                'Bank Ref No.'          => $row['gateway']['bank_payment_id'],
                'PGI Reference No.'     => $row['payment']['id'],
                'Txn Amount'            => $this->formatAmount($row['payment']['amount'] / 100),
                'Refund'                => $this->formatAmount($row['refund']['amount'] / 100),
            ];
        }

        $formattedData = $this->getTextData($formattedData);

        return $formattedData;
    }

    protected function getTextData($data)
    {
        $txt  = $this->generateText($data,'|',true);

        return $txt;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => $file->getLocation(),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return self::FILE_NAME.'_'.$time;
    }

    public function formatAmount($amount): string
    {
        return number_format($amount , 2, '.', '');
    }

    protected function loadGatewayConfig()
    {
        $configGatewayStr = 'gateway.' . self::GATEWAY;

        $this->config = $this->app['config']->get($configGatewayStr);
    }

    protected function getMerchantId($terminal): string
    {
        $merchantId = $this->config['test_merchant_id'];

        if ($this->mode === RZPMode::LIVE)
        {
            $merchantId = $terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];
        }

        return $merchantId;
    }

    protected function createDateFormat($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
                            ->format('d/m/Y');
    }
}

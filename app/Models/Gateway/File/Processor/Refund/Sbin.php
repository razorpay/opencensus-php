<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Constants\Mode as RZPMode;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Sbin extends Base
{
    use FileHandler;

    const FILE_NAME              = 'SBI_REFUND';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::SBI_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_SBI;
    const GATEWAY_CODE           = IFSC::SBIN;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    const BANK_CODE              = 'sbin';
    const DATE_FORMAT            = 'dmy';

    protected $config;

    protected static $headers = [
        'Tnx Code',
        'Txn Date',
        'Refund Date',
        'Bank Ref No.',
        'Txn Amount',
        'Refund Amount',
    ];

    protected function formatDataForFile(array $data)
    {
        $this->loadGatewayConfig();

        $content = [];

        $srNo = 1;

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row[ConstantsEntity::PAYMENT][PaymentEntity::CREATED_AT],
                Timezone::IST)
                ->format(self::DATE_FORMAT);

            $refundDate = Carbon::createFromTimestamp(
                $row[ConstantsEntity::REFUND][RefundEntity::CREATED_AT],
                Timezone::IST)
                ->format(self::DATE_FORMAT);

            $paymentId = $row[ConstantsEntity::PAYMENT][PaymentEntity::ID];

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
                Action::AUTHORIZE);

            $content[] = [
                'Tnx Code(99)'        => $srNo++,
                'Txn Date(YYMMDD)'    => $date,
                'Refund Date(YYMMDD)' => $refundDate,
                'Ban REf No.'         => $netbanking[NetbankingEntity::BANK_PAYMENT_ID],
                'Txn Amount'          => $row[ConstantsEntity::PAYMENT][PaymentEntity::AMOUNT] / 100,
                'Refund Amount'       => $row[ConstantsEntity::REFUND][RefundEntity::AMOUNT] / 100,
            ];
        }

        $initialLine = $this->getInitialLine();

        $content = $this->getTextData($content, $initialLine);

        return $content;
    }

    protected function getTextData($data, $initialLine)
    {
        $txt  = $this->generateText($data,'|',true);

        $txt = $initialLine . $txt;

        return $txt;
    }

    protected function getInitialLine()
    {
        $data = self::$headers;

        $line = implode('|', $data) . "\r\n";

        return $line;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME . '_' . $date;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $totalAmount = array_reduce(
            $data,
            function(int $carry, array $item)
            {
                $carry += $item[ConstantsEntity::REFUND][RefundEntity::AMOUNT];

                return $carry;
            },
            0);

        $totalAmount = $totalAmount / 100;

        $totalAmount = number_format($totalAmount, 2, '.', '');

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
            'amount'     => $totalAmount,
            'count'      => count($data),
            'date'       => $today
        ];

        return $mailData;
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
}

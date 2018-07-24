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
use RZP\Gateway\Netbanking\Canara\Gateway as CanaraGateway;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;


use Config;

class Canara extends Base
{
    use FileHandler;

    const FILE_NAME              = 'Canara_Netbanking_Refunds';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::CANARA_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_CANARA;
    const GATEWAY_CODE           = IFSC::CNRB;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE              = 'CNRB';

    //protected $config;

    protected function formatDataForFile(array $data)
    {

        $content[]    = [
                        'TRANSACTION DATE AND TIME',
                        'Refund Date',
                        'BANK_REF_NO',
                        'PG_REF_NUM',
                        'Refund Reference',
                        'Transaction Amount',
                        'Refund Amount'
                        ];

        foreach ($data as $row)
        {
            $transactionDate = Carbon::createFromTimestamp($row[ConstantsEntity::PAYMENT][PaymentEntity::CREATED_AT],
                                                          Timezone::IST)
                                                          ->format('d-m-Y H:i:s');

            $refundDate = Carbon::createFromTimestamp($row[ConstantsEntity::REFUND][RefundEntity::CREATED_AT],
                                                     Timezone::IST)
                                                     ->format('d-m-Y H:i:s');

            $paymentId = $row[ConstantsEntity::PAYMENT][PaymentEntity::ID];

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
                                                                           Action::AUTHORIZE);

            $content[] = [
                            $transactionDate,
                            $refundDate,
                            $netbanking[NetbankingEntity::BANK_PAYMENT_ID],
                            $paymentId,
                            $row[ConstantsEntity::REFUND][RefundEntity::ID],
                            $this->getFormattedAmount($row[ConstantsEntity::PAYMENT]
                                                        [PaymentEntity::AMOUNT]),
                            $this->getFormattedAmount($row[ConstantsEntity::REFUND]
                                                         [RefundEntity::AMOUNT])
                        ];
        }

        return $this->generateText($content,'|', true);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        return self::FILE_NAME . '_' . $date;
    }

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'  => $file->getLocation(),
            'signed_url' => $signedUrl,
        ];

        return $mailData;
    }
}

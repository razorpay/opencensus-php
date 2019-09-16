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
use RZP\Models\Payment\Refund\Status as RefundStatus;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Sbin extends Base
{
    use FileHandler;

    const FILE_NAME              = 'RZPY_SBI_Refund';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::SBI_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_SBI;
    const GATEWAY_CODE           = IFSC::SBIN;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;

    const BANK_CODE              = 'sbin';
    const DATE_FORMAT            = 'ymd';

    const REFUND_CODE            = 20;

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
        $content = [];

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

            $netbanking = $this->repo->netbanking->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);

            $content[] = [
                'Tnx Code'            => self::REFUND_CODE,
                'Txn Date(YYMMDD)'    => $date,
                'Refund Date(YYMMDD)' => $refundDate,
                'Ban Ref No.'         => $netbanking[NetbankingEntity::BANK_PAYMENT_ID],
                'Txn Amount'          => $this->getFormattedAmount($row[ConstantsEntity::PAYMENT][RefundEntity::AMOUNT]),
                'Refund Amount'       => $this->getFormattedAmount($row[ConstantsEntity::REFUND][RefundEntity::AMOUNT]),
            ];
        }

        return $this->generateText($content,'|',true);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('d.m.y');

        return self::FILE_NAME . '_' . $date;
    }
}

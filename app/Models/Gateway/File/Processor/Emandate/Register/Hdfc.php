<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Register;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Gateway\EMandate\Base as RegisterMail;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;

class Hdfc extends Base
{
    const DAILY         = 'daily';
    const MAX_END_DATE  = '31/12/2099';
    const FIXED         = 'Fixed';

    const STEP          = 'register';
    const GATEWAY       = Payment\Gateway::NETBANKING_HDFC;
    const FILE_NAME     = 'HDFC_EMandate_Registration';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::HDFC_EMANDATE_REGISTER;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        $payments = $this->repo->payment->fetchPendingEMandateRegistration(static::GATEWAY, $begin, $end);

        $paymentIds = $payments->pluck(Payment\Entity::ID)->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $payments;
    }

    public function generateData(PublicCollection $payments)
    {
        return $payments;
    }

    protected function formatDataForFile($payments)
    {
        $rows = [];

        foreach ($payments as $i => $payment)
        {
            $paymentId = $payment->getId();

            $startDate = Carbon::createFromTimestamp($payment->getCreatedAt(), Timezone::IST)->format('d/m/Y');

            $token = $payment->getGlobalOrLocalTokenEntity();

            $customer = $token->customer;

            $row = [
                Headings::CLIENT_NAME                   => 'RAZORPAY',
                Headings::MERCHANT_UNIQUE_REFERENCE_NO  => $paymentId,
                Headings::CUSTOMER_NAME                 => $customer->getName(),
                Headings::CUSTOMER_ACCOUNT_NUMBER       => $token->getAccountNumber(),
                Headings::AMOUNT                        => $this->getFormattedAmount($payment->getAmount()),
                Headings::AMOUNT_TYPE                   => self::FIXED,
                Headings::START_DATE                    => $startDate,
                Headings::END_DATE                      => self::MAX_END_DATE,
                Headings::FREQUENCY                     => self::DAILY,
                Headings::MANDATE_SERIAL_NUMBER         => $i+1,
                Headings::MANDATE_ID                    => $token->getId(),
                Headings::MERCHANT_REQUEST_NO           => $paymentId,
            ];

            $rows[] = $row;
        }

        $this->trace->info(TraceCode::EMANDATE_REGISTER_REQUEST_FILE_DATA, ['rows' => $rows]);

        return $rows;
    }
}

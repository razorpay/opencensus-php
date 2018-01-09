<?php

namespace RZP\Models\Gateway\File\Processor\EMandate\Register;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\EMandate\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;
use RZP\Gateway\Netbanking\Hdfc\Fields;

class Hdfc extends Base
{
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

        foreach ($payments as $payment)
        {
            $token = $payment->getGlobalOrLocalTokenEntity();

            $data = Fields::getEMandateRegistrationData($token);

            $startDate = Carbon::createFromTimestamp($data[Fields::START_TIMESTAMP], Timezone::IST)
                               ->format('d/m/Y');

            $endDate = Carbon::createFromTimestamp($data[Fields::END_TIMESTAMP], Timezone::IST)
                             ->format('d/m/Y');

            $row = [
                Headings::CLIENT_NAME                   => $data[Headings::CLIENT_NAME],
                Headings::MERCHANT_UNIQUE_REFERENCE_NO  => $data[Headings::MERCHANT_UNIQUE_REFERENCE_NO],
                Headings::CUSTOMER_NAME                 => $data[Headings::CUSTOMER_NAME],
                Headings::CUSTOMER_ACCOUNT_NUMBER       => $data[Headings::CUSTOMER_ACCOUNT_NUMBER],
                Headings::AMOUNT                        => $this->getFormattedAmount($payment->getAmount()),
                Headings::AMOUNT_TYPE                   => $data[Headings::AMOUNT_TYPE],
                Headings::START_DATE                    => $startDate,
                Headings::END_DATE                      => $endDate,
                Headings::FREQUENCY                     => $data[Headings::FREQUENCY],
                Headings::MANDATE_SERIAL_NUMBER         => $data[Headings::MANDATE_SERIAL_NUMBER],
                Headings::MANDATE_ID                    => $data[Headings::MANDATE_ID],
                Headings::MERCHANT_REQUEST_NO           => $data[Headings::MERCHANT_REQUEST_NO],
            ];

            $rows[] = $row;

            $this->trace->info(TraceCode::EMANDATE_REGISTER_REQUEST_ROW, ['row' => $row]);
        }

        return $rows;
    }
}

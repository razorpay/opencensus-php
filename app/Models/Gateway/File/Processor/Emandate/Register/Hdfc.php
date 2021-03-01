<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Register;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\Emandate\Base;
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
        $end   = $this->gatewayFile->getEnd();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        $tokens = $this->repo->token->fetchPendingEmandateRegistration(static::GATEWAY, $begin, $end);

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    public function generateData(PublicCollection $payments)
    {
        return $payments;
    }

    protected function formatDataForFile($tokens)
    {
        $rows = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $data = Fields::getEmandateRegistrationData($token, $paymentId, $token->merchant);

            $startDate = Carbon::createFromTimestamp($data[Fields::START_TIMESTAMP], Timezone::IST)
                               ->format('d/m/Y');

            $endDate = Carbon::createFromTimestamp($data[Fields::END_TIMESTAMP], Timezone::IST)
                             ->format('d/m/Y');

            $row = [
                Headings::CLIENT_NAME                  => $data[Headings::CLIENT_NAME],
                Headings::SUB_MERCHANT_NAME            => $data[Headings::SUB_MERCHANT_NAME],
                Headings::CUSTOMER_NAME                => $data[Headings::CUSTOMER_NAME],
                Headings::CUSTOMER_ACCOUNT_NUMBER      => $data[Headings::CUSTOMER_ACCOUNT_NUMBER],
                Headings::AMOUNT                       => number_format($token->getMaxAmount() / 100, 2, '.', ''),
                Headings::AMOUNT_TYPE                  => $data[Headings::AMOUNT_TYPE],
                Headings::START_DATE                   => $startDate,
                Headings::END_DATE                     => $endDate,
                Headings::FREQUENCY                    => $data[Headings::FREQUENCY],
                Headings::MANDATE_ID                   => $data[Headings::MANDATE_ID],
                Headings::MERCHANT_UNIQUE_REFERENCE_NO => $data[Headings::MERCHANT_UNIQUE_REFERENCE_NO],
                Headings::MANDATE_SERIAL_NUMBER        => $data[Headings::MANDATE_SERIAL_NUMBER],
                Headings::MERCHANT_REQUEST_NO          => $data[Headings::MERCHANT_REQUEST_NO],
            ];

            $rows[] = $row;

            $rowToTrace = $row;
            unset($rowToTrace[Headings::CUSTOMER_ACCOUNT_NUMBER]);

            $this->trace->info(TraceCode::EMANDATE_REGISTER_REQUEST_ROW, ['row' => $rowToTrace]);
        }

        return $rows;
    }
}

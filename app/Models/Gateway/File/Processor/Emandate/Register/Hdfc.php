<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Register;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Netbanking\Hdfc\Fields;
use RZP\Models\Gateway\File\Processor\Emandate\Base;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as Headings;

class Hdfc extends Base
{
    const STEP          = 'register';
    const GATEWAY       = Payment\Gateway::NETBANKING_HDFC;
    const FILE_NAME     = 'HDFC_EMandate_Registration';
    const EXTENSION     = FileStore\Format::XLSX;
    const FILE_TYPE     = FileStore\Type::HDFC_EMANDATE_REGISTER;

    const BASE_STORAGE_DIRECTORY = 'Hdfc/Emandate/Register/';

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end   = $this->gatewayFile->getEnd();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $variant = $this->app['razorx']->getTreatment(
                "EMANDATE_HDFC_REGISTER", self::EMANDATE_QUERY_OPTIMIZATION,
                $this->mode
            );

            if($variant === 'on')
            {
                $tokens = $this->repo->token->fetchPendingEmandateRegistrationOptimised(static::GATEWAY, $begin, $end);
            }
            else{
                $tokens = $this->repo->token->fetchPendingEmandateRegistration(static::GATEWAY, $begin, $end);
            }
        }
        catch (ServerErrorException $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::EMANDATE_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
                'target' => $this->gatewayFile->getTarget(),
                'type'   => $this->gatewayFile->getType()
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

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $fileName = parent::getFileToWriteNameWithoutExt($data);

        return static::BASE_STORAGE_DIRECTORY . $fileName;
    }
}

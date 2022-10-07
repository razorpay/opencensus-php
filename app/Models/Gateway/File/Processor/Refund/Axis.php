<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Gateway\Netbanking\Axis\Constants;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Services\NbPlus\Netbanking as NbplusNetbanking;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class Axis extends Base
{
    use FileHandler;

    const CORPORATE_FILE_NAME        = 'IConnect_Refund_RAZORPAY_CORP';
    const NON_CORPORATE_FILE_NAME    = 'IConnect_Refund_RAZORPAY';
    const EXTENSION                  = FileStore\Format::TXT;
    const FILE_TYPE                  = FileStore\Type::AXIS_NETBANKING_REFUND;
    const GATEWAY                    = Payment\Gateway::NETBANKING_AXIS;
    const CORPORATE_GATEWAY_CODE     = Netbanking::UTIB_C;
    const NON_CORPORATE_GATEWAY_CODE = IFSC::UTIB;
    const PAYMENT_TYPE_ATTRIBUTE     = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY     = 'Axis/Refund/Netbanking/';

    const HEADERS = [
        'Payee id', // pid
        'Payee name', // RAZORPAY
        'BID',
        'ITC',
        'PRN',
        'AMOUNT',
        'DATETIME',
        'REFUND Amount',
    ];

    /**
     * @param int $begin
     * @param int $end
     * @return PublicCollection
     */
    protected function fetchRefundsFromAPI(int $begin, int $end): PublicCollection
    {
        //
        // Regular flow - fetching refunds from API DB
        //

        $corporate = $this->gatewayFile->getCorporate();

        $gatewayCode = ($corporate === true) ?
            self::CORPORATE_GATEWAY_CODE :
            self::NON_CORPORATE_GATEWAY_CODE;

        $refunds = $this->repo->refund->fetchCorporateRefundsBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            $gatewayCode,
            $begin,
            $end,
            static::GATEWAY
        );

        return $refunds;
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $refundIds
     * @return array
     */
    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $corporate = $this->gatewayFile->getCorporate();

        $gatewayCode = ($corporate === true) ?
            self::CORPORATE_GATEWAY_CODE :
            self::NON_CORPORATE_GATEWAY_CODE;

        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY    => static::GATEWAY,
                    RefundConstants::SCROOGE_BANK       => $gatewayCode,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $row)
        {
            $date = Carbon::createFromTimestamp(
                    $row['payment']['created_at'], 'Asia/Kolkata')
                    ->format('Y/m/d');

            $formattedData[] = [
                $row['terminal']['gateway_merchant_id'],
                Constants::PAYEE_NAME,
                $this->fetchBankPaymentId($row),
                $row['terminal']['gateway_merchant_id'],
                $row['payment']['id'],
                number_format($row['payment']['amount'] / 100, 2, '.', ''),
                $date,
                number_format($row['refund']['amount'] / 100, 2, '.', '')
            ];
        }

        $initialLine = $this->getInitialLine('~~');

        $formattedData = $this->getTextData($formattedData, $initialLine, '~~');

        return $formattedData;
    }

    public function sendFile($data)
    {
        return;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $name = ($this->gatewayFile->getCorporate() === true) ?
            static::CORPORATE_FILE_NAME :
            static::NON_CORPORATE_FILE_NAME;

        if ($this->isTestMode() === true)
        {
            return static::BASE_STORAGE_DIRECTORY . $name . '_' . $time . '_' . $this->mode . '_1';
        }

        return static::BASE_STORAGE_DIRECTORY . $name . '_' . $time . '_1';
    }

    protected function fetchBankPaymentId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway'][NbplusNetbanking::BANK_TRANSACTION_ID]; // payment through nbplus service
        }

        return $data['gateway']['bank_payment_id'];
    }
}

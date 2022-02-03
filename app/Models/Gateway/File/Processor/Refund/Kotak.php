<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\NbPlus\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

//This code is not being used to generate refund file go to app/Gateway/Netbanking/Kotak/RefundFile.php

class Kotak extends Base
{
    use FileHandler;

    const TPV_FILE_NAME          = 'Kotak_Netbanking_Refund_OTRAZORPAY';
    const NON_TPV_FILE_NAME      = 'Kotak_Netbanking_Refund_OSRAZORPAY';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::KOTAK_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_KOTAK;
    const GATEWAY_CODE           = IFSC::KKBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Kotak/Refund/Netbanking/';

    protected $type = Payment\Entity::BANK;

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

        $tpv = $this->gatewayFile->getTpv();

        $refunds = $this->repo->refund->fetchRefundsForTpvBetweenTimestamps(
            static::PAYMENT_TYPE_ATTRIBUTE,
            static::GATEWAY_CODE,
            $begin,
            $end,
            static::GATEWAY,
            $tpv
        );

        return $refunds;
    }

    /**
     * Fetches all necessary refund related data required for generating the file
     * $entities - since it can either be payments or refunds based on whether we fetch from scrooge or not
     *
     * @param  PublicCollection $entities
     *
     * @return array
     */
    public function generateData(PublicCollection $entities)
    {
        $data = [];
        $isTpv = $this->gatewayFile->getTpv();

        // Refunds were fetched from scrooge
        if ($this->fetchRefundsFromScrooge === true)
        {
            foreach ($this->scroogeRefundsData as $refund)
            {
                $payment = $entities->where(Payment\Entity::ID, '=', $refund[RefundConstants::PAYMENT_ID])->first();

                if ($payment->terminal->isTpv() == $isTpv)
                {
                    $col = $this->collectPaymentData($payment);

                    $col['refund'] = $refund;

                    $data[] = $col;
                }
            }

            $data = $this->addGatewayEntitiesToDataWithPaymentIds($data, $this->scroogeRefundPaymentIds);
        }
        else
        {
            $scroogeRefundIds = [];

            // Is file based refund gateway for which refunds data need to be fetched from Scrooge
            $fileBasedRefundGateway = in_array(
                static::GATEWAY,
                array_keys(Payment\Gateway::$scroogeFileBasedRefundGatewaysWithTimestamps), true
            );

            if ($fileBasedRefundGateway === true)
            {
                $scroogeRefundIds = $entities->where(Payment\Refund\Entity::IS_SCROOGE, '=', 1)->getIds();

                if (count($scroogeRefundIds) > 0)
                {
                    $this->populateScroogeRefundsGivenIds($scroogeRefundIds);
                }

                $scroogeRefundIds = array_unique(array_column($this->scroogeRefundsData, RefundConstants::SCROOGE_ID));
            }

            // regular API flow
            foreach ($entities as $refund)
            {
                //
                // The following checks are being made to ensure these conditions
                // If a refund belongs to scrooge - Scrooge is the single source of truth -
                // whether the refund is to be sent in the file or not, there are various flows in which Scrooge
                // could process these refunds - Instant Refunds, FTAs, TPV, etc.
                // Hence, if a refund belongs to scrooge and it is of a file based gateway -
                // whose refunds data is fetched from Scrooge - we need to ensure that the refund must be present
                // in the response from Scrooge.
                //
                // Therefore, the only case where the following conditions don't evaluate to true is the following:
                // The refund was processed on scrooge, belonging to a file based refunds gateway via Scrooge,
                // by tpv or instant refunds so it should not be included in the file
                //
                if (($refund->isScrooge() === false) or
                    ($fileBasedRefundGateway === false) or
                    (in_array($refund->getId(), $scroogeRefundIds, true) === true))
                {
                    $payment = $refund->payment;

                    $col = $this->collectPaymentData($payment);

                    $col['refund'] = $refund->toArray();

                    $data[] = $col;
                }
            }

            $data = $this->addGatewayEntitiesToData($data, $entities);
        }

        $this->checkIfRefundsAreInValidDateRange($data);

        return $data;
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        $totalAmount = 0;

        foreach ($data as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['authorized_at'], Timezone::IST)->format('d-M-Y');
                $formattedData[] = [
                    $index + 1,
                    $this->fetchGatewayMerchantId($row),
                    $date,
                    $this->fetchBankVerificationId($row),
                    $row['refund']['amount'] / 100,
                    $this->fetchBankPaymentId($row),
                ];
            $totalAmount += $row['refund']['amount'] / 100;
        }

        $name = basename($this->getFileToWriteName());

        // First Line in the file is expected to be of the format
        // Format : FileName|ItemsCount|TotalAmount(Rs.)|CHECKSUM
        $initialLine = $name .'|'. count($formattedData) . '|' .$totalAmount . '|CHECKSUM' . "\r\n";

        $formattedData = $this->getTextData($formattedData, $initialLine);

        return $formattedData;
    }
    protected function fetchBankPaymentId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['payment']['transaction_id']; // payment through nbplus service
        }
        return $data['gateway']['bank_payment_id'];
    }
    protected function fetchBankVerificationId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['payment']['id']; // payment through nbplus service
        }

        return $data['gateway']['int_payment_id'] ?: $data['gateway']['verification_id'];
    }
    protected function fetchGatewayMerchantId($data)
    {
        if ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE)
        {
            return $data['terminal']['gateway_merchant_id']; // payment through nbplus service
        }

        return $data['gateway']['merchant_code'];
    }

    public function sendFile($data)
    {
        return;
    }


    protected function getFileToWriteName($ext = FileStore\Format::TXT)
    {
        return $this->getFileToWriteNameWithoutExt() . '.' . $ext;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $name = ($this->getTpv() === true) ? static::TPV_FILE_NAME : static::NON_TPV_FILE_NAME;

        return static::BASE_STORAGE_DIRECTORY . $name . '_' . $this->mode . '_' . $time;
    }
}

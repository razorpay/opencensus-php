<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Carbon\Carbon;

use Razorpay\IFSC\Bank;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Payment\Refund\Constants;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Gateway\File\Processor\FileHandler;

//This code is not being used to generate refund file go to app/Gateway/Netbanking/Kotak/RefundFile.php

class Kotak extends Base
{
    use FileHandler;

    const TPV_FILE_NAME          = 'Kotak_Netbanking_Refund_OTRAZORPAY';
    const NON_TPV_FILE_NAME      = 'Kotak_Netbanking_Refund_OSRAZORPAY';
    const EXTENSION              = FileStore\Format::TXT;
    const FILE_TYPE              = FileStore\Type::KOTAK_NETBANKING_REFUND;
    const GATEWAY                = Payment\Gateway::NETBANKING_KOTAK;
    const GATEWAY_CODE           = Bank::KKBK;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BASE_STORAGE_DIRECTORY = 'Kotak/Refund/Netbanking/';

    /**
     * Fetches all necessary refund related data required for generating the file
     * $entities - since it can either be payments or refunds based on whether we fetch from scrooge or not
     *
     * @param PublicCollection $entities
     *
     * @return array
     * @throws GatewayFileException
     */
    public function generateData(PublicCollection $entities): array
    {
        $data = [];
        $nbplusPaymentIds = [];
        $isTpv = $this->gatewayFile->getTpv();

        // Refunds were fetched from scrooge
        foreach ($this->scroogeRefundsData as $refund)
        {
            $payment = $entities->where(Payment\Entity::ID, '=', $refund[Constants::PAYMENT_ID])->first();

            if ($payment->terminal->isTpv() == $isTpv)
            {
                $col = $this->collectPaymentData($payment);

                $col['refund'] = $refund;

                $data[] = $col;
            }

            if (($payment->getCpsRoute() === PaymentEntity::NB_PLUS_SERVICE) or
                ($payment->getCpsRoute() === PaymentEntity::NB_PLUS_SERVICE_PAYMENTS))
            {
                $nbplusPaymentIds[] = $payment->getId();
            }
        }

        $data = $this->addGatewayEntitiesToDataWithPaymentIds($data, $this->scroogeRefundPaymentIds);

        $data = $this->addNbplusGatewayEntitiesToDataWithNbPlusPaymentIds($data, $nbplusPaymentIds, 'netbanking');

        $this->checkIfRefundsAreInValidDateRange($data);

        return $data;
    }

    protected function formatDataForFile(array $data): string
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
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['gateway']['bank_transaction_id']; // payment through nbplus service
        }
        return $data['gateway']['bank_payment_id'];
    }
    protected function fetchBankVerificationId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['payment']['id']; // payment through nbplus service
        }

        return $data['gateway']['int_payment_id'] ?: $data['gateway']['verification_id'];
    }
    protected function fetchGatewayMerchantId($data)
    {
        if (($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE) or
            ($data['payment']['cps_route'] === Payment\Entity::NB_PLUS_SERVICE_PAYMENTS))
        {
            return $data['terminal']['gateway_merchant_id']; // payment through nbplus service
        }

        return $data['gateway']['merchant_code'];
    }

    protected function getFileToWriteName($ext = FileStore\Format::TXT): string
    {
        return $this->getFileToWriteNameWithoutExt() . '.' . $ext;
    }

    protected function getFileToWriteNameWithoutExt(): string
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $name = ($this->getTpv() === true) ? static::TPV_FILE_NAME : static::NON_TPV_FILE_NAME;

        return static::BASE_STORAGE_DIRECTORY . $name . '_' . $this->mode . '_' . $time;
    }
}

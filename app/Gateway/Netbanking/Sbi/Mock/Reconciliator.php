<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Constants\Entity as ConstantsEntity;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    const PAYMENT_ENTITY = 'payment';
    const GATEWAY_ENTITY = 'netbanking';

    protected $gateway = Payment\Gateway::NETBANKING_SBI;

    protected $fileToWriteName = 'SBI_Mock_Recon';

    /**
     * The payment recon file does not contain any headers
     *
     * @override
     * @var bool
     */
    protected $shouldAddHeaders = true;

    protected $fileExtension = FileStore\Format::TXT;

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $paymentId = $data[ConstantsEntity::PAYMENT][Payment\Entity::ID];

        // We are doing a payment reconciliation, so action must be authorize
        $data[ConstantsEntity::NETBANKING] = $this->repo
            ->netbanking
            ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE)
            ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $data[] = [
                $row['payment']['merchant_id'],
                $row['payment']['id'],
                $row['netbanking']['bank_payment_id'],
                $row['payment']['amount'] / 100,
                'Success',
                $date,
            ];
        }

        $this->content($data, 'sbi_recon');

        return $this->generateText($data, ',');
    }
}

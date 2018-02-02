<?php

namespace RZP\Gateway\Netbanking\Csb\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Gateway\Netbanking\Base\Entity as Netbanking;

class Reconciliator extends Base\Mock\PaymentReconciliator
{
    protected $gateway = Payment\Gateway::NETBANKING_CSB;

    // TODO: What should this value be? If it is a manual recon, it should not matter
    protected $fileToWriteName = 'Sample Recon';

    /**
     * The payment recon file does not contain any headers
     *
     * @override
     * @var bool
     */
    protected $shouldAddHeaders = false;

    /**
     * CSB shares the payments to recon in txt format
     * @var string
     */
    protected $fileExtension = FileStore\Format::TXT;

    protected function getReconciliationData(array $input)
    {
        $data = array_reduce(
                    $input,
                    function($carry, $item)
                    {
                        $date = Carbon::createFromTimestamp(
                                    $item[ConstantsEntity::PAYMENT][Payment\Entity::CREATED_AT],
                                    Timezone::IST)
                                    ->format('Ymd');

                        $amount = $item[ConstantsEntity::PAYMENT][Payment\Entity::AMOUNT] / 100;

                        $carry[] = [
                            $item[ConstantsEntity::PAYMENT][Payment\Entity::ID],
                            $item[ConstantsEntity::NETBANKING][Netbanking::BANK_PAYMENT_ID],
                            $item[ConstantsEntity::TERMINAL][Terminal\Entity::ID],
                            number_format($amount, 2, '.', ''),
                            $item[ConstantsEntity::NETBANKING][Netbanking::STATUS],
                            $date,
                        ];

                        return $carry;
                    },
                    []
                );

        $this->content($data, 'csb_recon');

        return $this->generateText($data, '^');
    }

    /**
     * We need the CSB Merchant ID, and therefore we also fetch the terminal entity here.
     *
     * @return PublicCollection
     */
    protected function getEntitiesToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        return $this->repo
                    ->payment
                    ->fetchPaymentsAndTerminalsWithStatus($createdAtStart, $createdAtEnd, $this->gateway, $statuses);
    }

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $paymentId = $data[ConstantsEntity::PAYMENT][Payment\Entity::ID];

        // We are doing a payment reconciliation, so action must be authorize
        $data[Payment\Method::NETBANKING] = $this->repo
                                                 ->netbanking
                                                 ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE)
                                                 ->toArray();
    }

    /**
     * CSB needs terminal entity in array format as well.
     *
     * @override
     * @param PublicEntity $entity
     * @return array
     */
    protected final function getEntityAsArray(PublicEntity $entity): array
    {
        $terminal = $entity->terminal;

        return [
            ConstantsEntity::PAYMENT  => $entity->toArray(),
            ConstantsEntity::TERMINAL => $terminal->toArray()
        ];
    }
}

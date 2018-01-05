<?php

namespace RZP\Gateway\Base\Mock;

use App;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Base\RepositoryManager;
use RZP\Models\Base\PublicEntity;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Reconciliator\Base\Reconciliate;

class Reconciliator
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Override this variable in the child class
     * @var string
     */
    protected $gateway;

    /**
     * The current mode of reconciliation.
     * For eg. This can be payment, refund etc
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected static $fileToWriteName;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function generateReconciliation(array $input)
    {
        // If the type is not sent in the mock route request, we assign type to payment by default
        $input['type'] = $input['type'] ?? Reconciliate::PAYMENT;

        $this->setType($input['type']);

        switch ($this->type)
        {
            case Reconciliate::PAYMENT:
                $data = $this->generatePaymentReconciliation();
                break;

            case Reconciliate::REFUND:
                $data = $this->generateRefundReconciliation();
                break;

            default:
                throw new LogicException('Invalid recon type');
        }

        return $data;
    }

    private function generatePaymentReconciliation()
    {
        $payments = $this->getAllPaymentsToReconcile();

        $inputData = [];

        foreach ($payments as $payment)
        {
            $data['payment'] = $payment->toArray();

            $this->addGatewayEntityIfNeeded($data, $payment);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    private function generateRefundReconciliation()
    {
        $refunds = $this->getAllRefundsToReconcile();

        $inputData = [];

        foreach ($refunds as $refund)
        {
            $data['refund'] = $refund->toArray();

            $this->addGatewayEntityIfNeeded($data, $refund);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    protected function generate(array $input)
    {
        $data = $this->getReconciliationData($input);

        $creator = $this->createReconFile($data);

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }

    protected function getReconciliationData(array $input)
    {
        return [];
    }

    /**
     * Override this method in the child class
     * @param $content
     * @throws \BadMethodCallException
     */
    protected function createReconFile($content)
    {
        throw new \BadMethodCallException('createReconFile needs to be implemented in child gateway recon file');
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        $txt = '';

        $count = count($data);

        foreach ($data as $row)
        {
            $txt .= implode($glue, array_values($row));

            $count--;

            if (($ignoreLastNewline === false) or
                (($ignoreLastNewline === true) and ($count > 0)))
            {
                $txt .= "\r\n";
            }
        }

        return $txt;
    }

    protected function getAllRefundsToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->repo->refund->fetch(
            [
                'from'    => $createdAtStart,
                'to'      => $createdAtEnd,
                'gateway' => $this->gateway,
            ]);
    }

    /**
     * Not all methods need the gateway entity to generate the recon file.
     * The purpose of this method is to eliminate n DB calls for n payments.
     * To eliminate the DB calls, override this method in the base class.
     *
     * @param array $data
     * @param PublicEntity $entity
     */
    protected function addGatewayEntityIfNeeded(array & $data, PublicEntity $entity) {}

    /**
     * This can be used for mock recon content function
     * @param $content
     * @param null $action
     * @return void
     */
    public function content(& $content, $action = null) {}

    /**
     * Different gateways have different criteria for sending payments in the recon file.
     * To send payments do not match the criteria below, override this method in child class.
     *
     * @return PublicCollection
     */
    protected function getAllPaymentsToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        return $this->repo->payment->fetchPaymentsWithStatus($createdAtStart, $createdAtEnd, $this->gateway, $statuses);
    }

    private function setType(string $type)
    {
        $this->type = $type;
    }
}

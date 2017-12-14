<?php

namespace RZP\Gateway\Upi\Base\Mock;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Base\RepositoryManager;
use RZP\Models\Base\PublicCollection;

class Reconciliator
{
    /**
     * @var
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
     * @var string
     */
    protected static $fileToWriteName;

    const GATEWAYS_NEEDING_GATEWAY_ENTITY = [
        Payment\Gateway::UPI_SBI
    ];

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];
    }

    public function generateReconciliation(array $input)
    {
        $payments = $this->getAllPaymentsToReconcile();

        $inputData = [];

        foreach ($payments as $payment)
        {
            $data['payment'] = $payment->toArray();

            $this->addGatewayEntityIfNeeded($data);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    protected function generate(array $input)
    {
        list($totalAmount, $data) = $this->getReconciliationData($input);

        $creator = $this->createReconFile($data);

        $file = $creator->get();

        return [
            'local_file_path' => $file['local_file_path'],
            'count'           => count($data),
            'file_name'       => basename($file['local_file_path']),
            'total_amount'    => $totalAmount,
        ];
    }

    protected function getReconciliationData(array $input)
    {
        $data = [];

        $totalAmount = 0;

        return [$totalAmount, $data];
    }

    /**
     * Override this method in the child class
     * @param $content
     * @return null
     */
    protected function createReconFile($content)
    {
        // If this method is not overridden, we will have an exception be thrown
        return null;
    }

    protected function generateText($data, $glue = '~', $ignoreLastNewline = false)
    {
        //TODO : For now just copy pasting this from refund file
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

    /**
     * Not all methods need the gateway entity to generate the recon file.
     * The purpose of this method is to eliminate n DB calls for n payments.
     *
     * @param array $data
     */
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        if (in_array($this->gateway, self::GATEWAYS_NEEDING_GATEWAY_ENTITY, true) === false)
        {
            return;
        }

        $gatewayPayment = $this->repo->upi->fetchByPaymentId($data['payment']['id']);

        $data['gateway'] = $gatewayPayment->toArray();
    }

    /**
     * This can be used for mock recon content function
     * @param $content
     * @param null $action
     * @return mixed
     */
    public function content(& $content, $action = null)
    {
        return $content;
    }

    /**
     * Different gateways return different types of payments,
     * so this method can be overridden in the child class
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
}

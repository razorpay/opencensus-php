<?php

namespace RZP\Gateway\Base\Mock;

use App;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Base\RepositoryManager;
use RZP\Models\Base\PublicCollection;

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

    /**
     * Not all methods need the gateway entity to generate the recon file.
     * The purpose of this method is to eliminate n DB calls for n payments.
     * To eliminate the DB calls, override this method in the base class.
     *
     * @param array $data
     */
    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $gatewayPayment = $this->repo->upi->fetchByPaymentId($data['payment']['id']);

        $data['gateway'] = $gatewayPayment->toArray();
    }

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
}

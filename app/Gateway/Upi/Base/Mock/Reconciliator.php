<?php

namespace RZP\Gateway\Upi\Base\Mock;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment;
use RZP\Models\FileStore;
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

        $data[] = ReconFileFields::getHeaders();

        foreach ($input as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                        $row['payment']['created_at'],
                        Timezone::IST)
                        ->format('d-M-y H:i:s');

            $data[] = [
                $row['gateway']['gateway_merchant_id'],
                'Razorpay Software Private Limited',
                'Razorpay Software Private Limited',
                '9399',
                $row['payment']['id'],
                $row['gateway']['npci_reference_id'],
                $row['gateway']['gateway_payment_id'],
                'U69',
                'COLLECT',
                'Credit',
                $row['payment']['status'],
                'Collect from razorpay@sbi',
                $date,
                (string) ($row['payment']['amount'] / 100),
                '123456789',
                $row['gateway']['vpa'],
                $row['gateway']['name'] ?? 'Random name',
                $row['gateway']['bank'] . '0000437',
                (string) random_integer(10),
                'razorpay@sbi',
                'Razorpay Software Private Limited',
                'SBIN0000437',
                'P2M',
                'Mob'
            ];

            $totalAmount += $row['payment']['amount'] / 100;
        }

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
     * The purpose of this method is to eliminate O(n) DB calls for n payments.
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
     * Different gateways return different types of payments,
     * so this method can be overridden in the child class
     *
     * @return PublicCollection
     */
    protected function getAllPaymentsToReconcile()
    {
        return $this->repo->payment->fetchAllSuccessFullPaymentsFromYesterday();
    }
}
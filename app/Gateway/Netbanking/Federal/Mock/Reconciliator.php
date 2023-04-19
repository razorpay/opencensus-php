<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

class Reconciliator extends Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_FEDERAL;

        $this->fileToWriteName = 'mis_report_razorpay_' . Carbon::now(Timezone::IST)->format('dmY');

        parent::__construct();
    }

    protected function getEntitiesToReconcile()
    {
        return $this->repo
                    ->payment
                    ->fetch(['gateway' => $this->gateway]);
    }

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        $payment = $data['payment'];

        $data['netbanking'] = $this->repo
                                   ->netbanking
                                   ->findByPaymentIdAndAction($payment['id'], 'authorize')
                                   ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        $payment = $input[0]['payment'];

        $netbanking = $input[0]['netbanking'];

        $data[] = [
            'agg_reference_id' => $payment['id'],
            'atdr' => 7,
            'gst' => 1.26,
            'txn_date' => Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('d/m/Y'),
        ];

        $this->content($data);

        return $data;
    }

    protected function createFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($this->fileExtension)
                ->content($content)
                ->name($this->fileToWriteName)
                ->store($store)
                ->type($type)
                ->save();

        return $creator;
    }
}

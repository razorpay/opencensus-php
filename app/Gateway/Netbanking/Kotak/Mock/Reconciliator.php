<?php

namespace RZP\Gateway\Netbanking\Kotak\Mock;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

class Reconciliator extends Mock\PaymentReconciliator
{
    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_KOTAK;

        $this->fileExtension = 'txt';

        $this->fileToWriteName = 'otrazorpay' . Carbon::now(Timezone::IST)->format('Ymd');

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

        $data[] = [
            'OTRPKPSY',
            'OT RPKPSY',
            '9999999999',
            'Gaurav Kumar',
            '888888888',
            '777777777',
            number_format($payment['amount'] / 100, 2, '.', ''),
            Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('d-m-Y'),
            '456789',
            'C',
            'OT RPKPSY',
            '000003214326',
            Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('d/m/Y H:i:s'),
        ];

        $this->content($data);

        $formattedData = $this->generateText($data, ',');

        return $formattedData;
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

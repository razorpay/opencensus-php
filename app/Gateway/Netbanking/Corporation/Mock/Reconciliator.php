<?php

namespace RZP\Gateway\Netbanking\Corporation\Mock;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Corporation\ReconciliationFields;

class Reconciliator extends Mock\Reconciliator
{
    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_CORPORATION;

        $this->fileExtension = 'txt';

        $this->fileToWriteName = '12345_' . Carbon::now(Timezone::IST)->format('dmY') . '_OLT';

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

        $data[] = [ '12345',
                    Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('dmY'),
                    $netbanking['bank_payment_id'],
                    $payment['id'],
                    number_format($payment['amount'] / 100, 2, '.', ''),
                    'S',
        ];

        $this->content($data);

        $initialLine = 'HREC|' . $data[0][4] . '|1' . "\r\n";

        $formattedData = $this->generateText($data, '|');

        $finalLine = 'TREC**';

        $formattedData = $initialLine . $formattedData . $finalLine;

        return $formattedData;
    }

    //Overriding this base class create file as it creates and excel file
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

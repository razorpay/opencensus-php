<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

class Reconciliator extends Mock\Reconciliator
{
    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_PNB;

        $this->fileExtension = 'txt';

        $this->fileToWriteName = 'Recon File_RAZORPAY_PNB';

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

        $data['gateway'] = $this->repo
                                   ->netbanking
                                   ->findByPaymentIdAndAction($payment['id'], 'authorize')
                                   ->toArray();
    }

    protected function getReconciliationData(array $input)
    {
        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('Y-m-d');

            $data[] = [
                'prn'            => $row['payment']['reference1'],
                'payment_id'     => $row['payment']['id'],
                'bank_reference' => $row['gateway']['bank_payment_id'],
                'amount'         => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                'date'           => $date,
            ];
        }

        $this->content($data);

        return $this->generateText($data, '|');
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

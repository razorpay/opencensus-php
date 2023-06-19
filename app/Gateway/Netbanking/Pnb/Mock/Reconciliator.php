<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use Carbon\Carbon;

use RZP\Encryption;
use RZP\Models\FileStore;
use RZP\Gateway\Base\Mock;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;

class Reconciliator extends Mock\Reconciliator
{
    public function __construct()
    {
        $this->gateway = Gateway::NETBANKING_PNB;

        $this->fileExtension = 'xlsx';

        $this->fileToWriteName = 'Recon_File_RAZORPAY_PNB';

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
        $data = [];

        foreach ($input as $row)
        {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST));

            $col = [
                'Bank Reference No'       => $row['gateway']['bank_payment_id'],
                'Amount'                  => number_format($row['payment']['amount'] / 100, 2, '.', ''),
                'Date'                    => $date,
                'Aggregator Reference No' => $row['payment']['id'],
                'PID'                     => '',
                'Cust Acc No'             => '9999999999',
            ];

            $this->content($col, 'col_pnb_recon');

            $data[] = $col;
        }
        return $data;
    }

    protected function createFile(
        $content,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $config = $this->app['config'];

        $configKeys = $config['gateway.netbanking_pnb'];

        $publicKey  = trim(str_replace('\n', "\n", $configKeys['recon_key']));

        $creator->extension($this->fileExtension)
                ->mime('application/octet-stream')
                ->content($content)
                ->name($this->fileToWriteName)
                ->store($store)
                ->type($type)
                ->encrypt(Encryption\Type::PGP_ENCRYPTION,
                [
                    Encryption\PGPEncryption::PUBLIC_KEY => $publicKey,
                    Encryption\PGPEncryption::PASSPHRASE => $configKeys['recon_passphrase']
                ]
            )
                ->save();

        $creator->name($this->fileToWriteName.'.xlsx')
                ->extension(FileStore\Format::GPG)
                ->save();

        return $creator;
    }

}

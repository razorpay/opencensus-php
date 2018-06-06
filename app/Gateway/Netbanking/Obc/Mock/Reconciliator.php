<?php

namespace RZP\Gateway\Netbanking\Obc\Mock;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Mock\PaymentReconciliator as BaseMockRecon;

class Reconciliator extends BaseMockRecon
{
    protected $gateway = Payment\Gateway::NETBANKING_OBC;

    protected $fileExtension = FileStore\Format::DAT;

    const PAYMENT_ENTITY = 'payment';

    const GATEWAY_ENTITY = 'gateway';

    protected function generate(array $input)
    {
        $data = $this->getReconciliationData($input);

        $txtContent = $this->generateText($data, '|');

        $creator = $this->createFile($txtContent);

        $file = $creator->get();

        return ['local_file_path' => $file['local_file_path']];
    }

    public function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                            $row['payment']['created_at'],
                            Timezone::IST)
                        ->format('d/m/Y');

            $col = [
                'OBC',
                $date,
                '3028367',
                $this->formatAmount($row['payment']['amount']),
                $row['payment']['id'],
                99999,
            ];

            $this->content($col, 'col_payment_oriental_recon');

            $data[] = $col;
        }

        $this->content($data, 'oriental_recon');

        return $data;
    }

    protected function getFileNametoWrite()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $fileName = 'OBC_STLMT_' . $date . '_3028367_RAZORPAY_TX';

        return $fileName;
    }

    protected function formatAmount(int $amount)
    {
        $number =  number_format((float) $amount / 100, 2, '.', '');

        $numZeroes = 13 - strlen($number);

        $padLength = strlen($number) + $numZeroes;

        $padString = str_repeat('0', $numZeroes);

        return str_pad($number, $padLength, $padString, STR_PAD_LEFT);
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
                ->name($this->getFileNametoWrite())
                ->store($store)
                ->type($type)
                ->save();

        return $creator;
    }

    protected function getEntitiesToReconcile()
    {
        $input = [
            'gateway' => 'netbanking_obc',
        ];

        $payments = $this->repo->payment->fetch($input, '10000000000000');

        return $payments;
    }
}

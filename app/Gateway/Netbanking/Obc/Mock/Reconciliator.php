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

    public function __construct()
    {
        parent::__construct();

        // The file to write name attribute is generated dynamically
        $this->setFileToWriteName();
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
                '3028367', // TODO: Ensure that this is the correct PID
                $this->formatAmount($row['payment']['amount']),
                $row['payment']['id'],
                9999999999,
            ];

            $this->content($col, 'col_payment_oriental_recon');

            $data[] = $col;
        }

        $this->content($data, 'oriental_recon');

        return $this->generateText($data, '|');
    }

    protected function setFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $this->fileToWriteName = 'OBC_STLMT_' . $date . '_3028349_RAZORPAY_TX';
    }

    private function formatAmount(int $amount)
    {
        $number = number_format($amount / 100, 2, '.', ',');

        $numZeroes = 13 - strlen($number);

        $padLength = strlen($number) + $numZeroes;

        $padString = str_repeat('0', $numZeroes);

        return str_pad($number, $padLength, $padString, STR_PAD_LEFT);
    }
}

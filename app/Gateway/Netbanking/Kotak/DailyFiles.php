<?php

namespace RZP\Gateway\Netbanking\Kotak;

use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{

    public function generate($from, $to, $email = null)
    {
        // Since Kotak TPV requires entries for separate pool accounts in a
        // separate mail we will have to send them separately
        $tpvType = [true, false];

        $files = [];

        foreach ($tpvType as $tpv)
        {
            $files[] = $this->generateMail($from, $to, $tpv, $email);
        }

        return [
                    'refunds' => [
                        'tpv' => $files[0]['refunds'],
                        'nonTpv' => $files[1]['refunds']
                    ],
                    'claims' => [
                        'tpv' => $files[0]['claims'],
                        'nonTpv' => $files[1]['claims']
                    ]
                ];
    }

    public function generateMail($from, $to, $tpvEnabled = false, $email = null)
    {
        $claimsFileData = $this->getClaimsDataForTpv($from, $to, $tpvEnabled);

        $refundFileData = $this->getRefundsDataForTpv($from, $to, $tpvEnabled);

        $amount = [];
        $amount['claims'] = $claimsFileData['total_amount'];
        $amount['refunds'] = $refundFileData['total_amount'];
        $amount['total'] = $claimsFileData['total_amount'] - $refundFileData['total_amount'];

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFileData, $refundFileData, $email);
        }

        return [
                    'claims' => $claimsFileData['local_file_path'],
                    'refunds' => $refundFileData['local_file_path']
                ];
    }

    public function getRefundsDataForTpv($from, $to, $tpvEnabled = false)
    {
        $refunds = $this->repo->refund->fetchRefundsForTpvBetweenTimestamps(
                                            Payment\Entity::BANK,
                                            $this->bankCode,
                                            $from,
                                            $to,
                                            $this->gateway,
                                            $tpvEnabled);

        $count = $refunds->count();

        if ($count == 0)
        {
            return [
                'total_amount'    => 0,
                'count'           => 0,
                'url'             => '',
                'local_file_path' => '',
                'name'            => '',
             ];
        }

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $payment->toArray();
            $col['terminal'] = $terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;
        $input['tpv']  = $tpvEnabled;

        $gateway = $terminal->getGateway();

        $refundsResult =$this->app['gateway']->call($gateway, Payment\Action::GENERATE_REFUNDS, $input, $this->mode);

        $this->refundCore->reconcileNetbankingRefunds($data);

        return $refundsResult;
    }

    protected function getClaimsDataForTpv($from, $to, $tpvEnabled = false)
    {
        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        // Payments made yesterday are reconciled today,
        // so forwarding time stamps by 1 day
        list($from, $to) = $this->updateTimeStamps($from, $to);

        $claims= $this->repo->payment->fetchReconciledPaymentsForTpv($from,
                                                                         $to,
                                                                         $this->gateway,
                                                                         $status,
                                                                         $tpvEnabled,
                                                                         ['terminal']);

        if ($claims->count() === 0)
        {
            return [
                'total_amount'    => 0,
                'count'           => 0,
                'url'             => '',
                'name'            => '',
                'local_file_path' => '',
             ];
        }

        $data = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;
        $input['tpv']  = $tpvEnabled;

        $gateway = $claim->terminal->getGateway();

        return $this->app['gateway']->call($gateway, Payment\Action::GENERATE_CLAIMS, $input, $this->mode);
    }

    protected function updateTimeStamps($from, $to)
    {
        $tsDifference = self::SECONDS_PER_DAY;

        return [$from + $tsDifference, $to + $tsDifference];
    }
}

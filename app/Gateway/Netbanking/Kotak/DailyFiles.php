<?php

namespace RZP\Gateway\Netbanking\Kotak;

use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Base;

class DailyFiles extends Base\DailyFiles
{

    public function generate($from, $to)
    {
        // Since Kotak TPV requires entries for separate pool accounts in a
        // separate mail we will have to send them separately
        $tpvTerminals = $this->repo->terminal->getTpvTerminalIdsForGateway($this->gateway);

        $tpvTerminals = $tpvTerminals->map(function($item){return $item->getId();})->all();

        $nonTpvTerminals = $this->repo->terminal->getTerminalIdsForGateway($this->gateway, $tpvTerminals);

        $nonTpvTerminals = $nonTpvTerminals->map(function($item){return $item->getId();})->all();

        $tpvFiles = $this->generateMail($from, $to, $tpvTerminals);

        $nonTpvFiles = $this->generateMail($from, $to, $nonTpvTerminals);

        return ['refundsFile' => [ 'tpv' => $tpvFiles[0], 'nonTpv' => $nonTpvFiles[0]],
                'claimsFile' => [ 'tpv' => $tpvFiles[1], 'nonTpv' => $nonTpvFiles[1]]];
    }

    public function generateMail($from, $to, $terminalIds)
    {
        list($refundAmount, $refundsFile) = $this->getRefundsDataForTerminals($from, $to, $terminalIds);

        list($claimAmount, $claimsFile) = $this->getClaimsDataForTerminals($from, $to, $terminalIds);

        $amount = [];
        $amount['claims'] = $claimAmount;
        $amount['refunds'] = $refundAmount;
        $amount['total'] = $claimAmount - $refundAmount;

        // Send the mail only when there is at least 1 claim or refund
        if ($amount['claims'] + $amount['refunds'] > 0)
        {
            $this->sendMail($amount, $claimsFile, $refundsFile);
        }

        return [$refundsFile, $claimsFile];
    }

    protected function getRefundsDataForTerminals($from, $to, $terminalIds)
    {
        if (empty($terminalIds) === true)
        {
            return;
        }

        $refunds = $this->repo->refund->fetchRefundsForTerminalsBetweenTimestamps(
                                            Payment\Entity::BANK,
                                            $this->bankCode,
                                            $from,
                                            $to,
                                            $this->gateway,
                                            $terminalIds);

        $count = $refunds->count();

        if ($count == 0)
        {
            return [0, ''];
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

        $gateway = $terminal->getGateway();

        $action = 'generateRefunds';

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function getClaimsDataForTerminals($from, $to, $terminalIds)
    {
        if (empty($terminalIds) === true)
        {
            return;
        }

        $status = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        // Payments made yesterday are reconciled today,
        // so forwarding time stamps by 1 day
        list($from, $to) = $this->updateTimeStamps($from, $to);

        $claims= $this->repo->payment->fetchReconciledPaymentsForTerminals($from,
                                                                         $to,
                                                                         $this->gateway,
                                                                         $status,
                                                                         $terminalIds);

        if ($claims->count() === 0)
        {
            return [0, ''];
        }

        $data = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;

        $gateway = $claim->terminal->getGateway();

        $action = 'generateClaims';

        return $this->app['gateway']->call($gateway, $action, $input, $this->mode);
    }

    protected function updateTimeStamps($from, $to)
    {
        $tsDifference = self::SECONDS_PER_DAY;

        return [$from + $tsDifference, $to + $tsDifference];
    }
}

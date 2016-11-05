<?php

namespace RZP\Models\Transaction;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Gateway;
use RZP\Models\Adjustment;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
use RZP\Models\Base\UniqueIdEntity;

/**
 * See the issue https://github.com/razorpay/api/issues/59
 * for details on the bug being fixed here.
 */
class BugFixer extends Base\Service
{
    public function settlementFixerInTxn()
    {
        $txnRepo = new Transaction\Repository;
        $allTxns = $txnRepo->fetchTransactionsForAuthorizedRefundedPayments();

        $allTxns = $allTxns->groupBy(Entity::MERCHANT_ID)->toArray();

        $fullTotalAmount = 0;
        $fullTotalCount = 0;

        $data = [];

        foreach ($allTxns as $merchantId => $merchTxns)
        {
            $merchant = Merchant\Entity::findOrFail($merchantId);

            $merchTxns = new Base\Collection($merchTxns);
            $setlTxns = $merchTxns->groupBy(Entity::SETTLEMENT_ID)->toArray();

            $totalAmount = 0;

            $count = 0;

            foreach ($setlTxns as $setlId => $txns)
            {
                $amount = 0;

                foreach ($txns as $txn)
                {
                    $amount += $txn->getDebit();
                }

                $totalAmount += $amount;
                $count += count($txns);
            }

            $data['amount'][$merchantId] = $totalAmount;
            $data['count'][$merchantId] = $count;

            $fullTotalAmount += $totalAmount;
            $fullTotalCount += $count;

            $input = array(
                'update_escrow' => '0',
                'amount' => $amount,
                'currency' => 'INR',
                'description' => 'Random description');

            $adj = (new Adjustment\Core)->createAdjustment($input, $merchant);

            foreach ($setlTxns as $setlId => $txns)
            {
                $amount = 0;

                foreach ($txns as $txn)
                {
                    $amount += $txn->getDebit();
                    $txn->setAttribute(Transaction\Entity::SETTLED_AT, null);
                    $txn->setAttribute(Transaction\Entity::SETTLED, false);
                    $txn->saveOrFail();
                }

//                $totalAmount += $amount;

                $amount = -1 * $amount;
                $input = array('amount' => $amount, 'currency' => 'INR', 'description' => 'Random description');
                $updateEscrow = false;

                $adj = (new Adjustment\Entity)->build($input);
                $adj->setChannel(Settlement\Channel::KOTAK);

                $newAdjId = $setlId;

                $x = range (5,13);

                $base = UniqueIdEntity::BASE;

                foreach ($x as $ix)
                {
                    $l = $setlId[$ix];
                    $l = UniqueIdEntity::$baseValues[$l];
                    $i = rand(0, $l-1);
                    $newAdjId[$ix] = $base[$i];
                }

                $adj->setId($newAdjId);

                $timestamp = UniqueIdEntity::uidToTimestamp($newAdjId);
                $adj->setCreatedAt($timestamp);

                $adj->merchant()->associate($merchant);

                $adjRepo = new Adjustment\Repository;
                $adjRepo->saveOrFail($adj);

                $txn = (new Transaction\Core)->createFromAdjustment($adj, $updateEscrow);

                $x = range (6, 13);
                $newTxnId = $setlId;
                $newTxnId[5] = $newAdjId[5];

                foreach ($x as $ix)
                {
                    $l = $setlId[$ix];
                    $l = UniqueIdEntity::$baseValues[$l];
                    $i = rand(0, $l-1);
                    $newTxnId[$ix] = $base[$i];
                }

                $setl = (new Settlement\Repository)->findOrFail($setlId);

                $txn->setId($newTxnId);
                $txn->source()->associate($adj);
                $txn->setAttribute(Transaction\Entity::SETTLED, true);
                $txn->setAttribute(Transaction\Entity::SETTLED_AT, $setl->getCreatedAt());
                $txn->settlement()->associate($setl);

                (new Transaction\Repository)->saveOrFail($txn);
                $adj->transaction()->associate($txn);
                $adjRepo->saveOrFail($adj);
            }
        }

        $data['full_total_amount'] = $fullTotalAmount;
        $data['full_total_count'] = $fullTotalCount;

        return $data;
    }
}

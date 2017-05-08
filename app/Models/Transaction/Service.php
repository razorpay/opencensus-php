<?php

namespace RZP\Models\Transaction;

use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Transaction;
use RZP\Models\Report\Types\BasicEntityReport;

class Service extends Base\Service
{
    public function getTransactionRecords($input)
    {
        $txns = $this->repo->transaction->fetch($input, $this->merchant->getKey());

        return $txns->toArrayPublic();
    }

    public function getTransactionRecordById($id)
    {
        return $this->repo->transaction->fetchAndReturnPublicArray($id, $this->merchant);
    }

    public function settlementFixer()
    {
        return $this->repo->transaction(function()
        {
            return (new BugFixer)->settlementFixerInTxn();
        });
    }

    public function getReport($input)
    {
        $report = new BasicEntityReport(Constants\Entity::TRANSACTION);

        return $report->getReport($input);
    }

    public function createFeeBreakupForTransaction($input)
    {
        return (new Transaction\DataMigration())->createFeeBreakupForTransaction($input);
    }

    public function getEntityTransaction($entity, $id)
    {
        if ($entity === Constants\Entity::PAYMENT)
        {
            Payment\Entity::verifyIdAndStripSign($id);
        }
        else if ($entity === Constants\Entity::REFUND)
        {
            Refund\Entity::verifyIdAndStripSign($id);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                "invalid entity, entity should be either payment or refund");
        }

        $txn = $this->repo->transaction->findByEntityId($id, $this->merchant, true);

        return $txn->toArrayPublic();
    }
}

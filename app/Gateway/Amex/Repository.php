<?php

namespace RZP\Gateway\Amex;

use RZP\Constants\Table;
use RZP\Gateway\AxisMigs;
use RZP\Gateway\Base\Action;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Terminal as TerminalModel;

class Repository extends AxisMigs\Repository
{
    protected $entity = 'amex';

    protected function buildFetchQueryAdditional($params, $query)
    {
        $query->where('amex', '=', '1');
    }

    /**
     * Used in Payment Reconciliate for fetching
     * payment by given gateway vpc_TransacationNo and merchant account number
     * for authorize Action
     * @param string $referenceNumber, string $merchantAccountNumber
     * @return Entity
     */
    public function findPaymentForGateway(string $referenceNumber, string $merchantAccountNumber)
    {
        /*
         *  select * from `axis` inner join `terminals` on `terminals`.`id` = `terminal_id` where
         * `vpc_TransactionNo` = ? and `action` = ? and `gateway_merchant_id` = ?
         */

        (new TerminalModel\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $terminalId = $this->repo->terminal->dbColumn(Terminal::ID);

        return $this->newQuery()
            ->join(Table::TERMINAL, $terminalId, '=', Entity::TERMINAL_ID)
            ->where(Entity::VPC_TRANSACTION_NUMBER, '=', $referenceNumber)
            ->where(Entity::ACTION, '=', Action::AUTHORIZE)
            ->where(Terminal::GATEWAY_MERCHANT_ID, '=', $merchantAccountNumber)
            ->firstOrFail();
    }
}

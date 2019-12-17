<?php


namespace RZP\Models\TerminalOnboardingDetail;

use RZP\Models\Base;
use RZP\Constants;
use RZP\Models\Terminal;
use RZP\Constants\Table;
use RZP\Models\TerminalOnboardingDetail;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::TERMINAL_ONBOARDING_DETAIL;

    public function fetchByMerchantIdAndStatus(string $mid, array $status)
    {
        $terminalOnboardingDetailTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $terminalRepo = $this->repo->terminal;

        $terminalOnboardingStatus = $this->dbColumn(TerminalOnboardingDetail\Entity::STATUS);

        $terminalId = $terminalRepo->dbColumn(Terminal\Entity::ID);

        $terminalMerchantId = $terminalRepo->dbColumn(Terminal\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->join(Table::TERMINAL, $terminalId, '=', $terminalOnboardingDetailTerminalId)
                    ->where($terminalMerchantId, '=', $mid)
                    ->whereIn($terminalOnboardingStatus, $status)
                    ->get();
    }

}

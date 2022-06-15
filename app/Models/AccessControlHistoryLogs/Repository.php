<?php

namespace RZP\Models\AccessControlHistoryLogs;

use RZP\Constants;
use RZP\Models\Base;
use \RZP\Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = Constants\Table::ACCESS_CONTROL_HISTORY_LOGS;

    protected $merchantIdRequiredForMultipleFetch = false;

    public function fetchMultiple(array $input, Merchant\Entity $merchant)
    {
        $this->setBaseQueryIfApplicable(true);

        $data = parent::fetch($input, $merchant->getId());

        return $data;
    }

    protected function setBaseQueryIfApplicable(bool $useMasterConnection)
    {
        if ($useMasterConnection === true)
        {
            $mode = $this->app['rzp.mode'];

            $this->baseQuery = $this->newQueryWithConnection($mode)->useWritePdo();
        }
        else
        {
            $this->baseQuery = $this->newQueryWithConnection($this->getSlaveConnection());
        }
    }
}

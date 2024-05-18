<?php

namespace RZP\Models\Base\Traits;

use App;
use Database\Connection;
use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Mode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;

trait LazyLoadingRelationFetch
{
    public function getRelationValue($key)
    {
        $relationFetchFromSlaveEnv = getenv('RELATION_FETCH_FROM_SLAVE');

        if ((in_array($key, EntityConstants::getLazyLoadRelationEntitiesFromSlave(), true) === true) and ($relationFetchFromSlaveEnv == true))
        {
            $originalConnectionName = $this->getConnectionName();

            $mode = App::getFacadeRoot()['rzp.mode'];

            $this->setConnection(($mode === Mode::TEST) ? Connection::SLAVE_TEST : Connection::SLAVE_LIVE);

            $relation = parent::getRelationValue($key);

            $this->setConnection($originalConnectionName);

            return $relation;
        }
        else
        {
            return parent::getRelationValue($key);
        }
    }
}

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

        $testCaseExecution = App::getFacadeRoot()['config']['applications.test_case.execution'] ?? false;
        
        if ((in_array($key, EntityConstants::getLazyLoadRelationEntitiesFromSlave(), true) === true) and
            (App::getFacadeRoot()->runningUnitTests() === false) and ($testCaseExecution === false))
        {
            $originalConnectionName = $this->getConnectionName();

            try
            {
                $mode = App::getFacadeRoot()['rzp.mode'];
            }
            catch(\Throwable $e)
            {
                return parent::getRelationValue($key);
            }

            if ($mode === Mode::TEST)
            {
                return parent::getRelationValue($key);
            }

            $this->setConnection(Connection::SLAVE_LIVE);

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

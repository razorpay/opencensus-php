<?php

namespace RZP\Models\Card\IIN;

use RZP\Exception;
use RZP\Constants\Entity as E;
use RZP\Models\Base\Observer as BaseObserver;

class Observer extends BaseObserver
{
    /**
     * Used to flush the cache on creates.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<iin_id>_<type>
     *
     * @param Entity $Iin
     */
    public function created(Entity $iin)
    {
        $this->flushCache($iin);
    }

    /**
     * Used to flush the cache on deletes.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<iin_id>
     *
     * @param Entity $Iin
     */
    public function deleted(Entity $iin)
    {
        $this->flushCache($iin);
    }

    /**
     * Used to flush the cache on updates.
     * We flush the cache by deleting all keys with the tag
     * <entityName>_<iin_id>
     *
     * @param Entity $Iin
     */
    public function updated($iin)
    {
        $this->flushCache($iin);
    }

    protected function flushCache($iin)
    {
        $this->validateEntity($iin);

        $iin->flushCache($iin->getEntity() . '_' . $iin->getIin());
    }
}

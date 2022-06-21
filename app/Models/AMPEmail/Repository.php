<?php

namespace RZP\Models\AMPEmail;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'amp_email';


    /**
     * @param string $entityId
     *
     * @return mixed
     * @throws \RZP\Exception\ServerErrorException
     */
    public function getDetailsForEntityId(string $entityId)
    {
        return $this->newQueryOnSlave()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->first()
                    ->callOnEveryItem('toArrayPublic');

    }

}

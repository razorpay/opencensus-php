<?php

namespace RZP\Models\Nodal\Statement;

use RZP\Models\Base;
use RZP\Services\Harvester\EsClient as HarvesterEsClient;

class EsRepository extends Base\EsRepository
{
    /**
     * {@inheritDoc}
     *
     * Index & type name is fixed in harvester and it we don't need to follow api's convention to form index & type
     * name automatically in this specific case.
     */
    protected $indexName = 'nodal_statements';

    /**
     * {@inheritDoc}
     */
    protected $typeName  = 'nodal_statements';

    protected $queryFields = [
        Entity::MODE,
        Entity::DEBIT,
        Entity::CREDIT,
        Entity::BANK_NAME,
        Entity::PARTICULARS,
        Entity::REFERENCE1,
        Entity::REFERENCE2,
        Entity::TRANSACTION_DATE,
        Entity::BANK_REFERENCE_NUMBER,
    ];

    /**
     * {@inheritDoc}
     *
     * There wont be any change in index name in Harvester ES
     *
     * @param string $indexPrefix
     * @param string $typePrefix
     */
    public function setIndexAndTypeName(string $indexPrefix, string $typePrefix)
    {
        return;
    }

    /**
     * {@inheritDoc}
     *
     * This specific esDao client initialized in here will use Harvester's api as data source
     */
    protected function initEsDao()
    {
        $this->esDao = new HarvesterEsClient;
    }
}

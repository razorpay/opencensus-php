<?php

namespace RZP\Models\Transaction\Statement;

use RZP\Models\Base;
use RZP\Models\Transaction;

class EsRepository extends Transaction\EsRepository
{
    protected $indexName = 'transaction';


    public function getIndexSuffix(): string
    {
        return $this->indexName . '_' . $this->mode;
    }
}

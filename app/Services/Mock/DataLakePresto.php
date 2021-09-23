<?php

namespace RZP\Services\Mock;

use RZP\Services\DataLakePresto as BaseDataLakePresto;

class DataLakePresto extends BaseDataLakePresto
{
    public function getDataFromDataLake($query, $associate = true)
    {
        return [];
    }
}

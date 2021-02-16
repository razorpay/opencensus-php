<?php

namespace RZP\Models\PaperMandate\PaperMandateUpload;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'paper_mandate_upload';

    public function findLatestByMandateId($id)
    {
        return $this->newQuery()
                    ->where('paper_mandate_id', '=', $id)
                    ->orderBy('created_at', 'desc')
                    ->limit(1)
                    ->get();
    }
}

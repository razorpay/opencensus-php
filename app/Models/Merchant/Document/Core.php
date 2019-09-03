<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function delete(Entity $document)
    {
        $this->trace->info(TraceCode::DOCUMENT_DELETE_REQUEST, ['id' => $document->getId()]);

        return $this->repo->deleteOrFail($document);
    }
}

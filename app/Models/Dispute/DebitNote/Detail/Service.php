<?php

namespace RZP\Models\Dispute\DebitNote\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $this->trace->info(TraceCode::DEBIT_NOTE_DETAIL_CREATE_INPUT, $input);

        (new Validator)->validateInput('create', $input);

        $entity = (new Entity)->build($input);

        $this->repo->debit_note_detail->saveOrFail($entity);

        $this->trace->info(TraceCode::DEBIT_NOTE_DETAIL_CREATED, $entity->toArray());
    }
}
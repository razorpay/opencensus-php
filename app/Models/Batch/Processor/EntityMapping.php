<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;

class EntityMapping extends Base
{
    public function __construct(Entity $batch)
    {
        parent::__construct($batch);
    }

    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $fromEntityType = $this->settingsAccessor->get(Header::ENTITY_FROM_TYPE);

        $toEntityType = $this->settingsAccessor->get(Header::ENTITY_TO_TYPE) . 's';

        $fromEntity = $this->repo->$fromEntityType->findOrFailPublic($entry[Header::ENTITY_FROM_ID]);

        $fromEntity->$toEntityType()->sync($entry[Header::ENTITY_TO_ID]);
    }

    /**
     * Here in this we are traversing through the whole list and making a fromEntity -> toEntityId's mapping so that
     * we can directly sync.
     *
     * @param array $entries
     */
    protected function processEntries(array & $entries)
    {
        $processedEntries = [];

        foreach ($entries as $entry)
        {
            $fromEntityId = $entry[Header::ENTITY_FROM_ID];

            $toEntityId = $entry[Header::ENTITY_TO_ID];

            $processedEntries[$fromEntityId][] = $toEntityId;
        }

        // modifying the processed entries in such a way that processEntries parent function will be able to accomdate.
        $processedEntries = array_map(function ($fromEntityId, $toEntityId) {
            return [Header::ENTITY_FROM_ID => $fromEntityId, Header::ENTITY_TO_ID => $toEntityId];
        }, array_keys($processedEntries), $processedEntries);

        parent::processEntries($processedEntries);
    }

    /**
     * {@inheritDoc}
     */
    protected function updateBatchPostValidation(array $entries, array $input)
    {
        $totalCount  = count($entries);

        $this->batch->setTotalCount($totalCount);
    }

    /**
     * {@inheritDoc}
     */
    protected function sendProcessedMail()
    {
        return;
    }
}

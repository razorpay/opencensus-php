<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Base\EsRepository;

class EntityMapping extends Base
{
    /**
     * {@inheritDoc}
     */
    protected function processEntry(array & $entry)
    {
        $fromEntityType = $this->settingsAccessor->get(Header::ENTITY_FROM_TYPE);

        // Concatenating toEntityTpe with plural because we have relationship functions like merchants()
        // accounts() etc
        $toEntityType = str_plural($this->settingsAccessor->get(Header::ENTITY_TO_TYPE));

        $fromEntity = $this->repo->$fromEntityType->findOrFailPublic($entry[Header::ENTITY_FROM_ID]);

        $this->repo->sync($fromEntity, $toEntityType, $entry[Header::ENTITY_TO_IDS]);

        // we need to sync to ES to update on ES document
        $this->repo->$fromEntityType->syncToEs($fromEntity, EsRepository::UPDATE);

        $toEntities = $this->repo->$toEntityType->findMany($entry[Header::ENTITY_TO_IDS]);

        if (empty($toEntities) === false) 
        {
            foreach ($toEntities as $toEntity) 
            {
                $this->repo->$toEntityType->syncToEs($toEntity, EsRepository::UPDATE);
            }

        }

        $entry[Header::ENTITY_TO_IDS] = implode(',', $entry[Header::ENTITY_TO_IDS]);

        $entry[Header::STATUS] = Status::SUCCESS;
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

        // Modifying the processed entries in such a way that processEntries parent function will be able to accomdate.
        $processedEntries = array_map(function ($fromEntityId, $toEntityId) {
            return [Header::ENTITY_FROM_ID => $fromEntityId, Header::ENTITY_TO_IDS => $toEntityId];
        }, array_keys($processedEntries), $processedEntries);

        // Since entries is passed by reference the same is used in further processing so changing the whole entries.
        $entries = $processedEntries;

        parent::processEntries($entries);
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

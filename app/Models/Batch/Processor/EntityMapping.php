<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicCollection;

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

        $oldEntitiesMapped = $fromEntity->{$toEntityType};

        $this->repo->sync($fromEntity, $toEntityType, $entry[Header::ENTITY_TO_IDS]);

        // We don't need entityType in plural after this
        $toEntityType = $this->settingsAccessor->get(Header::ENTITY_TO_TYPE);

        $this->syncEntitiesToEs($fromEntityType, $entry[Header::ENTITY_FROM_ID], $toEntityType, $oldEntitiesMapped);

        $entry[Header::ENTITY_TO_IDS] = implode(',', $entry[Header::ENTITY_TO_IDS]);

        $entry[Header::STATUS] = Status::SUCCESS;
    }

    protected function syncEntitiesToEs(
        string $fromEntityType,
        string $fromEntityId,
        string $toEntityType,
        PublicCollection $oldEntitiesMapped)
    {
        // calling find again as we want updated data after sync
        $fromEntity = $this->repo->$fromEntityType->findOrFailPublic($fromEntityId);

        $newEntitiesMapped = $fromEntity->{str_plural($toEntityType)};

        $this->repo->$fromEntityType->syncToEs($fromEntity, EsRepository::UPDATE);

        $affectedEntities = $oldEntitiesMapped->concat($newEntitiesMapped);

        foreach ($affectedEntities as $entity)
        {
            $this->repo->$toEntityType->syncToEs($entity, EsRepository::UPDATE);
        }
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

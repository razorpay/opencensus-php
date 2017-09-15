<?php

namespace RZP\Models\Base\Traits\Es;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;

/**
 * Converts es results (array) back to model collections.
 *
 * This ensures that the interface to repo's fetch() is intact and returns
 * actual model collection in all cases (searched from es or MySQL).
 */
trait Hydrator
{
    protected function hydrate(array $items): PublicCollection
    {
        $entity = $this->getEntityObject();

        $hydrator = function (array $item) use ($entity)
                    {
                        $this->preProcessForHydration($item);

                        $model = $entity->newFromBuilder($item);

                        $this->postProcessForHydration($model, $item);

                        return $model;
                    };

        return $entity->newCollection(array_map($hydrator, $items));
    }

    // ----------------------------------------------------------------------
    // Following two methods can be overridden(and written in corresponding
    // Repository class) if required.

    /**
     * Process the array item before it's used to convert into
     * corresponding model. E.g. 'notes' comes as JSON from ES but as text
     * from MySQL and so we json_encode that value so the array can be used
     * to build model(in above method) without issues.
     *
     * @param array $item
     */
    protected function preProcessForHydration(array & $item)
    {
        $this->jsonEncodeNotesForHydration($item);
    }

    /**
     * Processes the model after hydration. Handles relations association
     * and unset not expected/required values.
     *
     * @param PublicEntity $model
     * @param array        $item
     */
    protected function postProcessForHydration(PublicEntity $model, array & $item)
    {
    }

    // ---------------------------------------------------------------------

    /**
     * Common to most of the models and so kept here.
     *
     * Json encodes the notes attribute from es search result.
     *
     * @param array $item
     *
     * @return void
     */
    protected function jsonEncodeNotesForHydration(array & $item)
    {
        if (array_key_exists('notes', $item) === true)
        {
            $item['notes'] = json_encode($item['notes'], JSON_FORCE_OBJECT);
        }
    }
}

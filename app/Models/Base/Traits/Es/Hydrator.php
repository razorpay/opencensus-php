<?php

namespace RZP\Models\Base\Traits\Es;

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
        $class = $this->getEntityClass();

        $instance = (new $class)->newInstance();

        $hydrator = function (array $item) use ($instance)
                    {
                        $this->preProcessForHydration($item);

                        $model = $instance->newFromBuilder($item);

                        $this->postProcessForHydration($model, $item);

                        return $model;
                    };

        return $instance->newCollection(array_map($hydrator, $items));
    }

    // ----------------------------------------------------------------------
    // Following two methods can be overridden(and written in corresponding
    // Repository class) if required.

    protected function preProcessForHydration(array & $item)
    {
        $this->jsonEncodeNotesForHydration($item);
    }

    protected function postProcessForHydration($model, array & $item)
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

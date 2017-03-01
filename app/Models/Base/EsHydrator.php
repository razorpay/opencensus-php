<?php

namespace RZP\Models\Base;

/**
 * Converts es results (array) back to model collections.
 *
 * This ensures that the interface to repo's fetch() is intact and returns
 * actual model collection in all cases (searched from es or MySQL).
 */
trait EsHydrator
{
    protected function hydrate(array $items)
    {
        $instance = $this->getModel()->newInstance();

        $hydrator = function (array $item) use ($instance)
                    {
                        $this->preProcessForHydration($item);

                        $model = $instance->newFromBuilder($item);

                        $this->postProcessForHydration($model, $item);

                        return $model;
                    };

        return $instance->newCollection(array_map($hydrator, $items));
    }

    //
    // Following two methods can be overridden if required. But it is not
    // recommended to be in that place at first.
    //

    protected function preProcessForHydration(array & $item)
    {
        $this->jsonEncodeNotesForHydration($item);
    }

    protected function postProcessForHydration($model, array & $item)
    {
    }

    // -----------------------------------------------------------------------

    protected function getModel()
    {
        $class = $this->getEntityClass();

        return new $class;
    }

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
        if (array_key_exists('notes', $item))
        {
            $item['notes'] = json_encode($item['notes']);
        }
    }
}

<?php

namespace RZP\Models\Base;

/**
 * Converts array results from ES to collection of models (see PublicCollection).
 *
 * Doing so helps in making the codes in other parts (controller, services)
 * carefree about where the search is happening and if the result will be array
 * or model collection.
 *
 * For now, it's very simple and will see if it's working fine in all coming
 * use cases with little abstracted changes.
 */
trait EsHitsToCollection
{
    public function esHitsToCollection(array $hits)
    {
        $hits = $this->preProcessForHydration($hits);

        $class = $this->getEntityClass();

        $model = new $class;

        return (new $model)->hydrate($hits);
    }

    /**
     * Pre-processes hits before calling laravel's hydrate method.
     *
     * For now, only notes seems to be an issue. So for it, just walking over array
     * and json_encoding the notes key.
     * This method (including the class itself) will mature with new use cases.
     *
     *
     * @param array $hits
     *
     * @return array
     */
    public function preProcessForHydration(array $hits)
    {
        return array_map(
            function ($hit)
            {
                if (array_key_exists('notes', $hit))
                {
                    $hit['notes'] = json_encode($hit['notes']);
                }

                return $hit;
            },
            $hits);
    }
}

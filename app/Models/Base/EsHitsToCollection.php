<?php

namespace RZP\Models\Base;

trait EsHitsToCollection
{
    public function esHitsToCollection(array $hits)
    {
        $hits = $this->preProcessForHydration($hits);

        $class = $this->getEntityClass();

        $model = new $class;

        return (new $model)->hydrate($hits);
    }

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

<?php

namespace RZP\Models\Merchant\Acs\Traits;

trait AsvCacheKeys
{

    public function getCacheKey($id, $columns, string $connectionType = null)
    {
        $tag              = 'asv:{' . strtolower($this->entity) . '_' . $id . '}';
        $columnsString    = md5(serialize($columns));
        $connectionSuffix = $connectionType ? ':' . $connectionType : '';
        return "tag:{$tag}:{$columnsString}{$connectionSuffix}:key";
    }
}

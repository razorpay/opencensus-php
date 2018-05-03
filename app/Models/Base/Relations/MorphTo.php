<?php

namespace RZP\Models\Base\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo as BaseMorphTo;

use RZP\Exception;

class MorphTo extends BaseMorphTo
{
    public function associate($model)
    {
        if (($model !== null) and (($model instanceof Model) === false))
        {
            throw new Exception\RuntimeException(
                'Only a valid parent entity can be associated',
                [
                    'entity'    => $this->parent->entity,
                    'parent_id' => $this->foreignKey,
                ]);
        }

        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $model->getKey() : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        return $this->parent->setRelation($this->relation, $model);
    }
}

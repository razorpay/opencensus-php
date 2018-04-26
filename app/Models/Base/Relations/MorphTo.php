<?php

namespace RZP\Models\Base\Relations;

use RZP\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo as BaseMorphTo;

class MorphTo extends BaseMorphTo
{
    public function associate($model)
    {
        if (($model !== null) and (($model instanceof Model) === false))
        {
            throw new Exception\LogicException('should be a model');
        }

        $this->parent->setAttribute(
            $this->foreignKey, $model instanceof Model ? $model->getKey() : null
        );

        $this->parent->setAttribute(
            $this->morphType, $model instanceof Model ? $model->getMorphClass() : null
        );

        // $this->parent->setPolyMorphicRelation($this->relation, $model);

        return $this->parent->setRelation($this->relation, $model);
    }
}

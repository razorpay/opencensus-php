<?php

namespace RZP\Models\Base\Relations;

use RZP\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BaseBelongsTo;

class BelongsTo extends BaseBelongsTo
{
    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        if (($model !== null) and (($model instanceof Model) === false))
        {
            throw new Exception\RuntimeException(
                'Only a valid parent entity can be associated',
                [
                    'entity'    => $this->child->entity,
                    'parent_id' => $this->foreignKey,
                ]);
        }

        $ownerKey = $model instanceof Model ? $model->getAttribute($this->ownerKey) : $model;

        $this->child->setAttribute($this->foreignKey, $ownerKey);

        if ($model instanceof Model)
        {
            $this->child->setRelation($this->relation, $model);
        }

        return $this->child;
    }
}

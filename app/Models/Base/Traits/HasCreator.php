<?php

namespace RZP\Models\Base\Traits;

use RZP\Constants\Entity;

/**
 * Requires using model to define CREATOR, CREATOR_ID & CREATOR_TYPE attributes.
 */
trait HasCreator
{
    // Todo: Use model observers and associate creator implicitly. That will make this trait complete.

    /**
     * Morphs to one of following models:
     * - \RZP\Models\User\Entity
     * - \RZP\Models\Admin\Admin\Entity
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function creator()
    {
        return $this->morphTo(self::CREATOR);
    }

    /**
     * @return string|null
     */
    public function getCreatorId()
    {
        return $this->getAttribute(self::CREATOR_ID);
    }

    /**
     * @return string|null
     */
    public function getCreatorType()
    {
        return $this->getAttribute(self::CREATOR_TYPE);
    }

    public function isCreatorTypeUser(): bool
    {
        return $this->getCreatorType() === Entity::USER;
    }

    public function isCreatorTypeAdmin(): bool
    {
        return $this->getCreatorType() === Entity::ADMIN;
    }
}

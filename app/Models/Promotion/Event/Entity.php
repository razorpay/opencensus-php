<?php

namespace RZP\Models\Promotion\Event;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
     const NAME             = 'name';
     const DESCRIPTION      = 'description';
     const REFERENCE1       = 'reference1';
     const REFERENCE2       = 'reference2';
     const REFERENCE3       = 'reference3';
     const REFERENCE4       = 'reference4';
     const REFERENCE5       = 'reference5';

    protected $entity = 'promotion_event';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function setName(string $name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }
}

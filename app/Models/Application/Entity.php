<?php

namespace RZP\Models\Application;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::APPLICATION;
    protected $table  = Table::APPLICATION;

    protected $generateIdOnCreate = true;

    const ID                     = 'id';
    const ID_LENGTH              = 14;
    const NAME                   = 'name';
    const TITLE                  = 'title';
    const DESCRIPTION            = 'description';
    const TYPE                   = 'type';
    const APP                    = 'app';
    const FEATURE                = 'feature';
    const HOME_APP               = 'home_app';

    const SLACK_APP              = 'slack_app';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::TYPE,
        self::HOME_APP,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::TYPE,
        self::HOME_APP,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::TITLE,
        self::DESCRIPTION,
        self::TYPE,
        self::HOME_APP,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getTitle()
    {
        return $this->getAttribute(self::TITLE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isHomeApp()
    {
        return $this->getAttribute(self::HOME_APP);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setTitle($title)
    {
        $this->setAttribute(self::TITLE, $title);
    }

    public function setDescription($description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setHomeApp($homeApp)
    {
        $this->setAttribute(self::HOME_APP, $homeApp);
    }

    // ============================= END SETTERS =============================
}

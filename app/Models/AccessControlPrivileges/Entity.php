<?php


namespace RZP\Models\AccessControlPrivileges;

use RZP\Constants;
use RZP\Models\Base\PublicEntity;
use RZP\Models\AccessPolicyAuthzRolesMap;

class Entity extends PublicEntity
{
    const NAME             = 'name';
    const LABEL            = 'label';
    const DESCRIPTION      = 'description';
    const PARENT_ID        = 'parent_id';
    const VISIBILITY       = 'visibility';
    const EXTRA_DATA       = 'extra_data';
    const VIEW_POSITION    = 'view_position';

    const ACTIONS = 'actions';

    const PRIVILEGE_DATA = 'privilege_data';

    const PRIVILEGES_FETCH_DATA_COUNT  = 100;

    protected $entity = Constants\Table::ACCESS_CONTROL_PRIVILEGES;

    protected static $generators = [
        self::ID,
    ];

    protected $casts = [
        self::EXTRA_DATA => 'array',
    ];

    protected $fillable = [
        self::NAME,
        self::LABEL,
        self::DESCRIPTION,
        self::PARENT_ID,
        self::VISIBILITY,
        self::EXTRA_DATA,
        self::VIEW_POSITION
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::LABEL,
        self::DESCRIPTION,
        self::PARENT_ID,
        self::VISIBILITY,
        self::EXTRA_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::VIEW_POSITION
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::LABEL,
        self::PARENT_ID,
        self::EXTRA_DATA,
        self::ACTIONS,
        self::VIEW_POSITION
    ];

    public function setName(string $name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setLabel(string $label)
    {
        $this->setAttribute(self::LABEL, $label);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setParentId(string $parentId)
    {
        $this->setAttribute(self::PARENT_ID, $parentId);
    }

    public function setVisibility(int $visibility)
    {
        $this->setAttribute(self::VISIBILITY, $visibility);
    }

    public function setExtraData(array $extraData)
    {
        $this->setAttribute(self::EXTRA_DATA, $extraData);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getLabel()
    {
        return $this->getAttribute(self::LABEL);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getParent()
    {
        return $this->getAttribute(self::PARENT_ID);
    }

    public function getVisibility()
    {
        return $this->getAttribute(self::VISIBILITY);
    }

    public function getExtraData()
    {
        return $this->getAttribute(self::EXTRA_DATA);
    }


    //Relation
    public function actions()
    {
        return $this->hasMany(AccessPolicyAuthzRolesMap\Entity::class, AccessPolicyAuthzRolesMap\Entity::PRIVILEGE_ID, self::ID);
    }

}

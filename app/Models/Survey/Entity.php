<?php

namespace RZP\Models\Survey;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::SURVEY;
    protected $table  = Table::SURVEY;

    protected $generateIdOnCreate = true;

    const NAME                   = 'name';
    const DESCRIPTION            = 'description';
    const SURVEY_TTL             = 'survey_ttl'; // In hours

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
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

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getSurveyTtl()
    {
        return $this->getAttribute(self::SURVEY_TTL);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setDescription($description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setSurveyTtl($surveyTtl)
    {
        $this->setAttribute(self::SURVEY_TTL, $surveyTtl);
    }

    // ============================= END SETTERS =============================
}

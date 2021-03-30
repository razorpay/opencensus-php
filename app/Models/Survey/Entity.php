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
    const TYPE                   = 'type';
    const SURVEY_URL             = 'survey_url';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
        self::TYPE,
        self::SURVEY_URL,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
        self::TYPE,
        self::SURVEY_URL,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::SURVEY_TTL,
        self::TYPE,
        self::SURVEY_URL,
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getSurveyUrl()
    {
        return $this->getAttribute(self::SURVEY_URL);
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

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setSurveyUrl($url)
    {
        $this->setAttribute(self::SURVEY_URL, $url);
    }

    // ============================= END SETTERS =============================
}

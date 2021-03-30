<?php

namespace RZP\Models\Survey\Response;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::SURVEY_RESPONSE;
    protected $table  = Table::SURVEY_RESPONSE;

    protected $generateIdOnCreate = true;

    const SURVEY_ID              = 'survey_id';
    const TRACKER_ID             = 'tracker_id';

    // Constants
    const MID                       = 'mid';
    const UID                       = 'uid';
    const HIDDEN                    = 'hidden';
    const SURVEY_RESPONSE_SAVED     = 'survey_response_saved';
    const SUCCESS                   = 'success';
    const EVENT_ID                  = 'event_id';
    const EVENT_TYPE                = 'event_type';
    const FORM_RESPONSE             = 'form_response';

    protected $fillable = [
        self::ID,
        self::SURVEY_ID,
        self::TRACKER_ID,
    ];

    protected $visible = [
        self::ID,
        self::SURVEY_ID,
        self::TRACKER_ID,
    ];

    protected $public = [
        self::ID,
        self::SURVEY_ID,
        self::TRACKER_ID,
    ];

    // ============================= GETTERS =============================

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getSurveyId()
    {
        return $this->getAttribute(self::SURVEY_ID);
    }

    public function getTrackerId()
    {
        return $this->getAttribute(self::TRACKER_ID);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setSurveyId($surveyId)
    {
        $this->setAttribute(self::SURVEY_ID, $surveyId);
    }

    public function setTrackerId($trackerId)
    {
        $this->setAttribute(self::TRACKER_ID, $trackerId);
    }

    // ============================= END SETTERS =============================
}

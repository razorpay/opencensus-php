<?php

namespace RZP\Models\Survey\Tracker;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as EntityConstants;

class Entity extends Base\PublicEntity
{
    protected $entity = EntityConstants::SURVEY_TRACKER;
    protected $table  = Table::SURVEY_TRACKER;

    protected $generateIdOnCreate = true;

    const SURVEY_ID              = 'survey_id';
    const SURVEY_EMAIL           = 'survey_email';
    const SURVEY_SENT_AT         = 'survey_sent_at';
    const SURVEY_FILLED_AT       = 'survey_filled_at';
    const ATTEMPTS               = 'attempts';
    const SKIP_IN_APP            = 'skip_in_app';

    //
    const CHANNEL                = 'channel';
    const NPS_ACTIVE_CA          = 'nps_active_ca';
    const NPS_CSAT               = 'nps_csat';
    const NPS_PAYOUTS_DASHBOARD  = 'nps_payouts_dashboard';
    const NPS_PAYOUTS_API        = 'nps_payouts_api';
    const NPS_SURVEY             = 'nps_survey';
    const SURVEY_TYPE            = 'survey_type';
    const MID                    = 'mid';
    const X_UID                  = 'x_uid';
    const START_TIME             = 'start_time';
    const END_TIME               = 'end_time';
    const COHORT                 = 'cohort';
    const COHORT_LIST            = 'cohort_list';
    const BASE_MODEL_DIR         = 'RZP\Models';
    const USER_ID                = 'user_id';
    const SURVEY_URL             = 'survey_url';
    const CONTACT_TYPE           = 'contact_type';
    const X_CONTACT_TYPE         = 'x_contact_type';
    const USER                   = 'user';
    const MERCHANT_POC           = 'merchant_poc';
    const BENEFICIARY            = 'beneficiary';
    const ACCOUNT_STATUS         = 'account_status';
    const X_CA_ACCOUNT_STATUS    = 'x_ca_account_status';


    const DUMMY_UID_FOR_MERCHANT_POC_MAILS = 'merc_poc_id';
    const DUMMY_UID_FOR_BENEFICIARY_MAILS  = 'beneficiary_id';

    protected $fillable = [
        self::ID,
        self::SURVEY_ID,
        self::SURVEY_EMAIL,
        self::SURVEY_SENT_AT,
        self::SURVEY_FILLED_AT,
        self::ATTEMPTS,
        self::SKIP_IN_APP,
    ];

    protected $visible = [
        self::ID,
        self::SURVEY_ID,
        self::SURVEY_EMAIL,
        self::SURVEY_SENT_AT,
        self::SURVEY_FILLED_AT,
        self::ATTEMPTS,
        self::SKIP_IN_APP,
        self::SURVEY_TYPE,
        self::SURVEY_URL,
        self::CHANNEL,
    ];

    protected $public = [
        self::ID,
        self::SURVEY_ID,
        self::SURVEY_EMAIL,
        self::SURVEY_SENT_AT,
        self::SURVEY_FILLED_AT,
        self::ATTEMPTS,
        self::SKIP_IN_APP,
        self::SURVEY_TYPE,
        self::SURVEY_URL,
        self::CHANNEL,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    // ============================= GETTERS =============================

    public function getId() : string
    {
        return $this->getAttribute(self::ID);
    }

    public function getSurveyId() : string
    {
        return $this->getAttribute(self::SURVEY_ID);
    }

    public function getSurveyEmail() : string
    {
        return $this->getAttribute(self::SURVEY_EMAIL);
    }

    public function getSurveySentAt() : int
    {
        return $this->getAttribute(self::SURVEY_SENT_AT);
    }

    public function getSurveyFilledAt()
    {
        return $this->getAttribute(self::SURVEY_FILLED_AT);
    }

    public function getAttempts() : int
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getSkipInApp() : bool
    {
        return $this->getAttribute(self::SKIP_IN_APP);
    }

    // ============================= END GETTERS =============================

    // ============================= SETTERS =============================

    public function setSurveyId(string $surveyId)
    {
        $this->setAttribute(self::SURVEY_ID, $surveyId);
    }

    public function setSurveyEmail(string $email)
    {
        $this->setAttribute(self::SURVEY_EMAIL, $email);
    }

    public function setSurveySentAt(int $surveySentAt)
    {
        $this->setAttribute(self::SURVEY_SENT_AT, $surveySentAt);
    }

    public function setSurveyFilledAt($surveyFilledAt)
    {
        $this->setAttribute(self::SURVEY_FILLED_AT, $surveyFilledAt);
    }

    public function setAttempts(int $attempts)
    {
        $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setSkipInApp(bool $skipInApp)
    {
        $this->setAttribute(self::SKIP_IN_APP, $skipInApp);
    }

    // ============================= END SETTERS =============================
}

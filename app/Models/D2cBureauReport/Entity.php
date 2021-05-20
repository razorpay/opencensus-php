<?php

namespace RZP\Models\D2cBureauReport;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $generateIdOnCreate = true;

    protected static $sign = 'd2c';

    protected $entity = 'd2c_bureau_report';

    const ID_LENGTH = 14;

    const ID                        = 'id';
    const MERCHANT_ID               = 'merchant_id';
    const USER_ID                   = 'user_id';
    const D2C_BUREAU_DETAIL_ID      = 'd2c_bureau_detail_id';
    const PROVIDER                  = 'provider';
    const ERROR_CODE                = 'error_code';
    const SCORE                     = 'score';
    const NTC_SCORE                 = 'ntc_score';
    const REPORT                    = 'report';
    const UFH_FILE_ID               = 'ufh_file_id';
    const INTERESTED                = 'interested';
    const CSV_REPORT_UFH_FILE_ID    = 'csv_report_ufh_file_id';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';

    const REQUEST_OBJECT            = 'request_object';

    protected $public = [
        self::ID,
        self::PROVIDER,
        self::SCORE,
        self::NTC_SCORE,
        self::REPORT,
        self::INTERESTED,
        self::CREATED_AT,
        self::MERCHANT_ID,
        self::USER_ID,
        self::UFH_FILE_ID,
        self::PROVIDER
    ];

    // merchant credit report should be exposed to least number of people in org.
    // score & report have been added to hidden to hide them from admin dashboard.
    protected $hidden = [
        self::SCORE,
        self::NTC_SCORE,
        self::REPORT,
    ];

    protected $fillable = [
        self::ID,
        self::PROVIDER,
        self::ERROR_CODE,
        self::SCORE,
        self::NTC_SCORE,
        self::REPORT,
        self::INTERESTED,
        self::UFH_FILE_ID,
        self::CSV_REPORT_UFH_FILE_ID,
        self::CREATED_AT,
    ];

    protected $casts = [
        self::INTERESTED     => 'bool',
    ];

    public function getUfhFileId()
    {
        return $this->getAttribute(self::UFH_FILE_ID);
    }

    public function getCsvReportUfhFileId()
    {
        return $this->getAttribute(self::CSV_REPORT_UFH_FILE_ID);
    }

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function user()
    {
        return $this->belongsTo(\RZP\Models\User\Entity::class);
    }

    public function d2cBureauDetail()
    {
        return $this->belongsTo(\RZP\Models\D2cBureauDetail\Entity::class);
    }

    public function toArrayForDashboard()
    {
        $report = $this->makeVisible([self::SCORE, self::REPORT, self::NTC_SCORE])->toArrayPublic();

        if(isset($report[self::REPORT]) === true)
        {
            $report[self::REPORT] = json_decode($report[self::REPORT]);
        }

        return $report;
    }

    public function setCsvReportFileId($fileId)
    {
        $this->setAttribute(self::CSV_REPORT_UFH_FILE_ID, $fileId);
    }
}

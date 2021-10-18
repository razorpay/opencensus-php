<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Admin\Admin;
use RZP\Models\BankingAccount;

class Entity extends Base\PublicEntity
{
    const DATE_AND_TIME = 'date_and_time';

    const FOLLOW_UP_DATE_AND_TIME = 'follow_up_date_and_time';

    const ADMIN_ID = 'admin_id'; // id of admin who added comment

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    const COMMENT_ID = 'comment_id';

    const STATE_LOG_ID = 'state_log_id';

    const ADMIN = 'admin';

    const COMMENT = 'comment';

    const STATE_LOG = 'state_log';

    protected $entity = 'banking_account_call_log';

    protected $table = Table::BANKING_ACCOUNT_CALL_LOG;

    protected $generateIdOnCreate = true;

    protected $relations = [
        self::ADMIN,
        self::COMMENT,
        self::STATE_LOG
    ];

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::ID,
        self::STATE_LOG_ID,
        self::ADMIN_ID,
        self::COMMENT_ID,
        self::DATE_AND_TIME,
        self::FOLLOW_UP_DATE_AND_TIME,
        self::BANKING_ACCOUNT_ID,
    ];

    protected $visible = [
        self::ID,
        self::STATE_LOG_ID,
        self::ADMIN_ID,
        self::COMMENT_ID,
        self::DATE_AND_TIME,
        self::FOLLOW_UP_DATE_AND_TIME,
        self::BANKING_ACCOUNT_ID,
        self::ADMIN,
        self::COMMENT,
        self::STATE_LOG,
        self::CREATED_AT,
    ];

    public $public = [
        self::ID,
        self::DATE_AND_TIME,
        self::FOLLOW_UP_DATE_AND_TIME,
        self::ADMIN_ID,
        self::COMMENT_ID,
        self::STATE_LOG_ID,
        self::BANKING_ACCOUNT_ID,
        self::CREATED_AT,
        self::ADMIN,
        self::COMMENT,
        self::STATE_LOG,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function bankingAccount()
    {
        return $this->belongsTo(BankingAccount\Entity::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin\Entity::class);
    }

    public function comment()
    {
        return $this->belongsTo(BankingAccount\Activation\Comment\Entity::class);
    }

    public function stateLog()
    {
        return $this->belongsTo(BankingAccount\State\Entity::class);
    }

}

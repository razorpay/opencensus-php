<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Admin\Admin;
use RZP\Models\User;
use RZP\Models\BankingAccount;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ADMIN_ID = 'admin_id'; // id of admin who added comment

    const USER_ID = 'user_id'; // id of user who added comment - one of RBL merchant users

    const BANKING_ACCOUNT_ID = 'banking_account_id';

    const COMMENT = 'comment';

    const SOURCE_TEAM_TYPE = 'source_team_type'; // internal(RZP) /external(bank)

    const SOURCE_TEAM = 'source_team'; // Sales/Ops/Product/etc

    // internal/external denoting whether the comment
    // is to be sent to an external party(bank, for example)
    const TYPE = 'type';

    /*
    This is needed because we want to capture the exact time at which
    we received the comment (say from bank). Sometimes we receive updates
    from bank in an ad-hoc manner via whatsapp/email/phone/etc.
    Ops team may not immediately add a comment to dashboard.
    They may do it the next day or so.
    added_at is to capture when exactly the comment corresponds to so
    as to gather an accurate timeline, rather than when it was created
    in our system(which is what created_at would signify)
     */
    const ADDED_AT = 'added_at';

    // relations
    const ADMIN = 'admin';

    const USER = 'user';

    //other constants
    const ADMIN_EMAIL = 'admin_email';

    const ADMIN_NAME = 'admin_name';

    const NOTES = 'notes';

    const FIRST_DISPOSITION = 'first_disposition';

    const SECOND_DISPOSITION = 'second_disposition';

    const THIRD_DISPOSITION = 'third_disposition';

    const DATE_TIME = 'date_time';

    const OPS_CALL_COMMENT = 'ops_call_comment';

    const MERCHANT_ID = 'merchant_id';

    protected $entity = 'banking_account_comment';

    protected $table = Table::BANKING_ACCOUNT_COMMENT;

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::COMMENT,
        self::NOTES,
        self::SOURCE_TEAM_TYPE,
        self::TYPE,
        self::SOURCE_TEAM,
        self::ADDED_AT,
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::COMMENT,
        self::NOTES,
        self::SOURCE_TEAM_TYPE,
        self::SOURCE_TEAM,
        self::TYPE,
        self::ADDED_AT,
        self::CREATED_AT,
        self::ADMIN,
        self::USER,
    ];

    public $public = [
        self::ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::COMMENT,
        self::NOTES,
        self::SOURCE_TEAM_TYPE,
        self::SOURCE_TEAM,
        self::TYPE,
        self::ADDED_AT,
        self::CREATED_AT,
        self::ADMIN,
        self::USER,
    ];

    /**
     * @var array
     *  This will be used for toArrayCaPartnerBankPoc filter
     */
    protected $bankBranchPoc = [
        self::ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::COMMENT,
        self::SOURCE_TEAM_TYPE,
        self::SOURCE_TEAM,
        self::TYPE,
        self::ADDED_AT,
        self::CREATED_AT,
        self::ADMIN,
        self::USER,
    ];

    /**
     * @var array
     *  This will be used for toArrayCaPartnerBankManager filter
     */
    protected $bankBranchManager = [
        self::ID,
        self::ADMIN_ID,
        self::USER_ID,
        self::BANKING_ACCOUNT_ID,
        self::COMMENT,
        self::SOURCE_TEAM_TYPE,
        self::SOURCE_TEAM,
        self::TYPE,
        self::ADDED_AT,
        self::CREATED_AT,
        self::ADMIN,
        self::USER,
    ];

    protected $defaults = [
      self::NOTES    => [],
    ];

    protected $dates = [
        self::ADDED_AT,
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

    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function getComment()
    {
        return $this->getAttribute(self::COMMENT);
    }

    public function getBankingAccountId()
    {
        return $this->getAttribute(self::BANKING_ACCOUNT_ID);
    }

    public function toArrayCaPartnerBankPoc(): array
    {
        $result = parent::toArrayAdmin();

        return array_only($result, $this->bankBranchPoc);
    }

    public function toArrayCaPartnerBankManager(): array
    {
        $result = parent::toArrayAdmin();

        return array_only($result, $this->bankBranchManager);
    }
}

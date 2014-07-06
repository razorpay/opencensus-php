<?php

namespace Models\Transaction;

use EE\Exception;

class Transaction extends UniqueIdDal
{

    const ID = Common::ID;

    const AUTH_AMOUNT = 'auth_amount';

    const AMOUNT = 'amount';

    const STATUS = 'status';

    const CURRENCY = 'currency';

    const DESCRIPTION = 'description';

    const TOKEN_ID = 'token_id';

    const HOLD = 'hold';

    const ERROR_CODE = 'error_code';

    const ERROR_DESCRIPTION = 'error_description';

    const EMAIL = 'email';

    const CONTACT = 'contact';

    const UDF = 'udf';

    protected $table = \Constants\Table::TRANSACTION;

    protected $sign = 'txn';

    protected $entity = 'transaction';

    private static $fetch_param_rules = array(
        'created'       => 'numeric',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required',
        'status'        => 'in:failed,captured,capture_failed,auth,open,refunded,settlement_sent,settled');

    protected $fillable = array(
        self::ID,
        self::MERCHANT_ID,
        self::TOKEN_ID,
        self::STATUS,
        self::AUTH_AMOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::DESCRIPTION,
        self::EMAIL,
        self::CONTACT,
        self::UDF);

    protected $visible = array(
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::EMAIL,
        self::CONTACT,
        // 'livemode',
        self::STATUS,
        self::UDF,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected $guarded = array(self::ID);

    protected function getUdfAttribute($udf)
    {
        return unserialize($udf);
    }

    public function setUdfAttribute($value)
    {
        $this->attributes[self::UDF] = serialize($value);
    }

    public function setCaptureAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setAuthAmount()
    {
        $authAmount = $this->getAttribute(self::AMOUNT);

        $this->setAttribute(self::AUTH_AMOUNT, $authAmount);
    }

    const FETCH_WITH_CARD       = 0x1024;
    const FETCH_WITH_TOKEN      = 0x2048;

    /**
     * Retrieves the transactions from database for a particular merchant.
     * @param  array        $param
     * @return Collection   A collection of transactions
     */
    public static function fetch($param)
    {
        if ($param === null)
        {
            throw new Exception\InvalidArgumentException('$param not provided');
        }
        self::validateFetchParams($param);

        $cols = array();

        /*
         * Create the query.
         */
        $query = self::where(self::MERCHANT_ID, '=', $param['merchant_id'])
                     ->orderBy(self::UPDATED_AT, 'desc');

        if (isset($param['from']))
        {
            $query = $query->where(self::UPDATED_AT, '>=', $param['from']);
        }

        if (isset($param['to']))
        {
            $query = $query->where(self::UPDATED_AT, '<=', $param['to']);
        }

        if (isset($param['status']))
        {
            $query = $query->where(self::STATUS, '=', $param['status']);
        }

        if (isset($param['count']))
        {
            $query->take($param['count']);
        }
        else
        {
            $query->take(10);
        }

        if (isset($param['skip']))
        {
            $query->skip($param['skip']);
        }

        return $query->with('token.card')->get();
    }

    public static function validateFetchParams(array $param)
    {
        validate(self::$fetch_param_rules, $param);
    }

    private static function checkTimestamp(&$timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp < 0)
            $timestamp = 0; // @todo: provide a better default.
        else if ($timestamp > time())
            $timestamp = time(); //@todo: consider what to put as max?
        return true;
    }

    public function isProcessed()
    {
        return ($this->getAttribute(self::STATUS) == Status::AUTH);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(self::STATUS) == Status::CAPTURED);
    }

    public function isRefunded()
    {
        return ($this->getAttribute(self::STATUS) == Status::REFUNDED);
    }

    public function isFailed()
    {
        return ($this->getAttribute(self::STATUS) == Status::FAILED);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(self::STATUS) == $status);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setStatusAndSave($status)
    {
        $this->setAttribute(self::STATUS, $status);
        $this->save();
    }

    public function getObjectAttribute()
    {
        return 'transaction';
    }

    public function getMerchantId()
    {
        return (int)$this->getAttribute(self::MERCHANT_ID);
    }

    public function toArrayWithCard()
    {
        $data = parent::toArray();

        $token = $this->token()->first();

        if ($token === null)
        {
            throw new Exception\LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_TOKEN_NOT_FOUND);
        }

        $card = $token->card()->first();

        if ($card === null)
        {
            throw new Exception\LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_CARD_NOT_FOUND);
        }

        $cardData = $card->toArray();

        $data['card'] = $cardData;

        return $data;
    }

    public function hdfc()
    {
        return $this->hasOne('hdfc', 'trackid', 'id');
    }

    public static function updateProcessed($id)
    {
        $txn = static::where('id', $id)
                    ->update(array(self::STATUS => 'auth'));
    }

    public function token()
    {
        return $this->belongsTo(
            __NAMESPACE__.'\Token');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\DAL\Merchant');
    }

    public function setError($code, $desc)
    {
        $this->setAttribute(self::ERROR_CODE, $code);
        $this->setAttribute(self::ERROR_DESCRIPTION, $desc);
    }

    public static function findByIdAndMerchantId($id, $merchantId)
    {
        return static::where(self::MERCHANT_ID, $merchantId)
                     ->find($id);
    }

    public static function findByIdAndMerchantIdOrFailPublic($id, $merchantId)
    {
        $txn = static::where(self::MERCHANT_ID, $merchantId)
                     ->find($id);

        if ($txn === null)
        {
            $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $txn;
    }

    public static function loadWithTokenAndCard($id, $merchantId)
    {
        $txn = self::with('token')
                   ->merchantId($merchantId)
                   ->where(self::ID, '=', $id)
                   ->first();

        if (($txn !== null) and
            ($txn->token !== null))
        {
            $card = $txn->token->card()->first();
        }

        return $txn;
    }

    public function scopeMerchantId($query, $merchantId)
    {
        return $query->where(self::MERCHANT_ID,'=',$merchantId);
    }

    public function toArrayTraceRelevant()
    {
        $data = $this->attributes;

        $relevantData = array();

        $fields = array(
            self::ID,
            self::MERCHANT_ID,
            self::TOKEN_ID,
            self::STATUS,
            self::AMOUNT,
            self::ERROR_CODE);

        $relevantData = array_intersect_key($data, array_flip($fields));

        return $relevantData;
    }
}

<?php

namespace Models\DAL;

use \Constants\Field;
use \Models\Manager\TransactionStatus;

class Transaction extends UniqueIdDal
{

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
        Field\Common::MERCHANT_ID,
        Field\Transaction::TOKEN_ID,
        Field\Transaction::STATUS,
        Field\Transaction::AUTH_AMOUNT,
        Field\Transaction::AMOUNT,
        Field\Transaction::CURRENCY,
        Field\Transaction::DESCRIPTION,
        Field\Transaction::EMAIL,
        Field\Transaction::CONTACT,
        Field\Transaction::UDF);

    protected $visible = array(
        Field\Transaction::ID,
        Field\Transaction::AMOUNT,
        Field\Transaction::CURRENCY,
        Field\Transaction::EMAIL,
        Field\Transaction::CONTACT,
        // 'livemode',
        Field\Transaction::STATUS,
        Field\Transaction::UDF,
        Field\Transaction::ERROR_CODE,
        Field\Transaction::ERROR_DESCRIPTION,
        Field\Common::CREATED_AT,
        Field\Common::UPDATED_AT);

    protected $guarded = array(Field\Transaction::ID);

    protected function getUdfAttribute($udf)
    {
        return unserialize($udf);
    }

    public function setUdfAttribute($value)
    {
        $this->attributes[Field\Transaction::UDF] = serialize($value);
    }

    public function setCaptureAmount($amount)
    {
        $this->setAttribute(Field\Transaction::AMOUNT, $amount);
    }

    public function setAuthAmount()
    {
        $authAmount = $this->getAttribute(Field\Transaction::AMOUNT);

        $this->setAttribute(Field\Transaction::AUTH_AMOUNT, $authAmount);
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
            throw new \InvalidArgumentException('$param not provided');
        }
        self::validateFetchParams($param);

        $cols = array();

        /*
         * Create the query.
         */
        $query = self::where(Field\Common::MERCHANT_ID, '=', $param['merchant_id'])
                     ->orderBy(Field\Common::UPDATED_AT, 'desc');

        if (isset($param['from']))
        {
            $query = $query->where(Field\Common::UPDATED_AT, '>=', $param['from']);
        }

        if (isset($param['to']))
        {
            $query = $query->where(Field\Common::UPDATED_AT, '<=', $param['to']);
        }

        if (isset($param['status']))
        {
            $query = $query->where(Field\Transaction::STATUS, '=', $param['status']);
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
        return ($this->getAttribute(Field\Transaction::STATUS) == TransactionStatus::AUTH);
    }

    public function isCaptured()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == TransactionStatus::CAPTURED);
    }

    public function isRefunded()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == TransactionStatus::REFUNDED);
    }

    public function isFailed()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == TransactionStatus::FAILED);
    }

    protected function isStatus($status)
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == $status);
    }

    public function setStatus($status)
    {
        $this->setAttribute(Field\Transaction::STATUS, $status);
    }

    public function setStatusAndSave($status)
    {
        $this->setAttribute(Field\Transaction::STATUS, $status);
        $this->save();
    }

    public function getObjectAttribute()
    {
        return 'transaction';
    }

    public function getMerchantId()
    {
        return (int)$this->getAttribute(Field\Common::MERCHANT_ID);
    }

    public function toArrayWithCard()
    {
        $data = parent::toArray();

        $token = $this->token()->first();

        if ($token === null)
        {
            throw new LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_TOKEN_NOT_FOUND);
        }

        $card = $token->card()->first();

        if ($card === null)
        {
            throw new LogicException(ErrorCode::SERVER_ERROR_ASSOCIATED_CARD_NOT_FOUND);
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
                    ->update(array(Field\Transaction::STATUS => 'auth'));
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
        $this->setAttribute(Field\Transaction::ERROR_CODE, $code);
        $this->setAttribute(Field\Transaction::ERROR_DESCRIPTION, $desc);
    }

    public static function findByIdAndMerchantId($id, $merchantId)
    {
        return static::where(Field\Common::MERCHANT_ID, $merchantId)
                     ->find($id);
    }

    public static function findByIdAndMerchantIdOrFailPublic($id, $merchantId)
    {
        $txn = static::where(Field\Common::MERCHANT_ID, $merchantId)
                     ->find($id);

        if ($txn === null)
        {
            $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        return $txn;
    }

    public static function loadWithTokenAndCard($id, $merchantId)
    {
        $txn = self::with('token')
                   ->merchantId($merchantId)
                   ->where(Field\Transaction::ID, '=', $id)
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
        return $query->where(Field\Common::MERCHANT_ID,'=',$merchantId);
    }

    public function toArrayTraceRelevant()
    {
        $data = $this->attributes;

        $relevantData = array();

        $fields = array(
            Field\Transaction::ID,
            Field\Common::MERCHANT_ID,
            Field\Transaction::TOKEN_ID,
            Field\Transaction::STATUS,
            Field\Transaction::AMOUNT,
            Field\Transaction::ERROR_CODE);

        $relevantData = array_intersect_key($data, array_flip($fields));

        return $relevantData;
    }
}

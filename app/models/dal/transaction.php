<?php

namespace Models\DAL;

use \Constants\Field;

class Transaction extends UuidDAL
{

    protected $table = \Constants\Table::TRANSACTION;

    private static $fetch_param_rules = array(
        'created'       => 'numeric',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required',
        'status'        => 'in:failed,captured,capture_failed,auth,open,refunded,settlement_sent,settled'
        );

    protected $fillable = array(
        Field\Common::MERCHANT_ID,
        Field\Transaction::TOKEN,
        Field\Transaction::STATUS,
        Field\Transaction::AMOUNT,
        Field\Transaction::CURRENCY,
        // 'hold',
        Field\Transaction::DESCRIPTION,
        Field\Transaction::UDF);

    protected $visible = array(
        Field\Transaction::ID,
        Field\Transaction::AMOUNT,
        Field\Transaction::CURRENCY,
        'livemode',
        Field\Transaction::STATUS,
        // 'hold',
        Field\Transaction::UDF,
        Field\Transaction::ERROR,
        Field\Common::CREATED_AT,
        Field\Common::UPDATED_AT
        );

    protected $guarded = array(Field\Transaction::ID);

    public function getUdfAttribute($udf)
    {
        return unserialize($udf);
    }

    public function setUdfAttribute($value)
    {
        $this->attributes[Field\Transaction::UDF] = serialize($value);
    }

    const FETCH_WITH_CARD       = 0x1024;
    const FETCH_WITH_TOKEN      = 0x2048;

    /**
     * Retrieves the transactions from database for a particular merchant.
     * @param  array $data
     * @param  int $flag
     * @return array $txn_list
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
                     ->orderBy('updated_at','desc');

        if (isset($param['created']))
        {
            $query->where(
                      Field\Common::CREATED_AT, 
                      '=', 
                      $param['created']);
        }
        else
        {
            if (isset($param['from']))
            {
                $query = $query->where(Field\Common::UPDATED_AT, '>', $param['from']);
            }

            if (isset($param['to']))
            {
                $query = $query->where(Field\Common::UPDATED_AT, '<', $param['to']);
            }
        }

        if(isset($param['status']))
        {
            $query = $query->where(Field\Common::STATUS, '=', $param['status']);
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

        return $query->with('card_token.card')->get();
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

    public static function fetchById($id)
    {
        return self::findOrFail2($id);
    }


    public function isProcessed()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == 'auth');
    }


    public function isCaptured()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == 'captured');
    }

    public function isRefunded()
    {
        return ($this->getAttribute(Field\Transaction::STATUS) == 'refunded');
    }

    public function setStatus($status)
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
        return (int)$this->getAttribute('merchant_id');
    }

    const WITH_CARD             = 0x256;

    public function toArrayEx($flag = 0x0)
    {
        $data = parent::toArray($flag);

        // TODO
        // const ONLY_PUBLIC_FIELDS is undefined
        // if ($flag and self::ONLY_PUBLIC_FIELDS)
        // {
        //     $array['id'] = $array['uid'];
        //     unset($array['uid']);
        // }

        if ($flag and self::WITH_CARD)
        {
            $token = $this->card_token()->first();
            $card_do = NULL;

            if ($this->token === null)
            {
                throw new \InvalidArgumentException('Transaction Exception: No card present');
            }

            if ($token !== null)
            {
                $card_do = $token->card()->first();
            }

            if ($card_do === null)
            {
                throw new \UnexpectedValueException('Transaction Exception: No card do present to fetch card data');
            }

            $card_data = $card_do->getCardData();

            $data['card'] = $card_data;
        }

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

    public function card_token()
    {
        return $this->hasOne('Models\DAL\CardToken', 'token', 'token');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\DAL\Merchant');
    }

    public function setError($error = false)
    {
        if(isset($error['code'])) 
            $this->setAttribute('error', $error['code']);
        else 
            $this->setAttribute('error', $error);
        
        $this->save();
        
        $this->error = $error;
    }
}

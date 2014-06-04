<?php

namespace Models\DAL;

use \Validator;

class Transaction extends UuidDAL
{

    protected $table = 'transactions';

    private static $fetch_param_rules = array(
        'created'       => 'numeric',
        'from_created'  => 'numeric',
        'to_created'    => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required',
        'status'        => 'in:failed,captured,capture_failed,auth,open,refunded,settlement_sent,settled'
        );

    protected $fillable = array(
        'merchant_id',
        'token',
        'status',
        'amount',
        'currency',
        // 'hold',
        'description',
        'udf',
        );

    protected $visible = array(
        'id',
        'amount',
        'currency',
        'livemode',
        'status',
        // 'hold',
        'udf',
        'error',
        'created_at',
        'updated_at'
        );

    protected $guarded = array('id');

    public function getUdfAttribute($udf)
    {
        return unserialize($udf);
    }

    public function setUdfAttribute($value)
    {
        $this->attributes['udf'] = serialize($value);
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
        $query = self::where('merchant_id', '=', $param['merchant_id']);

        if (isset($param['created']))
        {
            $query->where('created_at', '=', $param['created']);
        }
        else
        {
            if (isset($param['from_created']))
            {
                $query = $query->where('created_at', '>', $param['from_created']);
            }

            if (isset($param['to_created']))
            {
                $query = $query->where('created_at', '<', $param['to_created']);
            }
        }

        if(isset($param['status']))
        {
            $query = $query->where('status', '=', $param['status']);
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

    public static function fetchById($id = null)
    {
        if (! (null === $id))
            return self::findOrFail($id);
        else
            throw new \InvalidArgumentException('Transaction Exception: No transaction id present');
    }


    public function getProcessed()
    {
        return ($this->getAttribute('status')=='auth');
    }


    public function getCaptured()
    {
        return ($this->getAttribute('status')=='captured');
    }

    public function getRefunded()
    {
        return ($this->getAttribute('status')=='refunded');
    }


    public function setStatus($status)
    {
        $this->setAttribute('status', $status);
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
                    ->update(array('status' => 'auth'));
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
        if(isset($error['code'])) $this->setAttribute('error', $error['code']);
        else $this->setAttribute('error', $error);
        $this->save();
        $this->error = $error;
    }

    // public function getHold()
    // {
    //     return ($this->getAttribute('hold') == '1');
    // }

    // public function setCapturable($status = true)
    // {
    //     $this->capturable = $status;
    // }

    // public function unsetAndGetCapturable()
    // {
    //     $capturable = (isset($this->capturable)) ? $this->capturable : false;
    //     unset($this->capturable);
    //     return $capturable;
    // }

}

<?php 

namespace Models\DAL;

use \Validator;

class Transaction extends UuidDAL
{

    protected $table = 'transactions';

    private static $fetch_params_rules = array(
        'created'       => 'numeric|required_without:from_created,to_created',
        'created_gt'    => 'numeric|required_without:created',
        'created_lt'    => 'numeric|required_without:created',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required');

    protected $fillable = array(
        'merchant_id',
        'token',
        'status',
        'amount',
        'currency',
        'processed',
        'desc',
        'udf',
        );

    protected $visible = array(
        'id',
        'amount',
        'currency',
        'livemode',
        'processed',
        'captured',
        'refunded',
        'udf',
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
        if (! is_int($flag))
        {
            throw new \InvalidArgumentException('$flag is not an integer');
        }

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
            $query->where('created_at', '=', $created);
        }
        else
        {
            if (isset($param['from_created'])) 
            {
                $from_created = $param['from_created'];
            
                $txn_query = $txn_query->where('created_at', '>', $from_created);
            }
            
            if (isset($param['to_created']))
            {
                $to_created = $param['to_created'];
            
                $txn_query = $txn_query->where('created_at', '<', $to_created);
            }
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

        return $query->with('cardToken', 'card')->get();
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

    public static function fetchById($id = NULL)
    {
        if (! (NULL === $id))
            return self::findOrFail($id);
        else
            throw new \InvalidArgumentException('No transaction id present');
    }

    public static function fetchUncaptured($from_time = 86400)
    {
        return self::whereRaw('created_at > ? AND captured = 0 AND processed = 1', array(time() - $from_time))->get();
    }

    public function getProcessed()
    {
        return $this->getAttribute('processed');
    }

    public function setProcessed($processed)
    {
        $this->setAttribute('processed', (int)$processed);
        $this->save();
    }

    public function getCaptured()
    {
        return $this->getAttribute('captured');
    }

    public function setCaptured($captured)
    {
        $this->setAttribute('captured', $captured);
        $this->save();
    }

    public function getRefunded()
    {
        return $this->getAttribute('refunded');
    }

    public function setRefunded($refunded)
    {
        $this->setAttribute('refunded', $refunded);
        $this->save();
    }

    public function updateStatus($status)
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
                throw new \InvalidArgumentException('No card present');
            }

            if ($token !== null)
            {
                $card_do = $token->card()->first();
            }
            
            if ($card_do === null)
            {
                throw new \UnexpectedValueException('No card do present to fetch card data');
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
                    ->update(array('processed' => 1));
    }

    public function card_token()
    {
        return $this->hasOne('Models\DAL\CardToken', 'token', 'token');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\DAL\Merchant');
    }

}

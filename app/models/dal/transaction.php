<?php 

namespace Models\DAL;

use \Validator;

class Transaction extends UuidDAL
{

	protected $table = 'transactions';

	private static $fetch_params_rules = array(
        'created'  		=> 'numeric|required_without:from_created,to_created',
        'created_gt'    => 'numeric|required_without:created',
        'created_lt'    => 'numeric|required_without:created',
        'count'    		=> 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'	=> 'required');

    protected $fillable = array(
        'merchant_id',
        'token',
        'status',
        'amount',
        'currency',
        'processed',
        'desc',
        'udf');
//        'refund',);

    protected $guarded = array('id');

    public function getUdfAttribute($udf)
    {
    	return unserialize($udf);
    }

    public function setUdfAttribute($value)
    {
    	$this->attributes['udf'] = serialize($value);
    }

	const FETCH_WITH_CARD		= 0x1024;
	const FETCH_WITH_TOKEN		= 0x2048;

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

    public function getProcessed()
    {
        return $this->getAttribute('processed');
    }

    public function setProcessed($processed)
    {
        $this->setAttribute('processed', $processed);
    }

    public function getObjectAttribute()
    {
    	return 'transaction';
    }

    const WITH_CARD             = 0x256;

    public function toArrayEx($flag = 0x0)
    {
    	$array = parent::toArray($flag);

        if ($flag & self::ONLY_PUBLIC_FIELDS)
        {
            $array['id'] = $array['uid'];
            unset($array['uid']);
        }

        if ($flag & self::WITH_CARD)
        {
            $card_do = null;

            if ((($this->card_token_do === null) or
                 ($this->card_token_do->get_card_do() === null)) and
                ($this->card_do === null))
            {
                throw new \InvalidArgumentException('No card present');
            }

            if ($this->card_token_do !== null)
            {
                $card_do = $this->card_token_do->get_card_do();
            }
            
            if (($card_do === null) and 
                ($this->card_do !== null))
            {
                $card_do = $this->card_do;
            }

            if ($card_do === null)
            {
                throw new \UnexpectedValueException('No card do present to fetch card data');
            }

            $card_data = $card_do->toArray($flag);

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

}

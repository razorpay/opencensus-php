<?php 

namespace DataMapper;

use DomainObject\Transaction as TransactionDO;
use \DB;
use \ERR;

class Transaction {

	const table = 'transactions';

	/**
	 * attributes which can be set by us in db during insert.
	 */
	private static $attr_insert = array(
		'uid',
		'merchant_id',
		'token',

		'amount',
		'currency',
		'processed',
		'desc'
		);

	private static $attr_required = array(
		'uid',
		'merchant_id',
		'token',
		'amount',
		'currency',
		'desc'
		);

	private static $attr_update = array(
		'decline_code',
		'decline_message',
		'processed',
		'refund'
		);

	private static $fetch_params_keys = array(
		'merchant_id',
		'created',
		'from_created',
		'to_created',
		'count',
		'offset'
		);

	private static $fetch_params_rules = array(
        'merchant_id'   => 'required|integer',
        'created'  		=> 'integer',
        'from_created'  => 'integer',
        'to_created'    => 'integer',
        'count'    		=> 'integer|max:100',
        'offset'     	=> 'integer'
        );

	private static $attr_db = array(
		'id',
        'uid',
        'merchant_id',
        'token',
        'amount',
        'currency',
        'processed',
        'desc',
        'refund',
        'created_at',
        'updated_at');

	private $row = array();

	private $fetch_params = null;

	private $fetch_params_verified = false;

	public function insert(TransactionDO $txn_do)
	{
		$data = $txn_do->get_transaction_data();

		foreach ($data as $key=>$value)
		{
			if (!in_array($key, self::$attr_insert))
			{
				continue;
			}

			if (($value === null) or 
				($value === ''))
			{
				if (in_array($key, self::$attr_required))
				{
					throw new \InvalidArgumentException($key);
				}
			}
			else
			{
				$this->row[$key] = $value;
			}
		}

		try
		{
			$id = DB::table(self::table)
					->insert_get_id($this->row);
			$txn_do->set_id((int) $id);
		}
		catch(Exception $e)
		{
			var_dump($e);
			return ERR::DB_PROBLEM;
		}

		return ERR::SUCCESS;
	}


	const FETCH_DEFAULT 		= 0x0;
	const FETCH_WITH_CARD		= 0x1;
	const FETCH_WITH_TOKEN		= 0x2;

	/**
	 * Retrieves the transactions from database for a particular merchant.
	 * @param  array $data
	 * @param  array $error
	 * @return array $txn_list
	 */
	public static function fetch($param, $flag = 0x0)
	{
		if (is_int($flag) === false)
		{
			throw new \InvalidArgumentException("$flag is not an integer");
		}

		if (($this->fetch_params === null) and
			($param === null))
			throw new \InvalidArgumentException("$param not provided");

		if ($this->fetch_params === null)
		{
			$err = self::validate_fetch_params($param);

			if ($err !== ERR::SUCCESS)
			{
				throw new \InvalidArgumentException("Parameters provided for fetching transaction data is invalid.");
			}
		}
		else if ($this->fetch_params_verified === false)
		{
			throw new \InvalidArgumentException("Parameters provided for fetching transaction data is invalid.");
		}
		else
		{
			$param = $this->fetch_params;
		}
		

		/*
		 * Create the fluent query.
		 */
		$query = DB::table(self::table);

		$cols = array();

		if (isset($param['merchant_id']))
		{
			$query->where('merchant_id', '=', $param['merchant_id']);
		}

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

		if (isset($param['offset']))
		{
			$query->skip($param['offset']);
		}

		if ($flag === self::FETCH_DEFAULT)
		{
			$cols = self::$attr_db;
		}
		else
		{
			$cols = self::table_cols_aliasing_default();
		}

		if ($flag & self::FETCH_WITH_TOKEN)
		{
			$cols = array_merge($cols, CardToken::table_cols_aliasing_default());

			$query->join(CardToken::table, self::table.'.'.'token', '=', CardToken::table.'.'.'token');
		}

		if ($flag & self::FETCH_WITH_CARD)
		{
			$cols = array_merge($cols, Card::table_cols_aliasing_default());

			if (!($flag & self::FETCH_WITH_TOKEN))
			{
				$query->join(CardToken::table, self::table.'.'.'token', '=', CardToken::table.'.'.'token');		
			}

			$query->join(Card::table, CardToken::table.'.'.'card_id', '=', Card::table.'.'.'id');
		}

		$dataset = $query->get($cols);

		$num = count($result);

		if ($num !== $count)
		{
			; // do something here
		}

		$txn_do_arr 	= array();
		$token_do_arr 	= array();
		$card_do_arr 	= array();

		if ($flag === FETCH_DEFAULT)
		{
			;
		}
		else
		{
			$txn_do_arr = bulk_load($dataset, self::$attr_db, 'TransactionDO', self::table.'_');
		}

		if ($flag & self::FETCH_WITH_TOKEN)
		{
			$token_do_arr = bulk_load($dataset, CardToken::get_attr_db(), 'CardTokenDO', CardToken::table);

			for ($i = 0; $i < $count; $i++)
			{
				$txn_do_arr[$i]->set_token_do($token_do_arr[$i]);
			}
		}

		if ($flag & self::FETCH_WITH_CARD)
		{
			$card_do_arr = bulk_load($dataset, Card::get_attr_db(), 'CardDO', Card::table);
		}

		return $txn_list;
	}

	private function validate_fetch_params(array $param)
	{
		$param_keys = array_keys($this->data);
        $invalid_keys = array_diff($param_keys, self::$fetch_params);

        if (count($invalid_keys) !== 0)
        {
        	return ERR::invalid_keys($invalid_keys);
        }

        $validation = Validator::make($param, self::$fetch_params_rules);

        if ($validation->fails()) 
        {
            return ERR::invalid_parameters($validation->errors->all());
        }
        
        if ((isset($param['created'])) and
        	((isset($param['from_created']) or
        	 (isset($param['to_created'])))))
        {
        	; // throw error here
        }

        if (isset($param['created']))
        {
        	; // do few things here
        }

        if (isset($param['from_created']))
        {
        	;
        }

        if (isset($param['to_created']))
        {
        	;
        }

        if (isset($param['offset']))
        {
        	;
        }

        $this->fetch_params_verified = true;

        return ERR::SUCCESS;
	}

	private static function check_timestamp(&$timestamp)
	{
		$timestamp = (int) $timestamp;
		if ($timestamp < 0)
			$timestamp = 0; // @todo: provide a better default.
		else if ($timestamp > time())
			$timestamp = time(); //@todo: consider what to put as max?
		return true;
	}

}

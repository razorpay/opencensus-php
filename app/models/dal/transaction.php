<?php 

namespace Models\DAL;

use \Config;
use \PDO;
use Models\DO;
use \DB;
use \ERR;
use \Validator;

class Transaction extends DataMapper
{

	const table = 'transactions';

	private static $fetch_params_rules = array(
        'created'  		=> 'numeric|required_without:from_created,to_created',
        'from_created'  => 'numeric|required_without:created',
        'to_created'    => 'numeric|required_without:created',
        'count'    		=> 'numeric|max:100',
        'offset'     	=> 'numeric'
        'merchant_id'	=> 'required'
        );

	protected static $attributes = array(
		'id' => 'db',
        'uid' => 'db|insert_req',
        'merchant_id' => 'db|insert_req',
        'token' => 'db|insert_req',
        'amount' => 'db|insert_req',
        'currency' => 'db|insert_req',
        'processed' => 'db|update_req|insert',
        'desc' => 'db|insert_req',
//        'refund',
        'decline_code' => 'db|update_req',
        'decline_message' => 'db|update_req'
        'created_at' => 'db',
        'updated_at' => 'db');

	protected static $timestamps = true;

	protected static $primaryAutoGenerate = true;

	private $row = array();

	private $fetch_params = null;

	private $fetch_params_verified = false;

	const FETCH_DEFAULT 		= 0x0;
	const FETCH_WITH_CARD		= 0x1;
	const FETCH_WITH_TOKEN		= 0x2;

	/**
	 * Retrieves the transactions from database for a particular merchant.
	 * @param  array $data
	 * @param  int $flag
	 * @return array $txn_list
	 */
	public function fetch($param, $flag = 0x0)
	{
		if (! is_int($flag))
		{
			throw new \InvalidArgumentException('$flag is not an integer');
		}

		if ($param === null)
			throw new \InvalidArgumentException('$param not provided');

		self::validateFetchParams($param);

		/*
		 * Create the fluent query.
		 */
		$query = DB::table(self::table);

		$cols = array();

		$query->where('merchant_id', '=', $param['merchant_id']);

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


		$dataset = $do = $keys = array();

		if ($flag === self::FETCH_DEFAULT)
		{
			$cols = self::$attr_db;

			$do = 'Transaction';

			$dataset = $query->get($cols);
		}
		else
		{
			$keys = array();

			$do	= array();

			$cols = static::array_prefix_table_name();

			$keys['Transaction'] = static::get_attr_db();

			array_push($do, 'Transaction');

			if ($flag & self::FETCH_WITH_TOKEN)
			{
				$cols = array_merge($cols, CardToken::array_prefix_table_name());

				$keys['CardToken'] = CardToken::get_attr_db();

				array_push($do, 'CardToken');

				$query->join(CardToken::table, self::table.'.'.'token', '=', CardToken::table.'.'.'token');
			}

			if ($flag & self::FETCH_WITH_CARD)
			{
				$cols = array_merge($cols, Card::array_prefix_table_name());

				$keys['Card'] = Card::get_attr_db();

				array_push($do, 'Card');

				if (!($flag & self::FETCH_WITH_TOKEN))
				{
					$query->join(CardToken::table, self::table.'.'.'token', '=', CardToken::table.'.'.'token');		
				}

				$query->join(Card::table, CardToken::table.'.'.'card_id', '=', Card::table.'.'.'id');
			}

			Config::set('database.fetch', PDO::FETCH_NUM);
			$dataset = $query->get($cols);
			Config::set('database.fetch', PDO::FETCH_ASSOC);

		}

		$num = count($dataset);

		if (isset($param['count']) and $num !== $param['count'])
		{
			; // do something here
		}

		$txn_do_arr 	= array();

		if ($flag === self::FETCH_DEFAULT)
		{
			$txn_do_arr = static::bulkLoad($dataset, 'Transaction');
		}
		else
		{
			$token_do_arr 	= array();
			$card_do_arr 	= array();
		
			$do_obj_arr = static::bulk_load($dataset, $do, $keys);

			$txn_do_arr = $do_obj_arr['Transaction'];

			if ($flag & self::FETCH_WITH_CARD)
			{
				$card_do_arr = $do_obj_arr['Card'];

				if (!($flag &self::FETCH_WITH_TOKEN))
				{
					for ($i = 0; $i < $num; $i++)
					{
						$txn_do_arr[$i]->set_card_do($card_do_arr[$i]);
					}
				}
			}

			if ($flag & self::FETCH_WITH_TOKEN)
			{
				$token_do_arr = $do_obj_arr['CardToken'];

				for ($i = 0; $i < $num; $i++)
				{
					$txn_do_arr[$i]->set_token_do($token_do_arr[$i]);

					if ($flag & self::FETCH_WITH_CARD)
					{
						$token_do_arr[$i]->set_card_do($card_do_arr[$i]);
					}
				}
			}
		}

		return $txn_do_arr;
	}

	public function validateFetchParams(array $param)
	{
		$this->fetch_params = $param;

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

}

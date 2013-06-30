<?php 

namespace DataMapper;

use DomainObject\Transaction as TransactionDO;
use \DB;
use \ERR;

class Transaction {

	const $table = 'transactions';

    /**
     * attributes which can be set by us in db during insert.
     */
    private static $attr_insert = array(
        'txn_id',
        'merchant_id',
        'token_id',

        'amount',
        'currency',
        'processed',
        'desc',
        'refund'
        );

    private $row = array();

    private static $attr_update = array(
        'decline_code',
        'decline_message',
        'processed',
        'refund'
        );

    public function insert(TransactionDO $txn_do)
    {
    	$data = $txn_do->get_txn_data();

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
    		$id = DB::table(self::$table)
    				->insert_get_id($this->row);
    		$txn_do->set_id((int) $id);
    	}
    }

	/**
	 * Retrieves the transactions from database for a particular merchant.
	 * @param  array $data
	 * @param  array $error
	 * @return array $txn_list
	 */
	public static function retrieve($data, &$error)
	{
		$txn_query = Transaction::where('merchant_id', '=', $data['merchant_id']);

		if (isset($data['created']))
		{
			$created = $data['created'];
			Transaction::rectify_timestamp($created);
			
			$txn_query = $txn_query->where('created_at', '=', $created);
		}
		else
		{
			if (isset($data['from_created'])) 
			{
				$from_created = $data['from_created'];
				Transaction::rectify_timestamp($from_created);

				$txn_query = $txn_query->where('created_at', '>', $from_created);
			}

			if (isset($data['to_created']))
			{
				$to_created = $data['to_created'];
				Transaction::rectify_timestamp($to_created);

				$txn_query = $txn_query->where('created_at', '<', $to_created);
			}
		}

		if (isset($data['count']))
		{
			$count = (int) $data['count'];
			if ($count > 100)
				$count = 100;
			else if ($count < 0)
				$count = 10;
			
			$txn_query = $txn_query->take($count);
		}
		else
		{
			$txn_query = $txn_query->take(10);
		}

		$txn_list = $txn_query->get();

		return $txn_list;
	}

	private static function rectify_timestamp(&$timestamp)
	{
		$timestamp = (int) $timestamp;
		if ($timestamp < 0)
			$timestamp = 0; // @todo: provide a better default.
		else if ($timestamp > time())
			$timestamp = time(); //@todo: consider what to put as max?
		return true;
	}

}

?>
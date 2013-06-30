<?php namespace DataMapper;

	class Merchant extends \Eloquent {

		public static $hidden = array('id','pwd','hash');

		public function transactions()
		{
			return $this->has_many('Transaction');
		}

		public function keys()
		{
			return $this->has_many('Keys');
		}

	}

?>
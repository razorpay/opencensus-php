<?php 

namespace DataMapper;

use DB;
use ERR;

	class Merchant extends \Eloquent {

		protected $hidden = array('id','pwd','hash');

		// public function transactions()
		// {
		// 	return $this->hasMany(
		// 		__NAMESPACE__.'\Transaction');
		// }

		public function keys()
		{
			return $this->hasMany(
				__NAMESPACE__.'\Keys');
		}

		public function transactions()
		{
			return DB::table('transactions')
					 ->where('merchant_id', '=', $this->id)
					 ->get();
		}
	}

?>
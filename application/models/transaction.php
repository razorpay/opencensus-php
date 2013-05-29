<?php

	class Transaction extends Eloquent {

		public $includes = array('card');

		public static $hidden = array('id','merchant_id','card_id');

		private static $rules = array(
			'amount' => 'required|numeric',
			'card' => 'required|match:/crd_[0-9a-z]{28}/'
		);

		protected $guarded = array('id','amount','card_id');

		public function card()
		{
			return $this->belongs_to('Card');
		}

		public function buildTransaction ($input, $merchant)
		{
			$validation = Validator::make($input, static::$rules);
			if ($validation->fails()) {
				return $validation->errors;
			}

			$card = Card::where('token','=',$input['card'])->first();
			if ($card === NULL) {
				echo Response::error('404');
				die();
			}
			else
				$card_id = (int)$card->id;

			$this->setAttr('merchant_id',$merchant);
			$this->setAttr('amount',(float)$input['amount']);
			$this->setAttr('card_id',$card_id);
			$this->setAttr('token',self::generateTransactionToken());
			
			try {
				$this->save();
			}
			catch (\Exception $e) {
				echo Response::error('500');
				die();
			}
		}

		public function setAttr($key, $value)
		{
			$this->{$key} = $value;
		}

		public function getMerchant ()
		{
			return (int)$this->merchant_id;
		}

		public static function generateTransactionToken ()
		{
			return 'txn_' . substr ( bin2hex ( openssl_random_pseudo_bytes(16) ), 0, 28);
		}

	}

?>
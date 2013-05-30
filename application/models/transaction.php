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

		public function validate_attributes ($input)
		{
			$validation = Validator::make($input, static::$rules);
			if ($validation->fails()) {
				return Response::json($validation->errors);
			}
		}

		public function build_transaction ($input, $merchant)
		{
			$e = $this->validate_attributes ($input);
			if ($e !== NULL)
				return $e;

			$card = CardToken::where('token','=',$input['card'])->first();
			if ($card === NULL) {
				return Response::error('404');
			}
			else {
				if ($card->expired === '1') {
					return Response::error('400');
				}
				CardToken::where('card_id','=',$card->card_id)->update(array('expired'=>1));
			}
			$this->set_attr('merchant_id',$merchant);
			$this->set_attr('amount',(float)$input['amount']);
			$this->set_attr('card_id',(int)$card->card_id);
			$this->set_attr('token',self::generate_transaction_token());
			
			try {
				$this->save();
			}
			catch (\Exception $e) {
				return Response::error('500');
			}
		}

		public function set_attr($key, $value)
		{
			$this->{$key} = $value;
		}

		public function get_merchant ()
		{
			return (int)$this->merchant_id;
		}

		public static function generate_transaction_token ()
		{
			return 'txn_' . substr ( bin2hex ( openssl_random_pseudo_bytes(16) ), 0, 28);
		}

	}

?>
<?php

	class Transaction extends Eloquent {

		public $includes = array('card');

		public static $hidden = array('id','merchant_id','card_id');

		private static $rules = array(
			'amount' => 'required|integer',
			'card' => 'required|match:/crd_[0-9a-z]{28}/'
		);

		protected $guarded = array('id','amount','card_id');

		public function card()
		{
			return $this->belongs_to('Card');
		}

		public function validate_attributes($input)
		{
			$validation = Validator::make($input, static::$rules);
			if ($validation->fails()) {
				return Response::json($validation->errors);
			}
		}

		public function build($input, $merchant)
		{
			$e = $this->validate_attributes ($input);
			if (!is_null($e))
				return $e;

			$card = CardToken::where('token','=',$input['card'])->first();
			if (is_null($card)) {
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
<?php

	class Card extends Eloquent {

		public static $hidden = array('id','cardtype_id','user_id');

		private static $rules = array(
			'number' => 'required|match:/[0-9]{16}/',
			'expiry_month' => 'required|match:/[0-9]{2}/',
			'expiry_year' => 'required|match:/[0-9]{4}/',
			'cvv' => 'required|match:/[0-9]{3}/',
			'name' => 'required|match:/[a-zA-Z *]/'
		);

		public function transactions()
		{
			return $this->has_many('Transaction');
		}

		public function cardtokens()
		{
			return $this->has_many('CardToken');
		}

		public function validate_attributes ($input)
		{
			$validation = Validator::make($input, static::$rules);
			if ($validation->fails()) {
				return Response::json($validation->errors);
			}
		}

		public function build_card ($input)
		{
			$this->set_attr('number',$input['number']);
			$this->set_attr('expiry_month',$input['expiry_month']);
			$this->set_attr('expiry_year',$input['expiry_year']);
			$this->set_attr('cvv',$input['cvv']);
			$this->set_attr('name',strtoupper($input['name']));

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

	}

?>
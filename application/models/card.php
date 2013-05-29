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

		public function buildCard ($input)
		{
			$validation = Validator::make($input, static::$rules);
			if ($validation->fails()) {
				return $validation->errors;
			}

			$this->setAttr('number',$input['number']);
			$this->setAttr('expiry_month',(int)$input['expiry_month']);
			$this->setAttr('expiry_year',(int)$input['expiry_year']);
			$this->setAttr('cvv',(int)$input['cvv']);
			$this->setAttr('name',strtoupper($input['name']));

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

	}

?>
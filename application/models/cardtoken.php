<?php

	class CardToken extends Eloquent {

		public $includes = array('card');

		public static $hidden = array('id','card_id','expired');

		public function transactions()
		{
			return $this->has_many('Transaction');
		}

		public function card()
		{
			return $this->belongs_to('Card');
		}

		public function buildCardToken ($card_id)
		{
			$this->setAttr('card_id',(int)$card_id);
			$this->setAttr('token',self::generateCardToken());
			$this->setAttr('expired',0);

			try {
				$this->save();
			}
			catch (\Exception $e) {
				return Response::error('500');
			}
		}

		public static function generateCardToken ()
		{
			return 'crd_' . substr ( bin2hex ( openssl_random_pseudo_bytes(16) ), 0, 28);
		}

		public function setAttr($key, $value)
		{
			$this->{$key} = $value;
		}

	}

?>
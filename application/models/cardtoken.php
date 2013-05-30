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

		public function build_cardtoken ($card_id)
		{
			$this->set_attr('card_id',(int)$card_id);
			$this->set_attr('token',self::generate_card_token());
			$this->set_attr('expired',0);

			try {
				$this->save();
			}
			catch (\Exception $e) {
				return Response::error('500');
			}
		}

		public static function generate_card_token ()
		{
			return 'crd_' . substr ( bin2hex ( openssl_random_pseudo_bytes(16) ), 0, 28);
		}

		public function set_attr($key, $value)
		{
			$this->{$key} = $value;
		}

	}

?>
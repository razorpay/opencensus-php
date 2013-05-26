<?php

	class Card extends Eloquent {

		public static $hidden = array('id','cardtype_id','user_id');

		public function transactions()
		{
			return $this->has_many('Transaction');
		}

	}

?>
<?php

class CardType
{
    // No objects for this class.
    private function __construct() {}

	/**
	 * Determines type of credit card.
	 * @param [type] $card_no [description]
	 * @return string $type "one of {MasterCard, Visa, American Express, Diners Club, 
	 *                       Carte Blanche, Discover, EnRoute, JCB}"
	 */
	public static function credit_card($card_no)
	{
		return 'visa';
	}

	public static function debit_card($card_no)
	{
		;
	}

    /**
     * Returns the two letter country code from where the card was issued.
     * BTW, I have no idea how to figure that out! @todo: implement CardType::country
     */
    public static function country($card_no)
    {
        return "IN";
    }
}
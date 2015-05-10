<?php

namespace Tests\Functional\Fixtures\Entity;

class CardDetail extends Base
{
    protected $items = array(
        array(
            'iin'       => '400040',
            'category'  => 'CORPORATE T&E',
            'network'   => 'Visa',
            'type'      => 'debit',
            'country'   => 'IN',
            'issuer'    => 'STATE BANK OF INDI',
            'trivia'    => 'random',
        ),
        array(
            'iin'       => '502165',
            'category'  => null,
            'network'   => 'Maestro',
            'type'      => 'debit',
            'country'   => 'IN',
            'issuer'    => null,
            'trivia'    => 'random',
            ),
        array(
            'iin'       => '502166',
            'category'  => null,
            'network'   => 'Maestro',
            'type'      => 'debit',
            'country'   => 'IN',
            'issuer'    => null,
            'trivia'    => 'random',
            ),
        array(
            'iin'       => '549752',
            'category'  => 'STANDARD',
            'network'   => 'MasterCard',
            'type'      => 'credit',
            'country'   => 'IN',
            'issuer'    => 'PUNJAB NATIONAL BANK',
            'trivia'    => 'random trivia'
            ),
        array(
            'iin'       => '607002',
            'category'  => 'STANDARD',
            'network'   => 'Rupay',
            'type'      => 'debit',
            'country'   => 'IN',
            'issuer'    => 'PUNJAB NATIONAL BANK',
            'trivia'    => 'random trivia'
            ),
        );

    public function createDefaultIins()
    {
        $items = $this->items;

        $iins = [];
        foreach ($items as $attributes)
        {
            $iins[] = $this->fixtures->create('card_detail', $attributes);
        }

        return $iins;
    }
}
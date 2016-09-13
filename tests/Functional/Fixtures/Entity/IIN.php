<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Iin extends Base
{
    protected $items = array(
        array(
            'iin'           => '400040',
            'category'      => 'CORPORATE T&E',
            'network'       => 'Visa',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => 'STATE BANK OF INDI',
            'trivia'        => 'random',
        ),
        array(
            'iin'           => '502165',
            'category'      => null,
            'network'       => 'Maestro',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => null,
            'trivia'        => 'random',
            ),
        array(
            'iin'           => '502166',
            'category'      => null,
            'network'       => 'Maestro',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => null,
            'trivia'        => 'random',
            ),
        array(
            'iin'           => '549752',
            'category'      => 'STANDARD',
            'network'       => 'MasterCard',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'PUNJAB NATIONAL BANK',
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '559300',
            'network'       => 'MasterCard',
            'type'          => 'credit',
            ),
        array(
            'iin'           => '607002',
            'category'      => 'STANDARD',
            'network'       => 'RuPay',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => 'PUNJAB NATIONAL BANK',
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '607500',
            'category'      => 'STANDARD',
            'network'       => 'RuPay',
            'type'          => 'debit',
            'country'       => 'IN',
            'issuer_name'   => 'PUNJAB NATIONAL BANK',
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '414767',
            'category'      => 'STANDARD',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'KOTAK',
            'issuer'        => 'HDFC',
            'emi'           => 1,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '401200',
            'category'      => 'STANDARD',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'KOTAK',
            'issuer'        => 'HDFC',
            'emi'           => 1,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '401201',
            'category'      => 'STANDARD',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'US',
            'issuer_name'   => 'JP MORGAIN CHASE',
            'issuer'        => null,
            'emi'           => 0,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '428095',
            'category'      => 'CLASSIC',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'KOTAK MAHINDRA BANK, LTD.',
            'issuer'        => 'KKBK',
            'emi'           => 1,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '411146',
            'category'      => 'CLASSIC',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'AXIS BANK, LTD.',
            'issuer'        => 'UTIB',
            'emi'           => 1,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '411111',
            'category'      => 'CLASSIC',
            'network'       => 'Visa',
            'type'          => 'credit',
            'country'       => 'IN',
            'issuer_name'   => 'AXIS BANK, LTD.',
            'issuer'        => 'HDFC',
            'emi'           => 1,
            'trivia'        => 'random trivia'
            ),
        array(
            'iin'           => '424512',
            'category'      => 'ELECTRON',
            'network'       => 'Visa',
            'type'          => 'debit',
            'country'       => 'ZA',
            'issuer_name'   => 'CAPITEC BANK, LTD.',
            'issuer'        => null,
            'emi'           => 0,
            'trivia'        => null,
            ),
        );

    public function createDefaultIins()
    {
        $items = $this->items;

        $iins = [];

        $time = time();

        foreach ($items as $attributes)
        {
            $attributes['created_at'] = $time;
            $attributes['updated_at'] = $time;

            $iins[] = $this->fixtures->create('iin', $attributes);
        }

        return $iins;
    }
}

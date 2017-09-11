<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Iin extends Base
{
    protected $items = [
            [
                'iin'           => '400040',
                'category'      => 'CORPORATE T&E',
                'network'       => 'Visa',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => 'STATE BANK OF INDI',
                'trivia'        => 'random',
            ],
            [
                'iin'           => '502165',
                'category'      => null,
                'network'       => 'Maestro',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => null,
                'trivia'        => 'random',
            ],
            [
                'iin'           => '502166',
                'category'      => null,
                'network'       => 'Maestro',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => null,
                'trivia'        => 'random',
            ],
            [
                'iin'           => '549752',
                'category'      => 'STANDARD',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'PUNJAB NATIONAL BANK',
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '559300',
                'network'       => 'MasterCard',
                'type'          => 'credit',
            ],
            [
                'iin'           => '607002',
                'category'      => 'STANDARD',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => 'PUNJAB NATIONAL BANK',
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '607500',
                'category'      => 'STANDARD',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => 'PUNJAB NATIONAL BANK',
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '414767',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'KOTAK',
                'issuer'        => 'HDFC',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '401200',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'KOTAK',
                'issuer'        => 'HDFC',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '401201',
                'category'      => 'STANDARD',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'US',
                'issuer_name'   => 'JP MORGAIN CHASE',
                'issuer'        => null,
                'emi'           => 0,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '428095',
                'category'      => 'CLASSIC',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'KOTAK MAHINDRA BANK, LTD.',
                'issuer'        => 'KKBK',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '411146',
                'category'      => 'CLASSIC',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'AXIS BANK, LTD.',
                'issuer'        => 'UTIB',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '411111',
                'category'      => 'CLASSIC',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'AXIS BANK, LTD.',
                'issuer'        => 'HDFC',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '424512',
                'category'      => 'ELECTRON',
                'network'       => 'Visa',
                'type'          => 'debit',
                'country'       => 'ZA',
                'issuer_name'   => 'CAPITEC BANK, LTD.',
                'issuer'        => null,
                'emi'           => 0,
                'trivia'        => null,
            ],
            [
                'iin'           => '414772',
                'category'      => 'SIGNATURE',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'INDUSIND BANK, LTD.',
                'issuer'        => 'INDB',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '524373',
                'category'      => 'PLATINUM',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'THE RATNAKAR BANK LIMITED',
                'issuer'        => 'RATN',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '402874',
                'category'      => 'INFINITE',
                'network'       => 'Visa',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'STANDARD CHARTERED BANK',
                'issuer'        => 'SCBL',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '407651',
                'category'      => 'PLATINUM',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'ICICI Bank',
                'issuer'        => 'ICIC',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
            [
                'iin'           => '531849',
                'category'      => 'PLATINUM',
                'network'       => 'MasterCard',
                'type'          => 'credit',
                'country'       => 'IN',
                'issuer_name'   => 'YES Bank',
                'issuer'        => 'YESB',
                'emi'           => 1,
                'trivia'        => 'random trivia'
            ],
        ];

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

<?php


namespace RZP\Tests\Functional\Mpan;


trait MpanTrait
{
    private function getMpanData()
    {
        return [
            [
                'mpan'    => '4604901005005799',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005005823',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005005856',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005005880',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005005914',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005005971',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005006003',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005006037',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005006060',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '4604901005006094',
                'network' => 'Visa'
            ],
            [
                'mpan'    => '5122600005005789',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005813',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005847',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005870',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005904',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005961',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005005995',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005006027',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '5122600005006050',
                'network' => 'MasterCard'
            ],
            [
                'mpan'    => '6100020005005792',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005005826',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005005859',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005005883',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005005917',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005005974',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005006006',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => '6100020005006030',
                'network' => 'RuPay'
            ],
            [
                'mpan'    => 'NDEwNDkwMTAwNTAwNTgyMw==', // to test tokenization cron, len != 16 means it is tokenized
                'network' => 'RuPay'
            ]
        ];
    }
}

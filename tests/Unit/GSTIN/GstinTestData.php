<?php

return [
    'testValidGstin' => [
        '29ABCDE1234L1Z1',
        '37ABCDE1237L1Z1',
        '37ABCDE1237X1Z2',
        '01ABCDE1237X1ZP',
        '37ABXPE0068N1CD',
    ],

    'testInvalidGstin' => [
        '',
        '123',
        'ABCDE1234M',
        '123456789012345',
        '00ABCDE1237L1R1',
    ],

    'testValidStateCode' => [
        '29',
        '37',
        '37',
        '01',
    ],

    'testInvalidStateCode' => [
        '',
        '123',
        '00',
        '99',
        '-1',
    ],

    'testGstinStateMap' => [
        [
            'name'  => 'Andaman and Nicobar Islands',
            'code'  => '35',
            'is_ut' => true,
        ],
        [
            'name'  => 'Andhra Pradesh',
            'code'  => '28',
            'is_ut' => false,
        ],
        [
            'name'  => 'Andhra Pradesh (New)',
            'code'  => '37',
            'is_ut' => false,
        ],
        [
            'name'  => 'Arunachal Pradesh',
            'code'  => '12',
            'is_ut' => false,
        ],
        [
            'name'  => 'Assam',
            'code'  => '18',
            'is_ut' => false,
        ],
        [
            'name'  => 'Bihar',
            'code'  => '10',
            'is_ut' => false,
        ],
        [
            'name'  => 'Chandigarh',
            'code'  => '04',
            'is_ut' => true,
        ],
        [
            'name'  => 'Chattisgarh',
            'code'  => '22',
            'is_ut' => false,
        ],
        [
            'name'  => 'Dadra and Nagar Haveli',
            'code'  => '26',
            'is_ut' => true,
        ],
        [
            'name'  => 'Daman and Diu',
            'code'  => '25',
            'is_ut' => true,
        ],
        [
            'name'  => 'Delhi',
            'code'  => '07',
            'is_ut' => true,
        ],
        [
            'name'  => 'Goa',
            'code'  => '30',
            'is_ut' => false,
        ],
        [
            'name'  => 'Gujarat',
            'code'  => '24',
            'is_ut' => false,
        ],
        [
            'name'  => 'Haryana',
            'code'  => '06',
            'is_ut' => false,
        ],
        [
            'name'  => 'Himachal Pradesh',
            'code'  => '02',
            'is_ut' => false,
        ],
        [
            'name'  => 'Jammu and Kashmir',
            'code'  => '01',
            'is_ut' => false,
        ],
        [
            'name'  => 'Jharkhand',
            'code'  => '20',
            'is_ut' => false,
        ],
        [
            'name'  => 'Karnataka',
            'code'  => '29',
            'is_ut' => false,
        ],
        [
            'name'  => 'Kerala',
            'code'  => '32',
            'is_ut' => false,
        ],
        [
            'name'  => 'Lakshadweep Islands',
            'code'  => '31',
            'is_ut' => true,
        ],
        [
            'name'  => 'Madhya Pradesh',
            'code'  => '23',
            'is_ut' => false,
        ],
        [
            'name'  => 'Maharashtra',
            'code'  => '27',
            'is_ut' => false,
        ],
        [
            'name'  => 'Manipur',
            'code'  => '14',
            'is_ut' => false,
        ],
        [
            'name'  => 'Meghalaya',
            'code'  => '17',
            'is_ut' => false,
        ],
        [
            'name'  => 'Mizoram',
            'code'  => '15',
            'is_ut' => false,
        ],
        [
            'name'  => 'Nagaland',
            'code'  => '13',
            'is_ut' => false,
        ],
        [
            'name'  => 'Odisha',
            'code'  => '21',
            'is_ut' => false,
        ],
        [
            'name'  => 'Pondicherry',
            'code'  => '34',
            'is_ut' => true,
        ],
        [
            'name'  => 'Punjab',
            'code'  => '03',
            'is_ut' => false,
        ],
        [
            'name'  => 'Rajasthan',
            'code'  => '08',
            'is_ut' => false,
        ],
        [
            'name'  => 'Sikkim',
            'code'  => '11',
            'is_ut' => false,
        ],
        [
            'name'  => 'Tamil Nadu',
            'code'  => '33',
            'is_ut' => false,
        ],
        [
            'name'  => 'Telangana',
            'code'  => '36',
            'is_ut' => false,
        ],
        [
            'name'  => 'Tripura',
            'code'  => '16',
            'is_ut' => false,
        ],
        [
            'name'  => 'Uttar Pradesh',
            'code'  => '09',
            'is_ut' => false,
        ],
        [
            'name'  => 'Uttarakhand',
            'code'  => '05',
            'is_ut' => false,
        ],
        [
            'name'  => 'West Bengal',
            'code'  => '19',
            'is_ut' => false,
        ],
    ],
];

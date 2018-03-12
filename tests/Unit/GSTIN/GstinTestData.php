<?php

return [
    'testValidGstin' => [
        '29ABCDE1234L1Z1',
        '37ABCDE1237L1Z1',
        '37ABCDE1237X1Z2',
        '01ABCDE1237X1ZP',
    ],

    'testInvalidGstin' => [
        '',
        '123',
        'ABCDE1234M',
        '37ABCDE1237L1R1',
        '123456789012345',
        '00ABCDE1237L1R1',
    ],

    'testGstinStateMap' => [
        [
            'name' => 'Andaman and Nicobar Islands',
            'code' => '35'
        ],
        [
            'name' => 'Andhra Pradesh',
            'code' => '28'
        ],
        [
            'name' => 'Andhra Pradesh (New)',
            'code' => '37'
        ],
        [
            'name' => 'Arunachal Pradesh',
            'code' => '12'
        ],
        [
            'name' => 'Assam',
            'code' => '18'
        ],
        [
            'name' => 'Bihar',
            'code' => '10'
        ],
        [
            'name' => 'Chandigarh',
            'code' => '04'
        ],
        [
            'name' => 'Chattisgarh',
            'code' => '22'
        ],
        [
            'name' => 'Dadra and Nagar Haveli',
            'code' => '26'
        ],
        [
            'name' => 'Daman and Diu',
            'code' => '25'
        ],
        [
            'name' => 'Delhi',
            'code' => '07'
        ],
        [
            'name' => 'Goa',
            'code' => '30'
        ],
        [
            'name' => 'Gujarat',
            'code' => '24'
        ],
        [
            'name' => 'Haryana',
            'code' => '06'
        ],
        [
            'name' => 'Himachal Pradesh',
            'code' => '02'
        ],
        [
            'name' => 'Jammu and Kashmir',
            'code' => '01'
        ],
        [
            'name' => 'Jharkhand',
            'code' => '20'
        ],
        [
            'name' => 'Karnataka',
            'code' => '29'
        ],
        [
            'name' => 'Kerala',
            'code' => '32'
        ],
        [
            'name' => 'Lakshadweep Islands',
            'code' => '31'
        ],
        [
            'name' => 'Madhya Pradesh',
            'code' => '23'
        ],
        [
            'name' => 'Maharashtra',
            'code' => '27'
        ],
        [
            'name' => 'Manipur',
            'code' => '14'
        ],
        [
            'name' => 'Meghalaya',
            'code' => '17'
        ],
        [
            'name' => 'Mizoram',
            'code' => '15'
        ],
        [
            'name' => 'Nagaland',
            'code' => '13'
        ],
        [
            'name' => 'Odisha',
            'code' => '21'
        ],
        [
            'name' => 'Pondicherry',
            'code' => '34'
        ],
        [
            'name' => 'Punjab',
            'code' => '03'
        ],
        [
            'name' => 'Rajasthan',
            'code' => '08'
        ],
        [
            'name' => 'Sikkim',
            'code' => '11'
        ],
        [
            'name' => 'Tamil Nadu',
            'code' => '33'
        ],
        [
            'name' => 'Telangana',
            'code' => '36'
        ],
        [
            'name' => 'Tripura',
            'code' => '16'
        ],
        [
            'name' => 'Uttar Pradesh',
            'code' => '09'
        ],
        [
            'name' => 'Uttarakhand',
            'code' => '05'
        ],
        [
            'name' => 'West Bengal',
            'code' => '19'
        ],
    ],
];

<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\PaymentLink\Utility;

class UtilityTest extends BaseTest
{
    protected $datahelperPath   = '/Helpers/UtilityTestData.php';

    /**
     * @dataProvider getData
     * @group nocode_pp_type
     */
    public function testIsTextLink($data)
    {
        if ($data[1] === true)
        {
            $this->assertTrue(Utility::isTextLink($data[0]));
        }
        else
        {
            $this->assertFalse(Utility::isTextLink($data[0]));
        }
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_type
     */
    public function testConvertTextToQuillFormat($data)
    {
        $result = Utility::convertTextToQuillFormat($data['text']);
        $decoded = json_decode($result, true);

        $this->assertEquals($data['metaText'], $decoded['metaText']);

        foreach ($decoded['value'] as $formated)
        {
            if (! array_key_exists("attributes", $formated))
            {
                $this->assertEquals($data['text_insert'], $formated['insert']);
            }
            else
            {
                $this->assertArraySubset($data['link_attribute'], $formated);
            }
        }
    }
}

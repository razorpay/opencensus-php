<?php

namespace RZP\Tests\Unit\Modules\Acs\Comparator;

use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Modules\Acs\Comparator\Base;


class ChildComparatorNoDifference extends Base {
    protected $excludedKeys = [];
}

class ChildComparatorWithExcludedKeys extends Base {
    protected $excludedKeys = ["e" => true, "c" => true];
}

class ChildComparatorWithExcludedKeysChildKeys1 extends Base {
    protected $excludedKeys = ["e->m" => true];
}

class ChildComparatorWithExcludedKeysChildKeys2 extends Base {
    protected $excludedKeys = ["e->m->o" => true];
}

class ChildComparatorWithExcludedKeysChildKeys3 extends Base {
    protected $excludedKeys = ["c"=>true, "e->h" => true];
}


class BaseTest extends TestCase
{

    private $array1 = [
        "a" => "b",
        "c" => "d",
        "e" => [
            "f" => "g",
            "h" => "k",
            "l" => 1,
            "m" => [
                "j" => "4",
                "n" => true,
                "o" => [
                    "f" => "s"
                ]
            ]
        ]
    ];

    // remove field a and e->m->j from array
    private $array2 = [
        "c" => "d",
        "e" => [
            "f" => "g",
            "h" => "k",
            "l" => 1,
            "m" => [
                "n" => true,
                "o" => [
                    "f" => "s"
                ]
            ]
        ]
    ];

    //Change value and datatype
    private $array3 = [
        "a" => "b",
        "c" => "3",
        "e" => [
            "f" => "g",
            "h" => "f",
            "l" => 1,
            "m" => [
                "j" => "4",
                "n" => 4,
                "o" => [
                    "f" => "d"
                ]
            ]
        ]
    ];

    // Changed Array value
    private $array4 = [
        "a" => "b",
        "c" => "3",
        "e" => []
    ];

    // Changed Array value
    private $array5 = [
        "a" => "b",
        "c" => "3",
        "e" => NULL
    ];

    // Changed Array value, string '3' to int 3
    private $array6 = [
        "a" => "b",
        "c" => 3,
        "e" => NULL
    ];

    private $array7 = [
        "a" => "b",
        "c" => 3,
        "e" => 5
    ];


    protected function setUp(): void
    {
       parent::setUp();
    }

    function testGetDifferenceBaseNoDiff() {
       $diff = (new Base())->getDifference($this->array1, $this->array1);
       $this->assertEquals([], $diff);

       $diff = (new ChildComparatorNoDifference())->getDifference($this->array1, $this->array1);
       $this->assertEquals([], $diff);
    }

    function testGetDifferenceBaseFieldMissing() {
        $diff = (new Base())->getDifference($this->array1, $this->array2);
        $this->assertEquals(["a", "e->m->j"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array1, $this->array2);
        $this->assertEquals(["a", "e->m->j"], $diff);

        // reverse call should result in same result
        $diff = (new Base())->getDifference($this->array2, $this->array1);
        $this->assertEquals(["a", "e->m->j"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array2, $this->array1);
        $this->assertEquals(["a", "e->m->j"], $diff);
    }

    function testGetDifferenceBaseChangeValueAndDatatype() {
        $diff = (new Base())->getDifference($this->array1, $this->array3);
        $this->assertEquals(["c", "e->h","e->m->n", "e->m->o->f"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array1, $this->array3);
        $this->assertEquals(["c", "e->h","e->m->n", "e->m->o->f"], $diff);

        $diff = (new Base())->getDifference($this->array1, $this->array4);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array1, $this->array4);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);

        $diff = (new Base())->getDifference($this->array5, $this->array6);
        $this->assertEquals(["c"], $diff);

        // reverse  call should result in same result
        $diff = (new Base())->getDifference($this->array3, $this->array1);
        $this->assertEquals(["c", "e->h", "e->m->n", "e->m->o->f"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array3, $this->array1);
        $this->assertEquals(["c", "e->h", "e->m->n", "e->m->o->f"], $diff);

        $diff = (new Base())->getDifference($this->array4, $this->array1);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);

        $diff = (new ChildComparatorNoDifference())->getDifference($this->array4, $this->array1);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);

        $diff = (new Base())->getDifference($this->array6, $this->array5);
        $this->assertEquals(["c"], $diff);
    }

    function testGetDifferenceChildComparatorWithExcludedkeys() {
        $diff = (new ChildComparatorWithExcludedKeys())->getDifference($this->array1, $this->array3);
        $this->assertEquals([], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeys())->getDifference($this->array3, $this->array1);
        $this->assertEquals([], $diff);

        $diff = (new ChildComparatorWithExcludedKeys())->getDifference($this->array1, $this->array4);
        $this->assertEquals([], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeys())->getDifference($this->array3, $this->array4);
        $this->assertEquals([], $diff);
    }

    function testGetDifferenceChildComparatorWithExcludedKeysWithChild1() {

        // e->m is ignored, hence "e->m->o->f" should not appear but e->h should
        $diff = (new ChildComparatorWithExcludedKeysChildKeys1())->getDifference($this->array1, $this->array3);
        $this->assertEquals(["c", "e->h"], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeysChildKeys1())->getDifference($this->array3, $this->array1);
        $this->assertEquals(["c", "e->h"], $diff);
    }

    function testGetDifferenceChildComparatorWithExcludedKeysWithChild2() {

        // e->m->o is ignored, hence "e->m->o->f" should not appear but e->h should
        $diff = (new ChildComparatorWithExcludedKeysChildKeys2())->getDifference($this->array1, $this->array3);
        $this->assertEquals(["c", "e->h", "e->m->n"], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeysChildKeys2())->getDifference($this->array3, $this->array1);
        $this->assertEquals(["c", "e->h", "e->m->n"], $diff);

        // e->m->o is ignored, hence "e->m->o->f" should not appear but others should
        $diff = (new ChildComparatorWithExcludedKeysChildKeys2())->getDifference($this->array1, $this->array4);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeysChildKeys2())->getDifference($this->array4, $this->array1);
        $this->assertEquals(["c", "e->f", "e->h", "e->l", "e->m"], $diff);
    }

    function testGetDifferenceChildComparatorWithExcludedKeysWithChild3() {

        // c, e->h is ignored, hence "e->h" should not appear but e->m->o->f should
        $diff = (new ChildComparatorWithExcludedKeysChildKeys3())->getDifference($this->array1, $this->array3);
        $this->assertEquals(["e->m->n", "e->m->o->f"], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeysChildKeys3())->getDifference($this->array3, $this->array1);
        $this->assertEquals(["e->m->n","e->m->o->f"], $diff);

        // c, e->h is ignored, hence "e->h" should not appear but others should
        $diff = (new ChildComparatorWithExcludedKeysChildKeys3())->getDifference($this->array1, $this->array4);
        $this->assertEquals(["e->f", "e->l", "e->m"], $diff);

        // reverse call
        $diff = (new ChildComparatorWithExcludedKeysChildKeys3())->getDifference($this->array4, $this->array1);
        $this->assertEquals(["e->f", "e->l", "e->m"], $diff);
    }

    function testGetDifferenceBaseComparatorNullArrayOrValue() {

        $diff = (new Base())->getDifference($this->array4, $this->array5);
        $this->assertEquals([], $diff);

        $diff = (new Base())->getDifference($this->array5, $this->array4);
        $this->assertEquals([], $diff);

        $diff = (new Base())->getDifference($this->array6, $this->array7);
        $this->assertEquals(["e"], $diff);

        $diff = (new Base())->getDifference($this->array7, $this->array6);
        $this->assertEquals(["e"], $diff);
    }

}

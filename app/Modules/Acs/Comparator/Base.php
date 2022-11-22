<?php

namespace RZP\Modules\Acs\Comparator;

class Base
{
    // Keys which after comparing array
    // need not be considered in final difference list.
    protected $excludedKeys = [];

    function __construct()
    {
    }

    /**
     *
     * returns difference of two arrays, without considering keys as in $excludedKeys
     * Child key can be ignored by setting: parent->child
     * Difference in child key is returned as: parent->child1->child2
     * array_difference($a1, $a2) only compares keys present in $a1
     * Hence, to get complete difference also call in reverse.
     *
     * $a1=array("e"=>"red","b"=>0,"c"=>"blue","d"=>["ok"=>"ok", "ok3"=>"hello"]);
     * $a2=array("e"=>"red","b"=>"green","g"=>"blue","d"=>["ok"=>"ok", "different"=>"hello"], "de"=>"ok");
     * arrayDifference($a1, $a2) = [b,c,d->different]
     *
     * @param array $array1
     * @param array $array2
     * @param string $parentKey
     * @return array
     */
    protected function arrayDifference(array $array1, array $array2, string $parentKey = ""): array
    {
        $difference = [];
        foreach($array1 as $key => $value)
        {
            // skip if value in array1 is null
            if($value === null) {
                continue;
            }
            // checks if we need to find diff in this element.
            $keyWithParent = $key;
            if($parentKey !== ""){
                $keyWithParent = $parentKey."->".$key;
            }

            if(isset($this->excludedKeys[$keyWithParent]) === true){
                continue;
            }

            if(array_key_exists($key, $array2) === false){
                $difference[] = $key;
                continue;
            }

            // calculates diff recursively
            if(is_array($value) === true)
            {
                if((is_array($array2[$key]) === false))
                {
                    $difference[] = $key;
                }
                else
                {
                    $childDifference = $this->arrayDifference($value, $array2[$key], $keyWithParent);
                    if(count($childDifference) !== 0) {
                        foreach($childDifference as $childDifferenceKey => $childDifferenceValue) {
                            $difference[] = $key."->".$childDifferenceValue;
                        }
                    }
                }
            }
            elseif($array2[$key] !== $value)
            {
                $difference[] = $key;
            }
        }
        return $difference;
    }

    /**
     *
     * Calls arrayDifference with direct and reverse sequence.
     * and post processing with postProcessDifference function.
     *
     * $excludedKeys = []
     * $a1=array("e"=>"red","b"=>0,"c"=>"blue","d"=>["ok"=>"ok", "ok3"=>"hello"]);
     * $a2=array("e"=>"red","b"=>"green","g"=>"blue","d"=>["ok"=>"ok", "different"=>"hello"], "de"=>"ok");
     * getDifference($a1, $a2) = [b,c,g,d->different,de]
     *
     * $excludedKeys = [c]
     * $a1=array("e"=>"red","b"=>0,"c"=>"blue","d"=>["ok"=>"ok", "ok3"=>"hello"]);
     * $a2=array("e"=>"red","b"=>"green","g"=>"blue","d"=>["ok"=>"ok", "different"=>"hello"], "de"=>"ok");
     * getDifference($a1, $a2) = [b,g,d->different,de]
     *
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public function getDifference(array $array1, array $array2): array {
        $difference = array_values(array_unique(array_merge(
            $this->arrayDifference($array1, $array2),
            $this->arrayDifference($array2, $array1)
        )));

        return $this->postProcessDifference($difference);
    }

    /**
     *
     * Called by getDifference to help post process difference with custom logic.
     *
     * @param array $difference
     * @return array
     */
    protected function postProcessDifference(array $difference): array
    {
        return $difference;
    }

}

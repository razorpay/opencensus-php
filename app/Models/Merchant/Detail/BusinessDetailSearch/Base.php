<?php

namespace RZP\Models\Merchant\Detail\BusinessDetailSearch;

abstract class Base
{
    //
    // Constants Required for
    // Response Array
    //

    const MATCHES = "matches";

    const SUBCATEGORY_VALUE = "subcategory_value";

    const SUBCATEGORY_NAME = "subcategory_name";

    const TAGS = "tags";

    const GROUP_NAME = "group_name";
    const GROUP_VALUE = "group_value";

    protected $searchString;

    protected $searchResponse;

    /**
     * @param string $searchString
     */
    public function __construct(string $searchString)
    {
        $this->searchString = $searchString;

        $this->searchResponse = [];
    }

    /**
     *
     * Defines method which uses the search algo
     *
     * @return array
     */
    public abstract function searchString() : array ;

    /**
     * @param string $scopeString
     *
     * @return array
     */
    protected function stringStartsWithSearchString(string $scopeString): array
    {
        $wordList = preg_split("/[\s,]+/", $scopeString);

        $tags = [];

        $tempSearchString = strtolower($this->searchString);

        foreach ($wordList as $word)
        {
            $tempWord = strtolower($word);

            if (empty($tempWord) === true)
            {
                continue;
            }

            //
            //word starts with searchString
            //
            if (strpos($tempWord, $tempSearchString) === 0)
            {
                array_push($tags, $word);
            }
        }

        return $tags;
    }

}

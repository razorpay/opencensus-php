<?php

namespace Gateway;

class BaseGateway
{
    protected $txn_key_mappings = array();

    protected $card_key_mappints = array();

    //not being used. Only HdfcGateway->process is used.
    public function process($input)
    {
        $this->txn = $input['txn'];

        $this->card = $input['card'];

        $this->mapKeys($txn, $txn_key_mappings);

        $this->mapKeys($card, $card_key_mappings);

        $this->runGenerators();
    }

    protected function mapKeys($array, $map, &$data)
    {
        foreach ($map as $keyOld => $keyNew)
        {
            if (isset($array[$keyOld]))
            {
                $data[$keyNew] = $array[$keyOld];
            }
        }
    }

    protected function copyValues($array)
    {
        $fields = array_intersect(array_keys($array), $this->fields);

        foreach ($fields as $field)
        {
            $data[$field] = $array[$field];
        }
    }

    protected function runGenerators()
    {
        if (count ($this->generators) === 0)
            return;

        foreach ($generators as $generator)
        {
            $method = 'generate'.studly_case($generator);

            $this->$$method();
        }
    }

    protected function createRequest()
    {
        $ch = curl_init() or die(curl_error());
        curl_setopt($ch, CURLOPT_PORT, $this->port);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array($header));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $data1 = curl_exec($ch) or die(curl_error());

        curl_close($ch);

        $initial_response = $data1;

        $error = GetTextBetweenTags($initialResponse, "<error_text>", "</error_text");

        $enroll_result = GetTextBetweenTags($initial_response, "<result>", "</result>");
    }
}
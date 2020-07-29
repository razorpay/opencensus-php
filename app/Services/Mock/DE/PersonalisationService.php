<?php


namespace RZP\Services\Mock\DE;


class PersonalisationService
{
    public function fetchPersonalisationData()
    {
        $response = (new \Requests_Response());

        $response->body = '{
            "preferences": [
                {
                    "instrument" : "abcd@okhdfc",
                    "method"     : "upi",
                    "score"      :  0.54
                },
                {
                    "instrument" : "phonepay",
                    "method"     : "wallet",
                    "score"      :  0.54
                }
            ]
        }';

        return $response;
    }

}

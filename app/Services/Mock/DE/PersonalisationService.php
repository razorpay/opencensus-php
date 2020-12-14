<?php


namespace RZP\Services\Mock\DE;


class PersonalisationService
{
    public function fetchPersonalisationData()
    {
        $response = (new \Requests_Response());

        $response->body = '{
            "is_customer_identified" : true,
            "user_aggregates_available" : false,
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
                },
                {
                    "instrument" : "100000002lcard",
                    "method"     : "card",
                    "score"      : 0.54
                }
            ]
        }';

        return $response;
    }

}

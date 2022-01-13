<?php


namespace RZP\Services\Mock;
use App;


class AuthzClient
{

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->config = $app['config']->get('applications.authz');

        $this->trace = $app['trace'];

        $this->baseUrl = $this->config['url'];
    }

    public function adminAPIListAction ($pagination, $prefix){
        return [
            "pagination_token" => null,
            "count" => "1",
            "items" => [
                [
                    "id" => "FuEmr64NaJWKG7",
                    "name" => "get",
                    "type" => "ACTION_TYPE_R"
                ]
            ]

        ];
    }
}

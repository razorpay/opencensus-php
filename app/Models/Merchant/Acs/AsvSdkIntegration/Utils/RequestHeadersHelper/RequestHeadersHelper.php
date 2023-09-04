<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\RequestHeadersHelper;

use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant;

use App;


class RequestHeadersHelper
{

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function getRequestHeaders() : array
    {

        $ba = $this->app['basicauth'];

        [$actorId, $actorType] = $this->getActorIdAndType();

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $this->app['request']->ip();

        $headers = [
            Constant::X_ACTOR_ID => $actorId,
            Constant::X_ACTOR_TYPE => $actorType,
            Constant::X_AUTH_TYPE => $ba->getAuthType() ?? "",
            Constant::X_APP_NAME => $ba->getInternalApp() ?? "",
            Constant::X_IP => $clientIpAddress ?? ""
        ];

        return $headers;
    }

    protected function getActorIdAndType(): array
    {
        $ba = $this->app['basicauth'];

        $admin = $ba->getAdmin();

        if ($admin !== null) {
            return [$admin->getId(), Constant::ACTOR_TYPE_ADMIN];
        }

        $user = $ba->getUser();

        if ($user !== null) {
            return [$user->getId(), Constant::ACTOR_TYPE_USER];
        }

        return ["", ""];
    }
}

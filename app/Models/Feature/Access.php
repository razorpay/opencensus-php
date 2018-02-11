<?php

namespace RZP\Models\Feature;

use App;
use ApiResponse;
use Illuminate\Foundation\Application;

class Access
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    protected $merchant;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * Checks if the accessed route is a feature route, if yes
     * checks if the merchant has access to the feature
     *
     * @param array $authReturn
     *
     * @return null
     */
    public function verifyFeatureAccessByApplication($authReturn)
    {
        $routeFeatures = $this->app['basicauth']->getCurrentRouteFeatures();

        if (empty($routeFeatures) === true)
        {
            return null;
        }

        $routeFeaturesAvailableWithMerc = $this->getRouteFeaturesAvailableWithMerc($routeFeatures);

        $allowAccess = $this->allowApplicationAccessToFeatureRoute(
            $routeFeatures,
            $routeFeaturesAvailableWithMerc,
            $authReturn);

        if ($allowAccess === true)
        {
            return null;
        }

        return ApiResponse::routeNotFound();
    }

    /**
     * Checks if the accessed route is a feature route, if yes
     * checks if the merchant has access to the feature
     *
     * @return null
     */
    public function verifyFeatureAccessByMerchant()
    {
        $routeFeatures = $this->app['basicauth']->getCurrentRouteFeatures();

        if (empty($routeFeatures) === true)
        {
            return null;
        }

        $routeFeaturesAvailableWithMerc = $this->getRouteFeaturesAvailableWithMerc($routeFeatures);

        //
        // If the merchant is directly accessing the resource, allow if the
        // merchant has any of the route features required to access the resource.
        //
        if (empty($routeFeaturesAvailableWithMerc) === false)
        {
            return null;
        }

        return ApiResponse::routeNotFound();
    }

    protected function getRouteFeaturesAvailableWithMerc(array $routeFeatures)
    {
        //
        // If the merchant has at least one of the features
        // in the $features array enabled, we allow the request
        //
        $merchantFeatures = $this->merchant->getEnabledFeatures();

        return array_intersect($routeFeatures, $merchantFeatures);
    }

    protected function allowApplicationAccessToFeatureRoute(
        array $routeFeatures,
        array $routeFeaturesAvailableWithMerc,
        $authReturn): bool
    {
        //
        // 1. If the application has any of the route features required,
        //    allow the application to access the resource directly.
        //

        $applicationId = $authReturn['application']['id'];

        $application = Constants::APPLICATION;

        //
        // Fetch all the features of the application
        // that is trying to access the resource
        //
        $applicationFeatures = $this->repo->feature->findByEntityTypeAndEntityId($application, $applicationId);

        $applicationFeatures = $applicationFeatures->toArray();

        $routeFeaturesAvailableWithApp = array_intersect($routeFeatures, $applicationFeatures);

        if (empty($routeFeaturesAvailableWithApp) === false)
        {
            return true;
        }

        //
        // 2. If the application does not have any of the required route features,
        //    check the merchant features and allow the application to access the
        //    resource if the feature required is not a blacklisted feature.
        //

        $oauthBlacklistedFeatures = Entity::$oauthBlacklistedFeatures;

        $routeFeaturesAvailableWithMercWhitelisted = array_diff(
            $routeFeaturesAvailableWithMerc,
            $oauthBlacklistedFeatures);

        if (empty($routeFeaturesAvailableWithMercWhitelisted) === false)
        {
            return true;
        }

        return false;
    }
}
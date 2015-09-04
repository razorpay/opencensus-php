'use strict';


// Declare app level module which depends on filters, and services
var app = angular.module('app', [
    'ngAnimate',
    'ngCookies',
    'ngStorage',
    'ui.router',
    'ui.bootstrap',
    'ui.load',
    'ui.jq',
    'ui.validate',
    'pascalprecht.translate',
    'app.filters',
    'app.services',
    'app.directives',
    'app.controllers',
    'angularFileUpload',
    'ngIdle',
    'ngBusy',
    'angulartics',
    'angulartics.segment.io'
  ])
.run(
  [          '$rootScope', '$state', '$stateParams', 'admin', 'adminAuthorization',
    function ($rootScope,   $state,   $stateParams, admin, adminAuthorization) {
        $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams) {
            // track the state the user wants to go to; authorization service needs this
            $rootScope.toState = toState;
            $rootScope.toStateParams = toStateParams;

            // if the user is resolved, do an authorization check immediately. otherwise,
            // it'll be done when the state it resolved.
            if (admin.isIdentityResolved()) adminAuthorization.authorize();
        });

        $rootScope.$on('$stateChangeError', function(event) {
          $state.go('500');
        });
    }
  ]
)
.config(
  [          '$stateProvider', '$urlRouterProvider', '$controllerProvider', '$compileProvider', '$filterProvider', '$provide', '$analyticsProvider',
    function ($stateProvider,   $urlRouterProvider,   $controllerProvider,   $compileProvider,   $filterProvider,   $provide, $analyticsProvider) {

        // lazy controller, directive and service
        app.controller = $controllerProvider.register;
        app.directive  = $compileProvider.directive;
        app.filter     = $filterProvider.register;
        app.factory    = $provide.factory;
        app.service    = $provide.service;
        app.constant   = $provide.constant;
        app.value      = $provide.value;

        $urlRouterProvider
            .otherwise('/app/dashboard');
        $stateProvider
            //Logged in routes
            .state('app', {
                abstract: true,
                url: '/app',
                templateUrl: 'tpl/admin/app.html',
                resolve: {
                    authorize: ['adminAuthorization',
                      function(adminAuthorization) {
                        return adminAuthorization.authorize();
                      }
                    ]
                },
                data: {
                  role: 'auth'
                }
            })
            .state('app.payments.detail', {
                url: '/payments/:id',
                templateUrl: 'tpl/admin/app_payment_detail.html'
            })
            .state('app.dashboard', {
                url: '/dashboard',
                templateUrl: 'tpl/admin/app_dashboard.html'
            })
            .state('app.merchants', {
                url: '/merchants',
                template: '<div ui-view class="fade-in-down"></div>'
            })
            .state('app.merchants.list', {
                url: '/list',
                templateUrl: 'tpl/admin/app_merchants.html',
                // use resolve to load other dependences
            })
            .state('app.merchants.detail', {
                url: '/:id/detail',
                templateUrl: 'tpl/admin/app_merchant_detail.html'
            })
            .state('app.merchants.activation', {
                url: '/:id/activation',
                templateUrl: 'tpl/admin/app_merchant_activation.html'
            })
            .state('app.pricing', {
                url: '/pricing',
                templateUrl: 'tpl/admin/app_pricing.html'
            })
            .state('app.entities', {
                url: '/entities',
                templateUrl: 'tpl/admin/app_entities.html'
            })
            .state('app.actions', {
                url: '/actions',
                templateUrl: 'tpl/admin/app_actions.html'
            })
            .state('app.admins', {
                url: '/admins',
                templateUrl: 'tpl/admin/app_admins.html',
                data: {
                  superadmin: true
                }
            })
            .state('app.profile', {
                url: '/profile',
                templateUrl: 'tpl/admin/app_profile.html'
            })
            //Guest Routes
            .state('access', {
                url: '/access',
                template: '<div ui-view class="fade-in-right-big smooth"></div>',
                resolve: {
                    authorize: ['adminAuthorization',
                      function(adminAuthorization) {
                        return adminAuthorization.authorize();
                      }
                    ]
                },
                data: {
                  role: 'guest'
                }
            })
            .state('access.signin', {
                url: '/signin',
                templateUrl: 'tpl/admin/page_signin.html'
            })
            .state('access.lockme', {
                url: '/lockme/:username',
                templateUrl: 'tpl/page_lockme.html',
            })
            //other
            .state('404', {
                url: '/404',
                templateUrl: 'tpl/page_404.html'
            })
            //500
            .state('500', {
                url: '/500',
                templateUrl: 'tpl/page_500.html'
            })
    }
  ]
)

.config(['$translateProvider', function($translateProvider){

  // Register a loader for the static files
  // So, the module will search missing translation tables under the specified urls.
  // Those urls are [prefix][langKey][suffix].
  $translateProvider.useStaticFilesLoader({
    prefix: 'l10n/admin/',
    suffix: '.json'
  });

  // Tell the module what language to use by default
  $translateProvider.preferredLanguage('en');

  // Tell the module to store the language in the local storage
  $translateProvider.useLocalStorage();

}])
.config(['$keepaliveProvider', '$idleProvider', function($keepaliveProvider, $idleProvider) {
  $idleProvider.idleDuration(30 * 60);
  $idleProvider.warningDuration(5 * 60);
  $keepaliveProvider.interval(15);
}]);

// eslint-disable-next-line strict
'use strict';

if (typeof window.Sentry !== 'undefined') {
  // eslint-disable-next-line no-undef
  window.Sentry.onLoad(() => {
    const environment = window.INSTANCE_TYPE === 'canary' ? 'canary' : window.APP_ENV;
    window.Sentry.init({
      environment,
      release: __VERSION__,
      dsn: window.SENTRY_DSN,
    });
  });
}
// Declare app level module which depends on filters, and services
// eslint-disable-next-line
var app = angular
  .module('app', [
    'ngAnimate',
    'ngCookies',
    'ngStorage',
    'ui.router',
    'ui.load',
    'ui.validate',
    'app.filters',
    'app.services',
    'app.controllers',
    'ngIdle',
    'ngBusy',
  ])
  .run([
    '$rootScope',
    '$state',
    '$stateParams',
    'user',
    'authorization',
    // eslint-disable-next-line
    function ($rootScope, $state, $stateParams, user, authorization) {
      // eslint-disable-next-line
      $rootScope.$on('$stateChangeStart', function (event, toState, toStateParams) {
        // track the state the user wants to go to; authorization service needs this
        $rootScope.toState = toState;
        $rootScope.toStateParams = toStateParams;
        // if the user is resolved, do an authorization check immediately. otherwise,
        // it'll be done when the state it resolved.
        if (user.isIdentityResolved()) {
          authorization.authorize();
        }
        // eslint-disable-next-line
        user.identity(true).then(function (data) {
          if (data) {
            $rootScope.role = data.merchants[data.id].role;
          }
        });
      });
      // eslint-disable-next-line
      $rootScope.$on('$stateChangeError', function () {
        $state.go('500');
      });
    },
  ])
  .config([
    '$stateProvider',
    '$urlRouterProvider',
    '$controllerProvider',
    '$compileProvider',
    '$filterProvider',
    '$provide',
    '$httpProvider',
    'isHostedInBB',
    'appHost',
    // eslint-disable-next-line
    function (
      $stateProvider,
      $urlRouterProvider,
      $controllerProvider,
      $compileProvider,
      $filterProvider,
      $provide,
      $httpProvider,
      isHostedInBB,
      appHost,
    ) {
      // lazy controller, directive and service
      app.controller = $controllerProvider.register;
      app.directive = $compileProvider.directive;
      app.filter = $filterProvider.register;
      app.factory = $provide.factory;
      app.service = $provide.service;
      app.constant = $provide.constant;
      app.value = $provide.value;
      $urlRouterProvider.otherwise('/access/signin');

      $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

      if (isHostedInBB) {
        $httpProvider.defaults.headers.common['X-Origin-Product'] = appHost;
      }

      $stateProvider //Logged in routes
        .state('app', {
          url: '/app',
          templateUrl: 'tpl/app.html',
          resolve: {
            authorize: [
              'authorization',
              // eslint-disable-next-line
              function (authorization) {
                return authorization.authorize();
              },
            ],
          },
          data: { role: 'auth' },
        })
        //Guest Routes
        .state('access', {
          url: '/access',
          template: '<div ui-view class=""></div>',
          resolve: {
            authorize: [
              'authorization',
              // eslint-disable-next-line
              function (authorization) {
                return authorization.authorize();
              },
            ],
          },
          data: { role: 'guest' },
        })
        // auth routes
        .state('access.signin', {
          url: '/signin?next',
          templateUrl: 'tpl/auth/index.html',
        })
        .state('access.pre_signup', {
          url: '/pre_signup',
          templateUrl: 'tpl/auth/index.html',
        })
        .state('access.lockme', {
          url: '/lockme/:email',
          templateUrl: 'tpl/auth/index.html',
        })
        .state('access.signup', {
          url: '/signup',
          templateUrl: 'tpl/auth/index.html',
        })
        .state('access.forgotpwd', {
          url: '/forgotpwd',
          templateUrl: 'tpl/auth/index.html',
        })
        .state('access.signupnasscom', {
          url: '/signup/nasscom',
          templateUrl: 'tpl/auth/index.html',
          data: {
            ref: 'nasscom',
          },
        })
        .state('access.confirm', {
          url: '/confirm/:token',
          templateUrl: 'tpl/page_confirm.html',
          data: { role: 'any' },
        })
        .state('access.emailupdate', {
          url: '/emailupdate',
          templateUrl: 'tpl/page_emailupdate.html',
        })
        .state('access.resetpwd', {
          url: '/resetpwd/:token',
          templateUrl: 'tpl/page_resetpwd.html',
        })
        .state('access.resetpassword', {
          url: '/resetpassword',
          templateUrl: 'tpl/page_resetpwd.html',
        }) //404
        .state('404', {
          url: '/404',
          templateUrl: 'tpl/page_404.html',
        }) //500
        .state('500', {
          url: '/500',
          templateUrl: 'tpl/page_500.html',
        });
    },
  ])
  .config([
    '$keepaliveProvider',
    '$idleProvider',
    // eslint-disable-next-line
    function ($keepaliveProvider, $idleProvider) {
      // Lock out Duration = 15 minutes
      $idleProvider.idleDuration(15 * 60);
      $idleProvider.warningDuration(15);
      $keepaliveProvider.interval(60);
    },
  ])
  .constant('appHost', window.RZP && window.RZP.appHost)
  .constant('appName', window.RZP && window.RZP.appName)
  .constant('isHostedInBB', window.RZP && window.RZP.appName === 'businessbanking');

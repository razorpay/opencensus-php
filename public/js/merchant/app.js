'use strict';
// Declare app level module which depends on filters, and services
var app = angular
  .module('app', [
    'ngAnimate',
    'ngCookies',
    'ngStorage',
    'ui.router',
    'ui.bootstrap',
    'ui.load',
    'ui.jq',
    'ui.validate',
    'app.filters',
    'app.services',
    'app.directives',
    'app.controllers',
    'angularFileUpload',
    'ngIdle',
    'ngBusy',
    'noCAPTCHA',
  ])
  .run([
    '$rootScope',
    '$state',
    '$stateParams',
    'user',
    'authorization',
    function($rootScope, $state, $stateParams, user, authorization) {
      $rootScope.$on('$stateChangeStart', function(
        event,
        toState,
        toStateParams
      ) {
        // track the state the user wants to go to; authorization service needs this
        $rootScope.toState = toState;
        $rootScope.toStateParams = toStateParams;
        // if the user is resolved, do an authorization check immediately. otherwise,
        // it'll be done when the state it resolved.
        if (user.isIdentityResolved()) {
          authorization.authorize();
        }
        user.identity(true).then(function(data) {
          if (data) {
            $rootScope.role = data.merchants[data.id].role;
          }
        });
      });
      $rootScope.$on('$stateChangeError', function() {
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
    function(
      $stateProvider,
      $urlRouterProvider,
      $controllerProvider,
      $compileProvider,
      $filterProvider,
      $provide,
      $httpProvider
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

      $httpProvider.defaults.headers.common['X-Requested-With'] =
        'XMLHttpRequest';

      $stateProvider //Logged in routes
        .state('app', {
          url: '/app',
          templateUrl: 'tpl/app.html',
          resolve: {
            authorize: [
              'authorization',
              function(authorization) {
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
              function(authorization) {
                return authorization.authorize();
              },
            ],
          },
          data: { role: 'guest' },
        })
        // auth routes
        .state('access.signin', {
          url: '/signin',
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
        .state('access.resetpwd', {
          url: '/resetpwd/:token',
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
    function($keepaliveProvider, $idleProvider) {
      // Lock out Duration = 15 minutes
      $idleProvider.idleDuration(15 * 60);
      $idleProvider.warningDuration(15);
      $keepaliveProvider.interval(60);
    },
  ]);

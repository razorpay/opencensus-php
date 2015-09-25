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
  'noCAPTCHA',
  'angulartics',
  'angulartics.segment.io'
]).run([
  '$rootScope',
  '$state',
  '$stateParams',
  'user',
  'authorization',
  function ($rootScope, $state, $stateParams, user, authorization) {
    $rootScope.$on('$stateChangeStart', function (event, toState, toStateParams) {
      // track the state the user wants to go to; authorization service needs this
      $rootScope.toState = toState;
      $rootScope.toStateParams = toStateParams;
      // if the user is resolved, do an authorization check immediately. otherwise,
      // it'll be done when the state it resolved.
      if (user.isIdentityResolved())
        authorization.authorize();
    });
    $rootScope.$on('$stateChangeError', function (event) {
      $state.go('500');
    });
  }
]).config([
  '$stateProvider',
  '$urlRouterProvider',
  '$controllerProvider',
  '$compileProvider',
  '$filterProvider',
  '$provide',
  '$analyticsProvider',
  function ($stateProvider, $urlRouterProvider, $controllerProvider, $compileProvider, $filterProvider, $provide, $analyticsProvider) {
    // lazy controller, directive and service
    app.controller = $controllerProvider.register;
    app.directive = $compileProvider.directive;
    app.filter = $filterProvider.register;
    app.factory = $provide.factory;
    app.service = $provide.service;
    app.constant = $provide.constant;
    app.value = $provide.value;
    $urlRouterProvider.otherwise('/app/dashboard');
    $stateProvider  //Logged in routes
.state('app', {
      abstract: true,
      url: '/app',
      templateUrl: 'tpl/app.html',
      resolve: {
        authorize: [
          'authorization',
          function (authorization) {
            return authorization.authorize();
          }
        ]
      },
      data: { role: 'auth' }
    }).state('app.dashboard', {
      url: '/dashboard',
      templateUrl: 'tpl/app_dashboard.html'
    }).state('app.payments', {
      url: '/payments',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.payments.list', {
      url: '/list',
      templateUrl: 'tpl/app_payments.html'
    }).state('app.payments.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_payment_detail.html'
    }).state('app.refunds', {
      url: '/refunds',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.refunds.list', {
      url: '/list',
      templateUrl: 'tpl/app_refunds.html'
    }).state('app.refunds.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_refund_detail.html'
    }).state('app.settlements', {
      url: '/settlements',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.settlements.list', {
      url: '/list',
      templateUrl: 'tpl/app_settlements.html'
    }).state('app.settlements.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_settlement_detail.html'
    }).state('app.transactions', {
      url: '/transactions',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.transactions.list', {
      url: '/list',
      templateUrl: 'tpl/app_transactions.html'
    }).state('app.transactions.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_transaction_detail.html'
    }).state('app.addfunds', {
      url: '/addfunds',
      templateUrl: 'tpl/app_addfunds.html'
    }).state('app.keys', {
      url: '/keys',
      templateUrl: 'tpl/app_keys.html'
    }).state('app.activation', {
      url: '/activation',
      templateUrl: 'tpl/app_activation.html'
    }).state('app.profile', {
      url: '/profile',
      templateUrl: 'tpl/app_profile.html'
    })  //Guest Routes
.state('access', {
      url: '/access',
      template: '<div ui-view class="fade-in-right-big smooth"></div>',
      resolve: {
        authorize: [
          'authorization',
          function (authorization) {
            return authorization.authorize();
          }
        ]
      },
      data: { role: 'guest' }
    }).state('access.signin', {
      url: '/signin',
      templateUrl: 'tpl/page_signin.html'
    }).state('access.lockme', {
      url: '/lockme/:email',
      templateUrl: 'tpl/page_lockme.html'
    }).state('access.signupnasscom', {
      url: '/signup/nasscom',
      templateUrl: 'tpl/page_signup_nasscom.html'
    }).state('access.signup', {
      url: '/signup',
      templateUrl: 'tpl/page_signup.html'
    }).state('access.forgotpwd', {
      url: '/forgotpwd',
      templateUrl: 'tpl/page_forgotpwd.html'
    }).state('access.confirm', {
      url: '/confirm/:token',
      templateUrl: 'tpl/page_confirm.html'
    }).state('access.resetpwd', {
      url: '/resetpwd/:token',
      templateUrl: 'tpl/page_resetpwd.html'
    })  //404
.state('404', {
      url: '/404',
      templateUrl: 'tpl/page_404.html'
    })  //500
.state('500', {
      url: '/500',
      templateUrl: 'tpl/page_500.html'
    });
  }
]).config([
  '$translateProvider',
  function ($translateProvider) {
    // Register a loader for the static files
    // So, the module will search missing translation tables under the specified urls.
    // Those urls are [prefix][langKey][suffix].
    $translateProvider.useStaticFilesLoader({
      prefix: 'l10n/',
      suffix: '.json'
    });
    // Tell the module what language to use by default
    $translateProvider.preferredLanguage('en');
    // Tell the module to store the language in the local storage
    $translateProvider.useLocalStorage();
  }
]).config([
  '$keepaliveProvider',
  '$idleProvider',
  function ($keepaliveProvider, $idleProvider) {
    $idleProvider.idleDuration(30 * 60);
    $idleProvider.warningDuration(15);
    $keepaliveProvider.interval(60);
  }
]);
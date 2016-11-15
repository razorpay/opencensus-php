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
  'jqTourbusService',
  function ($rootScope, $state, $stateParams, user, authorization, jqTourbusService) {
    $rootScope.$on('$stateChangeStart', function (event, toState, toStateParams) {
      // track the state the user wants to go to; authorization service needs this
      $rootScope.toState = toState;
      $rootScope.toStateParams = toStateParams;
      // if the user is resolved, do an authorization check immediately. otherwise,
      // it'll be done when the state it resolved.
      if (user.isIdentityResolved()) {
        authorization.authorize();
      }
      user.identity(true).then(function (data) {
        $rootScope.role = data.merchants[data.id].pivot.role;
      });

    });
    $rootScope.$on('$stateChangeError', function () {
      $state.go('500');
    });

    $rootScope.tour = jqTourbusService;
  }
]).config([
  '$stateProvider',
  '$urlRouterProvider',
  '$controllerProvider',
  '$compileProvider',
  '$filterProvider',
  '$provide',
  function ($stateProvider, $urlRouterProvider, $controllerProvider, $compileProvider, $filterProvider, $provide) {
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
    }).state('app.orders', {
      url: '/orders',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.payments.list', {
      url: '/list',
      templateUrl: 'tpl/app_payments.html'
    }).state('app.orders.list', {
      url: '/list',
      templateUrl: 'tpl/app_orders.html'
    }).state('app.payments.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_payment_detail.html'
    }).state('app.config', {
      url: '/config',
      templateUrl: 'tpl/app_config.html'
    }).state('app.refunds', {
      url: '/refunds',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.refunds.list', {
      url: '/list',
      templateUrl: 'tpl/app_refunds.html'
    }).state('app.refunds.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_refund_detail.html'
    }).state('app.batch', {
      url: '/batch',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.batch.upload', {
      url: '/upload',
      templateUrl: 'tpl/app_batch_upload.html'
    }).state('app.batch.list', {
      url: '/list',
      templateUrl: 'tpl/app_batch_list.html'
    }).state('app.orders.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_order_detail.html'
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
    }).state('app.generatereport', {
      url: '/generatereport',
      templateUrl: 'tpl/app_generate_report.html'
    }).state('app.teammanagement', {
      url: '/team',
      templateUrl: 'tpl/app_team_management.html'
    }).state('app.credits', {
      url: '/credits',
      templateUrl: 'tpl/app_credits.html'
    }).state('app.keys', {
      url: '/keys',
      templateUrl: 'tpl/app_keys.html'
    }).state('app.activation', {
      url: '/activation',
      templateUrl: 'tpl/app_activation.html'
    }).state('app.webhooks', {
      url: '/webhooks',
      templateUrl: 'tpl/app_webhooks.html'
    }).state('app.referrals', {
      url: '/referral',
      templateUrl: 'tpl/app_referrals.html'
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
      templateUrl: 'tpl/page_signup.html',
      data: {
        ref: 'nasscom'
      }
    }).state('access.signup', {
      url: '/signup',
      templateUrl: 'tpl/page_signup.html'
    }).state('access.lead', {
      url: '/lead',
      templateUrl: 'tpl/page_lead_signup.html'
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
  '$keepaliveProvider',
  '$idleProvider',
  function ($keepaliveProvider, $idleProvider) {
    // Lock out Duration = 15 minutes
    $idleProvider.idleDuration(15 * 60);
    $idleProvider.warningDuration(15);
    $keepaliveProvider.interval(60);
  }
]);

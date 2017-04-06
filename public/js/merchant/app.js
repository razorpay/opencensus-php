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
  'angulartics.segment.io',
  'react'
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
        if (data) {
          $rootScope.role = data.merchants[data.id].pivot.role;
        }
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
  '$httpProvider',
  function ($stateProvider, $urlRouterProvider, $controllerProvider, $compileProvider, $filterProvider, $provide, $httpProvider) {
    // lazy controller, directive and service
    app.controller = $controllerProvider.register;
    app.directive = $compileProvider.directive;
    app.filter = $filterProvider.register;
    app.factory = $provide.factory;
    app.service = $provide.service;
    app.constant = $provide.constant;
    app.value = $provide.value;
    $urlRouterProvider.otherwise(function($injector, $location) {
      var role = $injector.get('$rootScope').role
      if (role === 'sellerapp') {
        return '/app/invoices'
      }
      return '/app/dashboard'
    });

    $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
    }).state('app.batch', {
      url: '/batch',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.batch.upload', {
      url: '/upload',
      templateUrl: 'tpl/app_batch_upload.html'
    }).state('app.batch.list', {
      url: '/list',
      templateUrl: 'tpl/app_batch_list.html'
    }).state('app.transactions', {
      url: '/transactions',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.transactions.list', {
      url: '/list',
      templateUrl: 'tpl/app_transactions.html'
    }).state('app.transactions.detail', {
      url: '/:id',
      templateUrl: 'tpl/app_transaction_detail.html'
    }).state('app.generatereport', {
      url: '/generatereport',
      templateUrl: 'tpl/app_generate_report.html'
    }).state('app.activation', {
      url: '/activation',
      templateUrl: 'tpl/app_activation.html'
    }).state('app.referrals', {
      url: '/referral',
      templateUrl: 'tpl/app_referrals.html'
    }).state('app.profile', {
      url: '/profile',
      templateUrl: 'tpl/app_profile.html'
    }).state('app.accounts', {
      url: '/accounts',
      templateUrl: 'tpl/app_accounts.html'
    })

    // React

    .state('app.invoices', {
      url: '/invoices',
      templateUrl: 'tpl/app_invoices.html'
    }).state('app.invoices.list', {
      url: '/list',
      templateProvider: reactTemplateProvider('<invoices-list />')
    }).state('app.invoices.customers', {
      url: '/customers',
      templateProvider: reactTemplateProvider('<customers-list />')
    }).state('app.invoices.items', {
      url: '/items',
      templateProvider: reactTemplateProvider('<items-list />')
    }).state('app.invoices.new', {
      url: '/new',
      templateProvider: reactTemplateProvider('<invoices-new />')
    }).state('app.invoices.details', {
      url: '/:id/details',
      controller: ['$scope', '$stateParams', function($scope, $stateParams) {
        $scope.invoiceId = $stateParams.id;
      }],
      templateProvider: reactTemplateProvider('<invoice-detail id="invoiceId" />')
    }).state('app.invoices.edit', {
      url: '/:id',
      controller: ['$scope', '$stateParams', function($scope, $stateParams) {
        $scope.invoiceId = $stateParams.id
      }],
      templateProvider: reactTemplateProvider('<invoices-new id="invoiceId" />')
    }).state('app.subscriptions', {
      url: '/subscriptions',
      templateProvider: reactTemplateProvider('<subscriptions-list />')
    }).state('app.subscriptionsnew', {
      url: '/subscriptions/new',
      templateProvider: reactTemplateProvider('<subscriptions-new />')
    }).state('app.plans', {
      url: '/plans',
      templateProvider: reactTemplateProvider('<plans-list />')
    }).state('app.orders', {
      url: '/orders',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.orders.list', {
      url: '/list',
      templateProvider: reactTemplateProvider('<orders-list />')
    }).state('app.orders.detail', {
      url: '/:id/details',
      controller: ['$scope', '$stateParams', function($scope, $stateParams) {
        $scope.id = $stateParams.id;
      }],
      templateProvider: reactTemplateProvider('<order-details id="id" />')
    }).state('app.settlements', {
      url: '/settlements',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.settlements.list', {
      url: '/list',
      templateProvider: reactTemplateProvider('<settlements-list />')
    }).state('app.settlements.detail', {
      url: '/:id',
      controller: ['$scope', '$stateParams', function($scope, $stateParams) {
        $scope.id = $stateParams.id;
      }],
      templateProvider: reactTemplateProvider('<settlement-details id="id" />')
    }).state('app.webhooks', {
      url: '/webhooks',
      templateProvider: reactTemplateProvider('<webhooks-list />')
    }).state('app.keys', {
      url: '/keys',
      templateProvider: reactTemplateProvider('<keys-list />')
    }).state('app.credits', {
      url: '/credits',
      templateProvider: reactTemplateProvider('<credits-new/>'),
    }).state('app.addfunds', {
      url: '/addfunds',
      templateProvider: reactTemplateProvider('<add-funds />')
    }).state('app.teammanagement', {
      url: '/team',
      templateProvider: reactTemplateProvider('<manage-team />')
    }).state('app.config', {
      url: '/config',
      templateProvider: reactTemplateProvider('<config-new/>'),
    })

      //Guest Routes
    .state('access', {
      url: '/access',
      template: '<div ui-view class=""></div>',
      resolve: {
        authorize: [
          'authorization',
          function (authorization) {
            return authorization.authorize();
          }
        ]
      },
      data: { role: 'guest' }
    })

    // auth routes
    .state('access.signin', {
      url: '/signin',
      templateUrl: 'tpl/auth/index.html',
    }).state('access.pre_signup', {
      url: '/pre_signup',
      templateUrl: 'tpl/auth/index.html',
    }).state('access.lockme', {
      url: '/lockme/:email',
      templateUrl: 'tpl/auth/index.html'
    }).state('access.signup', {
      url: '/signup',
      templateUrl: 'tpl/auth/index.html'
    }).state('access.forgotpwd', {
      url: '/forgotpwd',
      templateUrl: 'tpl/auth/index.html'
    }).state('access.signupnasscom', {
      url: '/signup/nasscom',
      templateUrl: 'tpl/auth/index.html',
      data: {
        ref: 'nasscom'
      }
    }).state('access.confirm', {
      url: '/confirm/:token',
      templateUrl: 'tpl/page_confirm.html',
      data: { role: 'any' }
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

var injectScript = (function () {
  return function (src, callback) {
    var script = document.createElement('script');
    script.async = true;
    script.src = src;
    if (callback) {
      script.onload = function() {
        callback.call();
      };
    }
    document.getElementsByTagName('head')[0].appendChild(script);
  };
})();

var reactTemplateProvider = function(template) {
  var calledOnce = false
  var deferred = null
  return ['$q', '$stateParams', function ($q) {
    deferred = deferred || $q.defer();
    if (!window.React) {
      if (!calledOnce) {
        calledOnce = true
        var url = "<% asset('js/generated/merchant_react.js') %>";
        url = (url.indexOf('-') !== -1) ? url : 'js/generated/merchant_react.js';
        injectScript(url, function() {
          deferred.resolve(template);
        });
      }
    } else {
      deferred.resolve(template);
    }
    return deferred.promise;
  }];
};

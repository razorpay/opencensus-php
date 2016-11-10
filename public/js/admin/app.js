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
  'angulartics',
  'angulartics.segment.io'
]).run([
  '$rootScope',
  '$state',
  '$stateParams',
  'admin',
  'adminAuthorization',
  function ($rootScope, $state, $stateParams, admin, adminAuthorization) {
    $rootScope.$on('$stateChangeStart', function (event, toState, toStateParams) {
      // track the state the user wants to go to; authorization service needs this
      $rootScope.toState = toState;
      $rootScope.toStateParams = toStateParams;
      // if the user is resolved, do an authorization check immediately. otherwise,
      // it'll be done when the state it resolved.
      if (admin.isIdentityResolved())
        adminAuthorization.authorize();
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
      templateUrl: 'tpl/admin/app.html',
      resolve: {
        authorize: [
          'adminAuthorization',
          function (adminAuthorization) {
            return adminAuthorization.authorize();
          }
        ]
      },
      data: { role: 'auth' }
    }).state('app.payments', {
      url: '/payments/:mode/:id',
      templateUrl: 'tpl/admin/app_payment_detail.html'
    }).state('app.dashboard', {
      url: '/dashboard',
      templateUrl: 'tpl/admin/app_dashboard.html'
    }).state('app.merchants', {
      url: '/merchants',
      template: '<div ui-view class="fade-in-down"></div>'
    }).state('app.merchants.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_merchants.html'
    }).state('app.merchants.invite', {
      url: '/invite',
      templateUrl: 'tpl/admin/app_merchant_invite.html'
    }).state('app.merchants.detail', {
      url: '/:id/detail',
      templateUrl: 'tpl/admin/app_merchant_detail.html'
    }).state('app.merchants.activation', {
      url: '/:id/activation',
      templateUrl: 'tpl/admin/app_merchant_activation.html'
    }).state('app.pricing', {
      url: '/pricing',
      templateUrl: 'tpl/admin/app_pricing.html'
    }).state('app.pricingdetail', {
      url: '/pricing/:id',
      templateUrl: 'tpl/admin/app_pricing_detail.html'
    }).state('app.entities', {
      url: '/entities/:mode/:type',
      templateUrl: 'tpl/admin/app_entities.html',
      reloadOnSearch: false,
      params: {
        mode: 'live',
        type: 'payment'
      }
    }).state('app.entitiesdetail', {
      url: '/entity/:mode/:type/:id',
      templateUrl: 'tpl/admin/app_entity_detail.html'
    }).state('app.actions', {
      url: '/actions',
      templateUrl: 'tpl/admin/app_actions.html'
    }).state('app.admins', {
      url: '/admins',
      templateUrl: 'tpl/admin/app_admins.html',
      data: { superadmin: true }
    }).state('app.aggregations', {
      url: '/aggregations',
      templateUrl: 'tpl/admin/app_aggregations.html',
      data: { superadmin: true }
    }).state('app.profile', {
      url: '/profile',
      templateUrl: 'tpl/admin/app_profile.html'
    }).state('app.orgs', {
      url: '/orgs',
      templateUrl: 'tpl/admin/app_orgs.html'
    }).state('app.orgs.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_orgs_list.html'
    }).state('app.orgs.detail', {
      url: '/:id/detail',
      templateUrl: 'tpl/admin/app_orgs_detail.html'
    }).state('app.users', {
      url: '/users',
      templateUrl: 'tpl/admin/app_orgs_users.html',
    }).state('app.adduser', {
      url: '/adduser',
      templateUrl: 'tpl/admin/app_orgs_user_add.html',
    }).state('app.roles', {
      url: '/roles',
      template: '<div ui-view class="fade-in-down smooth"></div>'
    }).state('app.roles.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_roles_list.html'
    }).state('app.roles.detail', {
      url: '/:id/detail',
      templateUrl: 'tpl/admin/app_roles_detail.html'
    }).state('app.addrole', {
      url: '/:id/detail',
      templateUrl: 'tpl/admin/app_orgs_role_add.html'
    }).state('app.groups', {
      url: '/groups',
      template: '<div ui-view class="fade-in-down smooth"></div>'
    }).state('app.groups.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_groups_list.html'
    }).state('app.groups.detail', {
      url: '/:id/detail',
      templateUrl: 'tpl/admin/app_group_detail.html'
    }).state('app.permissions', {
      url: '/permissions',
      template: '<div ui-view class="fade-in-down smooth"></div>'
    }).state('app.permissions.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_permissions_list.html'
    }).state('app.invitations', {
      url: '/invitations',
      template: '<div ui-view class="fade-in-down smooth"></div>'
    }).state('app.invitations.list', {
      url: '/list',
      templateUrl: 'tpl/admin/app_invitations_list.html'
    }) //Guest Routes
.state('access', {
      url: '/access',
      template: '<div ui-view class="fade-in-right-big smooth"></div>',
      resolve: {
        authorize: [
          'adminAuthorization',
          function (adminAuthorization) {
            return adminAuthorization.authorize();
          }
        ]
      },
      data: { role: 'guest' }
    }).state('access.signin', {
      url: '/signin',
      templateUrl: 'tpl/admin/page_signin.html'
    }).state('access.lockme', {
      url: '/lockme/:username',
      templateUrl: 'tpl/page_lockme.html'
    }).state('access.logout', {
      url: '/logout',
      templateUrl: 'tpl/admin/page_logout.html'
    }) //other
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
    // This is not used in frontend
    $idleProvider.idleDuration(15 * 60);
    // Show warning after 5 minutes, but this doesn't log you out
    $idleProvider.warningDuration(5 * 60);
    // Poke server every 15 seconds
    // Backend logs you out if you poke after 15 minutes of inactivity
    // In which case it will show you the login popup
    $keepaliveProvider.interval(15);
  }
]);

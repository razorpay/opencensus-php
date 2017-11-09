'use strict';
// Declare app level module which depends on filters, and services
var app = angular
  .module('app', [
    'ngAnimate',
    'ngCookies',
    'ngStorage',
    'ui.router',
    'ui.bootstrap',
    'ui.bootstrap.timepicker',
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
    'react',
  ])
  .run([
    '$rootScope',
    '$state',
    '$stateParams',
    'admin',
    'adminAuthorization',
    'organization',
    function(
      $rootScope,
      $state,
      $stateParams,
      admin,
      adminAuthorization,
      organization
    ) {
      $rootScope.$on('$stateChangeStart', function(
        event,
        toState,
        toStateParams
      ) {
        // track the state the user wants to go to; authorization service needs this
        $rootScope.toState = toState;
        $rootScope.toStateParams = toStateParams;
        // $rootScope.currentAdmin = organization.fetchCurrentAdmin();
      });

      // Auth
      if (!admin.isIdentityResolved()) {
        adminAuthorization.authorize();
      }

      $rootScope.$on('$stateChangeError', function(event) {
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
    function(
      $stateProvider,
      $urlRouterProvider,
      $controllerProvider,
      $compileProvider,
      $filterProvider,
      $provide
    ) {
      // lazy controller, directive and service
      app.controller = $controllerProvider.register;
      app.directive = $compileProvider.directive;
      app.filter = $filterProvider.register;
      app.factory = $provide.factory;
      app.service = $provide.service;
      app.constant = $provide.constant;
      app.value = $provide.value;

      // Default route
      // $urlRouterProvider.otherwise('/app/dashboard');

      $stateProvider //Logged in routes
        .state('app', {
          abstract: true,
          url: '/app',
          templateUrl: 'tpl/admin/app.html',
          resolve: {
            authorize: [
              'adminAuthorization',
              function(adminAuthorization) {
                return adminAuthorization.authorize();
              },
            ],
          },
          data: { role: 'auth' },
        })
        .state('app.payments', {
          url: '/payments/:mode/:id',
          templateUrl: 'tpl/admin/app_payment_detail.html',
        })
        .state('app.dashboard', {
          url: '/dashboard',
          templateUrl: 'tpl/admin/app_dashboard.html',
        })
        .state('app.merchants', {
          url: '/merchants',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.merchants.list', {
          url: '/list/:type',
          templateUrl: 'tpl/admin/app_merchants.html',
        })
        .state('app.merchants.invite', {
          url: '/invite',
          templateUrl: 'tpl/admin/app_merchant_invite.html',
        })
        .state('app.merchants.detail', {
          url: '/:id/detail',
          templateUrl: 'tpl/admin/app_merchant_detail.html',
        })
        .state('app.merchants.stats', {
          url: '/:id/stats',
          templateUrl: 'tpl/admin/app_merchant_stats.html',
        })
        .state('app.merchants.activation', {
          url: '/:id/activation',
          templateUrl: 'tpl/admin/app_merchant_activation.html',
        })
        .state('app.gateway', {
          url: '/gateway_rule',
          templateUrl: 'tpl/admin/app_gateway.html',
        })
        .state('app.pricing', {
          url: '/pricing',
          templateUrl: 'tpl/admin/app_pricing.html',
        })
        .state('app.pricingplan', {
          url: '/pricing/new',
          templateUrl: 'tpl/admin/app_add_pricing.html',
        })
        .state('app.pricingdetail', {
          url: '/pricing/:id',
          templateUrl: 'tpl/admin/app_pricing_detail.html',
        })
        .state('app.entities', {
          url: '/entities/:mode/:type',
          templateUrl: 'tpl/admin/app_entities.html',
          reloadOnSearch: false,
          params: {
            mode: 'live',
            type: 'payment',
          },
        })
        .state('app.entitiesdetail', {
          url: '/entity/:mode/:type/:id',
          templateUrl: 'tpl/admin/app_entity_detail.html',
        })
        .state('app.actions', {
          url: '/actions',
          templateUrl: 'tpl/admin/app_actions.html',
        })
        .state('app.admins', {
          url: '/admins',
          templateUrl: 'tpl/admin/app_admins.html',
          data: { superadmin: true },
        })
        .state('app.aggregations', {
          url: '/aggregations',
          templateUrl: 'tpl/admin/app_aggregations.html',
          data: { superadmin: true },
        })
        .state('app.profile', {
          url: '/profile',
          templateUrl: 'tpl/admin/app_profile.html',
        })
        .state('app.auditlogs', {
          url: '/auditlogs',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.auditlogs.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_auditlogs_list.html',
        })
        .state('app.orgs', {
          url: '/orgs',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.orgs.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_orgs_list.html',
        })
        .state('app.orgs.detail', {
          url: '/:id/detail',
          templateUrl: 'tpl/admin/app_orgs_detail.html',
        })
        .state('app.orgs.new', {
          url: '/new',
          templateUrl: 'tpl/admin/app_add_org.html',
        })
        .state('app.orgs.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_add_org.html',
        })
        .state('app.orgs.fieldmaps', {
          url: '/:id/fieldmaps',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.orgs.fieldmaps.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_org_fieldmaps_list.html',
        })
        .state('app.orgs.fieldmaps.new', {
          url: '/new',
          templateUrl: 'tpl/admin/app_org_add_fieldmap.html',
        })
        .state('app.orgs.fieldmaps.edit', {
          url: '/:fieldMapId/edit',
          templateUrl: 'tpl/admin/app_org_add_fieldmap.html',
        })
        .state('app.users', {
          url: '/users',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.users.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_orgs_users.html',
        })
        .state('app.users.add', {
          url: '/add',
          templateUrl: 'tpl/admin/app_orgs_user_add.html',
        })
        .state('app.users.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_orgs_user_add.html',
        })
        .state('app.roles', {
          url: '/roles',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.roles.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_roles_list.html',
        })
        .state('app.roles.add', {
          url: '/add',
          templateUrl: 'tpl/admin/app_orgs_role_add.html',
        })
        .state('app.roles.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_orgs_role_add.html',
        })
        .state('app.groups', {
          url: '/groups',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.groups.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_groups_list.html',
        })
        .state('app.groups.detail', {
          url: '/:id/detail',
          templateUrl: 'tpl/admin/app_group_detail.html',
        })
        .state('app.groups.add', {
          url: '/add',
          templateUrl: 'tpl/admin/app_add_group.html',
        })
        .state('app.groups.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_add_group.html',
        })
        .state('app.permissions', {
          url: '/permissions',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.permissions.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_permissions_list.html',
        })
        .state('app.permissions.new', {
          url: '/new',
          templateUrl: 'tpl/admin/app_add_permission.html',
        })
        .state('app.permissions.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_add_permission.html',
        })
        .state('app.invitations', {
          url: '/invitations',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.invitations.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_invitations_list.html',
        })
        .state('app.emaillogs', {
          url: '/emaillogs',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.emaillogs.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_email_logs_list.html',
        })
        .state('app.onboardingRequests', {
          url: '/onboarding_requests',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.onboardingRequests.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_onboarding_requests.html',
        })
        .state('app.workflows', {
          url: '/workflows',
          template: '<div ui-view class=""></div>',
        })
        .state('app.workflows.actions', {
          url: '/actions',
          template: '<div ui-view class="fade-in-down smooth"></div>',
        })
        .state('app.workflows.actions.list', {
          url: '/list/:type',
          templateUrl: 'tpl/admin/app_workflow_feed_list.html',
        })
        .state('app.workflows.actions.detail', {
          url: '/:action_id',
          templateUrl: 'tpl/admin/app_workflow_feed.html',
        })
        .state('app.workflows.list', {
          url: '/list',
          templateUrl: 'tpl/admin/app_workflow_list.html',
        })
        .state('app.workflows.new', {
          url: '/new',
          templateUrl: 'tpl/admin/app_workflow_new.html',
        })
        .state('app.workflows.edit', {
          url: '/:id/edit',
          templateUrl: 'tpl/admin/app_workflow_new.html',
        })
        // React Routes
        // Test routes
        .state('app.merchants.team', {
          url: '/:id/team',
          controller: [
            '$scope',
            '$stateParams',
            function($scope, $stateParams) {
              $scope.id = $stateParams.id;
            },
          ],
          templateProvider: reactTemplateProvider('<merchant-team id="id" />'),
        })
        .state('app.zroles', {
          url: '/zroles',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('app.zroles.list', {
          url: '/list',
          templateProvider: reactTemplateProvider('<roles-list />'),
        })
        // End of React Routes

        //Guest Routes
        .state('access', {
          url: '/access',
          template: '<div ui-view class="fade-in-right-big smooth"></div>',
          resolve: {
            authorize: [
              'adminAuthorization',
              function(adminAuthorization) {
                return adminAuthorization.authorize();
              },
            ],
          },
          data: { role: 'guest' },
        })
        .state('access.auth', {
          url: '/auth',
          template: '<div ui-view class="fade-in-down"></div>',
        })
        .state('access.auth.password', {
          url: '/password',
          templateUrl: 'tpl/admin/page_signin.html',
          data: { role: 'guest' },
        })
        .state('access.lockme', {
          url: '/lockme/:username',
          templateUrl: 'tpl/page_lockme.html',
        })
        .state('access.logout', {
          url: '/logout',
          templateUrl: 'tpl/admin/page_logout.html',
        })
        .state('access.forgotpwd', {
          url: '/forgotpwd',
          templateUrl: 'tpl/admin/forgot_pwd.html',
        })
        .state('access.resetpwd', {
          url: '/resetpwd/:token',
          templateUrl: 'tpl/admin/password_reset.html',
        })
        //other
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
      // This is not used in frontend
      $idleProvider.idleDuration(15 * 60);
      // Show warning after 5 minutes, but this doesn't log you out
      $idleProvider.warningDuration(5 * 60);
      // Poke server every 15 seconds
      // Backend logs you out if you poke after 15 minutes of inactivity
      // In which case it will show you the login popup
      $keepaliveProvider.interval(15);
    },
  ]);

var injectScript = (function() {
  return function(src, callback) {
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
  var calledOnce = false;
  var deferred = null;
  return [
    '$q',
    '$stateParams',
    function($q) {
      deferred = deferred || $q.defer();
      if (!window.React) {
        if (!calledOnce) {
          calledOnce = true;
          injectScript('<%=REACT_REV_PLACEHOLDER=%>', function() {
            deferred.resolve(template);
          });
        }
      } else {
        deferred.resolve(template);
      }
      return deferred.promise;
    },
  ];
};

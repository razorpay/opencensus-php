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
    'app.controllers'
  ])
.run(
  [          '$rootScope', '$state', '$stateParams', 'user', 'authorization',
    function ($rootScope,   $state,   $stateParams, user, authorization) {
        $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams) {
        // track the state the user wants to go to; authorization service needs this
        $rootScope.toState = toState;
        $rootScope.toStateParams = toStateParams;
        // if the user is resolved, do an authorization check immediately. otherwise,
        // it'll be done when the state it resolved.
        if (user.isIdentityResolved()) authorization.authorize();
      });   
    }
  ]
)
.config(
  [          '$stateProvider', '$urlRouterProvider', '$controllerProvider', '$compileProvider', '$filterProvider', '$provide',
    function ($stateProvider,   $urlRouterProvider,   $controllerProvider,   $compileProvider,   $filterProvider,   $provide) {
        
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
            .state('app', {
                abstract: true,
                url: '/app',
                templateUrl: 'tpl/app.html',
                resolve: {
                    authorize: ['authorization',
                      function(authorization) {
                        return authorization.authorize();
                      }
                    ]
                },
                data: {
                  role: 'auth'
                }
            })
            .state('app.dashboard', {
                url: '/dashboard',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.transactions', {
                url: '/transactions',
                templateUrl: 'tpl/table_static.html',
                // use resolve to load other dependences
                resolve: {
                    deps: ['uiLoad',
                      function( uiLoad ){
                        return uiLoad.load( ['js/libs/moment.min.js']);
                    }]
                }
            })
            .state('app.refunds', {
                url: '/transactions/refunded',
                templateUrl: 'tpl/table_static.html',
                // use resolve to load other dependences
                resolve: {
                    deps: ['uiLoad',
                      function( uiLoad ){
                        return uiLoad.load( ['js/libs/moment.min.js']);
                    }]
                },
                data: {
                  status: 'refunded'
                }
            })
            .state('app.settlements', {
                url: '/settlements',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.keys', {
                url: '/keys',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.activation', {
                url: '/activation',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.profile', {
                url: '/profile',
                templateUrl: 'tpl/page_profile.html'
            })
            // others
            .state('lockme', {
                url: '/lockme',
                templateUrl: 'tpl/page_lockme.html'
            })
            .state('access', {
                url: '/access',
                template: '<div ui-view class="fade-in-right-big smooth"></div>',
                resolve: {
                    authorize: ['authorization',
                      function(authorization) {
                        return authorization.authorize();
                      }
                    ]
                },
                data: {
                  role: 'guest'
                }
            })
            .state('access.signin', {
                url: '/signin',
                templateUrl: 'tpl/page_signin.html'
            })
            .state('access.signup', {
                url: '/signup',
                templateUrl: 'tpl/page_signup.html'
            })
            .state('access.forgotpwd', {
                url: '/forgotpwd',
                templateUrl: 'tpl/page_forgotpwd.html'
            })
            .state('access.404', {
                url: '/404',
                templateUrl: 'tpl/page_404.html'
            })
            .state('access.confirm', {
                url: '/confirm/:token',
                templateUrl: 'tpl/page_confirm.html'
            })
            .state('access.resetpwd', {
                url: '/resetpwd/:token',
                templateUrl: 'tpl/page_resetpwd.html'
            })
    }
  ]
)

.config(['$translateProvider', function($translateProvider){

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

}])

/**
 * jQuery plugin config use ui-jq directive , config the js and css files that required
 * key: function name of the jQuery plugin
 * value: array of the css js file located
 */
.constant('JQ_CONFIG', {
    easyPieChart:   ['js/jquery/charts/easypiechart/jquery.easy-pie-chart.js'],
    sparkline:      ['js/jquery/charts/sparkline/jquery.sparkline.min.js'],
    plot:           ['js/jquery/charts/flot/jquery.flot.min.js', 
                        'js/jquery/charts/flot/jquery.flot.time.js',
                        'js/jquery/charts/flot/jquery.flot.resize.js',
                        'js/jquery/charts/flot/jquery.flot.tooltip.min.js',
                        'js/jquery/charts/flot/jquery.flot.spline.js',
                        'js/jquery/charts/flot/jquery.flot.orderBars.js',
                        'js/jquery/charts/flot/jquery.flot.pie.min.js'],
    slimScroll:     ['js/jquery/slimscroll/jquery.slimscroll.min.js'],
    sortable:       ['js/jquery/sortable/jquery.sortable.js'],
    nestable:       ['js/jquery/nestable/jquery.nestable.js',
                        'js/jquery/nestable/nestable.css'],
    filestyle:      ['js/jquery/file/bootstrap-filestyle.min.js'],
    slider:         ['js/jquery/slider/bootstrap-slider.js',
                        'js/jquery/slider/slider.css'],
    chosen:         ['js/jquery/chosen/chosen.jquery.min.js',
                        'js/jquery/chosen/chosen.css'],
    TouchSpin:      ['js/jquery/spinner/jquery.bootstrap-touchspin.min.js',
                        'js/jquery/spinner/jquery.bootstrap-touchspin.css'],
    wysiwyg:        ['js/jquery/wysiwyg/bootstrap-wysiwyg.js',
                        'js/jquery/wysiwyg/jquery.hotkeys.js'],
    dataTable:      ['js/jquery/datatables/jquery.dataTables.min.js',
                        'js/jquery/datatables/dataTables.bootstrap.js',
                        'js/jquery/datatables/dataTables.bootstrap.css'],
    vectorMap:      ['js/jquery/jvectormap/jquery-jvectormap.min.js', 
                        'js/jquery/jvectormap/jquery-jvectormap-world-mill-en.js',
                        'js/jquery/jvectormap/jquery-jvectormap-us-aea-en.js',
                        'js/jquery/jvectormap/jquery-jvectormap.css'],
    footable:       ['js/jquery/footable/footable.all.min.js',
                        'js/jquery/footable/footable.core.css']
    }
)


.constant('MODULE_CONFIG', {
    select2:        ['js/jquery/select2/select2.css',
                        'js/jquery/select2/select2-bootstrap.css',
                        'js/jquery/select2/select2.min.js',
                        'js/modules/ui-select2.js']
    }
)
;
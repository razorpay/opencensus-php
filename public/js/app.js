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
  [          '$rootScope', '$state', '$stateParams',
    function ($rootScope,   $state,   $stateParams) {
        $rootScope.$state = $state;
        $rootScope.$stateParams = $stateParams;        
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
                templateUrl: 'tpl/app.html'
            })
            .state('app.dashboard', {
                url: '/dashboard',
                templateUrl: 'tpl/app_dashboard.html'
            })
            .state('app.ui', {
                url: '/ui',
                template: '<div ui-view class="fade-in-up"></div>'
            })
            .state('app.ui.buttons', {
                url: '/buttons',
                templateUrl: 'tpl/ui_buttons.html'
            })
            .state('app.ui.icons', {
                url: '/icons',
                templateUrl: 'tpl/ui_icons.html'
            })
            .state('app.ui.grid', {
                url: '/grid',
                templateUrl: 'tpl/ui_grid.html'
            })
            .state('app.ui.widgets', {
                url: '/widgets',
                templateUrl: 'tpl/ui_widgets.html'
            })
            .state('app.ui.bootstrap', {
                url: '/bootstrap',
                templateUrl: 'tpl/ui_bootstrap.html'
            })
            .state('app.ui.sortable', {
                url: '/sortable',
                templateUrl: 'tpl/ui_sortable.html'
            })
            .state('app.ui.portlet', {
                url: '/portlet',
                templateUrl: 'tpl/ui_portlet.html'
            })
            .state('app.ui.timeline', {
                url: '/timeline',
                templateUrl: 'tpl/ui_timeline.html'
            })
            .state('app.ui.jvectormap', {
                url: '/jvectormap',
                templateUrl: 'tpl/ui_jvectormap.html'
            })
            .state('app.ui.googlemap', {
                url: '/googlemap',
                templateUrl: 'tpl/ui_googlemap.html',
                resolve: {
                    deps: ['uiLoad',
                      function( uiLoad ){
                        return uiLoad.load( ['js/app/map/load-google-maps.js',
                                                'js/modules/ui-map.js',
                                                'js/app/map/map.js'] ).then(function(){ return loadGoogleMaps(); });
                    }]
                }
            })
            .state('app.ui.chart', {
                url: '/chart',
                templateUrl: 'tpl/ui_chart.html'
            })
            // table
            .state('app.table', {
                url: '/table',
                template: '<div ui-view></div>'
            })
            .state('app.table.static', {
                url: '/static',
                templateUrl: 'tpl/table_static.html'
            })
            .state('app.table.datatable', {
                url: '/datatable',
                templateUrl: 'tpl/table_datatable.html'
            })
            .state('app.table.footable', {
                url: '/footable',
                templateUrl: 'tpl/table_footable.html'
            })
            // form
            .state('app.form', {
                url: '/form',
                template: '<div ui-view class="fade-in"></div>'
            })
            .state('app.form.elements', {
                url: '/elements',
                templateUrl: 'tpl/form_elements.html'
            })
            .state('app.form.validation', {
                url: '/validation',
                templateUrl: 'tpl/form_validation.html'
            })
            .state('app.form.wizard', {
                url: '/wizard',
                templateUrl: 'tpl/form_wizard.html'
            })
            // pages
            .state('app.page', {
                url: '/page',
                template: '<div ui-view class="fade-in-down"></div>'
            })
            .state('app.page.profile', {
                url: '/profile',
                templateUrl: 'tpl/page_profile.html'
            })
            .state('app.page.post', {
                url: '/post',
                templateUrl: 'tpl/page_post.html'
            })
            .state('app.page.search', {
                url: '/search',
                templateUrl: 'tpl/page_search.html'
            })
            .state('app.page.invoice', {
                url: '/invoice',
                templateUrl: 'tpl/page_invoice.html'
            })
            .state('app.docs', {
                url: '/docs',
                templateUrl: 'tpl/docs.html'
            })
            // others
            .state('lockme', {
                url: '/lockme',
                templateUrl: 'tpl/page_lockme.html'
            })
            .state('access', {
                url: '/access',
                template: '<div ui-view class="fade-in-right-big smooth"></div>'
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

            // fullCalendar
            .state('app.calendar', {
                url: '/calendar',
                templateUrl: 'tpl/app_calendar.html',
                // use resolve to load other dependences
                resolve: {
                    deps: ['uiLoad',
                      function( uiLoad ){
                        return uiLoad.load( ['js/jquery/fullcalendar/fullcalendar.css',
                                             'js/jquery/jquery-ui-1.10.3.custom.min.js',
                                             'js/jquery/fullcalendar/fullcalendar.min.js',
                                             'js/modules/ui-calendar.js',
                                             'js/app/calendar/calendar.js']);
                    }]
                }
            })

            // mail
            .state('app.mail', {
                abstract: true,
                url: '/mail',
                templateUrl: 'tpl/mail.html',
                // use resolve to load other dependences
                resolve: {
                    deps: ['uiLoad',
                      function( uiLoad ){
                        return uiLoad.load( ['js/app/mail/mail.js',
                                             'js/app/mail/mail-service.js',
                                             'js/libs/moment.min.js']);
                    }]
                }
            })
            .state('app.mail.list', {
                url: '/inbox/{fold}',
                templateUrl: 'tpl/mail.list.html'
            })
            .state('app.mail.detail', {
                url: '/{mailId:[0-9]{1,4}}',
                templateUrl: 'tpl/mail.detail.html'
            })
            .state('app.mail.compose', {
                url: '/compose',
                templateUrl: 'tpl/mail.new.html'
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
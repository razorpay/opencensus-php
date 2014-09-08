'use strict';

/* Controllers */

angular.module('app.controllers', ['pascalprecht.translate', 'ngCookies'])
  .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window',
    function(              $scope,   $translate,   $localStorage,   $window) {
      // add 'ie' classes to html
      var isIE = !!navigator.userAgent.match(/MSIE/i);
      isIE && angular.element($window.document.body).addClass('ie');
      isSmartDevice( $window ) && angular.element($window.document.body).addClass('smart');

      // config
      $scope.app = {
        name: 'Razorpay',
        version: '0.9.1',
        today: new Date(),
        // for chart colors
        color: {
          primary: '#7266ba',
          info:    '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger:  '#f05050',
          light:   '#e8eff0',
          dark:    '#3a3f51',
          black:   '#1c2b36'
        },
        settings: {
          themeID: 9,
          navbarHeaderColor: 'bg-dark',
          navbarCollapseColor: 'bg-primary',
          asideColor: 'bg-dark',
          headerFixed: true,
          asideFixed: true,
          asideFolded: false
        }
      }

      // save settings to local storage
      if ( angular.isDefined($localStorage.settings) ) {
        $scope.app.settings = $localStorage.settings;
      } else {
        $localStorage.settings = $scope.app.settings;
      }
      $scope.$watch('app.settings', function(){ $localStorage.settings = $scope.app.settings; }, true);

      // angular translate
      $scope.langs = {en:'English', hi:'Hindi'};
      $scope.selectLang = $scope.langs[$translate.proposedLanguage()] || "English";
      $scope.setLang = function(langKey) {
        // set the current lang
        $scope.selectLang = $scope.langs[langKey];
        // You can change the language during runtime
        $translate.use(langKey);
      };


      function isSmartDevice( $window )
      {
          // Adapted from http://www.detectmobilebrowsers.com
          var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
          // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
          return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
      }

      $scope.flag = false;
  }])

  //Application mode change controller
  .controller('modeCtrl', ['$scope', 'modeFactory', 'user', '$modal',
    function($scope, modeFactory, user, $modal){
      $scope.modes = modeFactory.getModes;
      $scope.mode = modeFactory.getMode;
      
      $scope.selectMode = function(mode) {
        user.identity().then(function(data){
          var userData = data;
          if(mode == "live" && parseInt(userData.activated) !== 1) {
            var modalInstance = $modal.open({
              templateUrl: 'activationModalContent.html',
              controller: activationModalCtrl,
              size: 'sm'
            });
          }
          else {
            modeFactory.selectMode(mode);   
          }
        });
      };

      var activationModalCtrl = function ($scope, $modalInstance) {
        $scope.ok = function () {
          $modalInstance.close();
        };
        $scope.cancel = function () {
          $modalInstance.dismiss('cancel');
        };
      };
  }]);
//Rest controllers loaded from /public/js/controllers/*.js by grunt
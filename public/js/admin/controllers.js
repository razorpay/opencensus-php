'use strict';
/* Controllers */
angular.module('app.controllers', [
  'ngCookies'
]).controller('AppCtrl', [
  '$scope',
  '$localStorage',
  '$window',
  function ($scope, $localStorage, $window) {
    // add 'ie' classes to html
    var isIE = !!navigator.userAgent.match(/MSIE/i);
    isIE && angular.element($window.document.body).addClass('ie');
    isSmartDevice($window) && angular.element($window.document.body).addClass('smart');
    // config
    $scope.app = {
      name: 'RZP Admin',
      version: '1.0.0',
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
        navbarCollapseColor: 'bg-white-only',
        asideColor: 'bg-dark',
        headerFixed: true,
        asideFixed: true,
        asideFolded: false
      }
    };
    // save settings to local storage
    if (angular.isDefined($localStorage.settings)) {
      $scope.app.settings = $localStorage.settings;
    } else {
      $localStorage.settings = $scope.app.settings;
    }
    $scope.$watch('app.settings', function () {
      $localStorage.settings = $scope.app.settings;
    }, true);

    function isSmartDevice($window) {
      // Adapted from http://www.detectmobilebrowsers.com
      var ua = $window.navigator.userAgent || $window.navigator.vendor || $window.opera;
      // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
      return /iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/.test(ua);
    }
    $scope.flag = false;
  }
]).controller('confirmModalCtrl', [
  '$scope',
  '$modalInstance',
  'message',
  function ($scope, $modalInstance, message) {
    $scope.message = message;
    $scope.ok = function () {
      $modalInstance.close();
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  }
]);  //Rest controllers loaded from /public/js/admin/controllers/*.js by grunt

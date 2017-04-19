'use strict';
/* Controllers */
angular.module('app.controllers', [
  'ngCookies'
]).controller('AppCtrl', [
  '$scope',
  '$localStorage',
  '$window',
  'theme',
  'organization',
  function ($scope, $localStorage, $window, theme, organization) {
    var baseTheme = {
      transparent : 'rgba(0,0,0,0.2)',
      transparentDark : 'rgba(0,0,0,0.4)',
      textLight : 'rgba(255,255,255,0.9)',
      primaryLight : '#e1f3ff',
      primaryTransparent : 'rgba(225,243,252,0.3)',
      errorBackground : 'rgba(234,33,45,0.1)',
      secondary : '#ea212d',
      tertiary : '#ffffff'
    }

    organization.fetchCurrentOrg().then(function (data) {
      if (data.custom_code) {
        switch (data.custom_code) {
          case 'hdfc':
            theme.apply(angular.extend(baseTheme, {
              primary : '#084c8d',
            }));
            break;

          case 'icici':
            theme.apply(angular.extend(baseTheme, {
              navBg : '#F07937',
              primary: '#0A3D6B',
            }));
            break;

          case 'bob':
            theme.apply(angular.extend(baseTheme, {
              primary : '#F04E00',
            }));
            break;
        }
      }
    });

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
]);

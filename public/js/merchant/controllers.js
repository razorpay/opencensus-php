'use strict';
/* Controllers */
angular
  .module('app.controllers', ['ngCookies'])
  .controller('AppCtrl', [
    '$scope',
    '$localStorage',
    '$window',
    'theme',
    'organization',
    function($scope, $localStorage, $window, theme, organization) {
      isSmartDevice($window) &&
        angular.element($window.document.body).addClass('smart');

      var baseTheme = {
        transparent: 'rgba(0,0,0,0.2)',
        transparentDark: 'rgba(0,0,0,0.4)',
        textLight: 'rgba(255,255,255,0.9)',
        primaryLight: '#e1f3ff',
        primaryTransparent: 'rgba(225,243,252,0.3)',
        errorBackground: 'rgba(234,33,45,0.1)',
        secondary: '#ea212d',
        tertiary: '#ffffff',
      };

      organization.fetchCurrentOrg().then(function(data) {
        if (data.custom_code) {
          switch (data.custom_code) {
            case 'hdfc':
              theme.apply(
                angular.extend(baseTheme, {
                  primary: '#084c8d',
                })
              );
              break;

            case 'icici':
              theme.apply(
                angular.extend(baseTheme, {
                  navBg: '#F07937',
                  primary: '#0A3D6B',
                })
              );
              break;

            case 'bob':
              theme.apply(
                angular.extend(baseTheme, {
                  navBg: '#FF5D27',
                  primary: '#F04E00',
                })
              );
              break;
          }
        }
      });

      // config
      $scope.app = {
        name: 'Razorpay',
        version: '0.9.1',
        today: new Date(),
        // for chart colors
        color: {
          primary: '#7266ba',
          info: '#23b7e5',
          success: '#27c24c',
          warning: '#fad733',
          danger: '#f05050',
          light: '#e8eff0',
          dark: '#3a3f51',
          black: '#1c2b36',
        },
        settings: {
          themeID: 9,
          navbarHeaderColor: 'bg-dark',
          navbarCollapseColor: 'bg-white-only',
          asideColor: 'bg-dark',
          headerFixed: true,
          asideFixed: true,
          asideFolded: false,
        },
      };
      // save settings to local storage
      if (angular.isDefined($localStorage.settings)) {
        $scope.app.settings = $localStorage.settings;
      } else {
        $localStorage.settings = $scope.app.settings;
      }
      $scope.$watch(
        'app.settings',
        function() {
          $localStorage.settings = $scope.app.settings;
        },
        true
      );

      function isSmartDevice($window) {
        // Adapted from http://www.detectmobilebrowsers.com
        var ua =
          $window.navigator.userAgent ||
          $window.navigator.vendor ||
          $window.opera;
        // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
        return /iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/.test(
          ua
        );
      }
      $scope.flag = false;
    },
  ]) //Application mode change controller
  .controller('modeCtrl', [
    '$scope',
    'modeFactory',
    'user',
    '$modal',
    function($scope, modeFactory, user, $modal) {
      $scope.modes = modeFactory.getModes;
      $scope.mode = modeFactory.getMode;
      $scope.selectMode = function(mode) {
        user.identity().then(function(data) {
          var userData = data;
          if (mode == 'live' && parseInt(userData.activated) !== 1) {
            var modalInstance = $modal.open({
              templateUrl: 'activationModalContent.html',
              controller: 'activationModalCtrl',
              size: 'sm',
            });
          } else {
            modeFactory.selectMode(mode);
          }
        });
      };
    },
  ])
  .controller('switchMerchantCtrl', [
    '$scope',
    '$http',
    '$state',
    'user',
    function($scope, $http, $state, $user) {
      $user.identity(true).then(function(data) {
        $scope.merchants = $.map(data.merchants || [], function(v) {
          return v;
        });
      });

      $scope.initRoleSelector = function(element) {
        element.on('select2:select', function(e) {
          var merchantId = e.params.data.id;

          if (merchantId) {
            var request = $http.get('/settings/merchants/switch/' + merchantId);
            request
              .success(function(data) {
                if (data.success) {
                  location.reload();
                } else {
                  $scope.alerts.addAlert('danger', null, true);
                }
              })
              .error(function() {
                $scope.alerts.addAlert('danger', null, true);
              });
          }
        });
      };
    },
  ])
  .controller('activationModalCtrl', [
    '$scope',
    '$modalInstance',
    function($scope, $modalInstance) {
      $scope.ok = function() {
        $modalInstance.close();
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('confirmModalCtrl', [
    '$scope',
    '$modalInstance',
    'message',
    function($scope, $modalInstance, message) {
      $scope.message = message;
      $scope.ok = function() {
        $modalInstance.close();
      };
      $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
      };
    },
  ]); //Rest controllers loaded from /public/js/controllers/*.js by grunt

// eslint-disable-next-line strict
'use strict';
/* Controllers */
/*global angular */
angular
  .module('app.controllers', ['ngCookies'])
  .controller('AppCtrl', [
    '$scope',
    '$localStorage',
    '$window',
    'theme',
    'organization',
    'tracking',

    ($scope, $localStorage, $window, theme, organization, tracking) => {
      isSmartDevice($window) && angular.element($window.document.body).addClass('smart');

      const baseTheme = {
        transparent: 'rgba(0,0,0,0.2)',
        transparentDark: 'rgba(0,0,0,0.4)',
        textLight: 'rgba(255,255,255,0.9)',
        primaryLight: '#e1f3ff',
        primaryTransparent: 'rgba(225,243,252,0.3)',
        errorBackground: 'rgba(234,33,45,0.1)',
        secondary: '#ea212d',
        tertiary: '#ffffff',
        bgCover: true,
        logoVisible: true,
      };

      organization.fetchCurrentOrg().then((data) => {
        if (data.custom_code) {
          switch (data.custom_code) {
            case 'hdfc':
              theme.apply(
                angular.extend(baseTheme, {
                  ...data.merchant_styles,
                  primary: '#084c8d',
                }),
              );
              break;

            case 'icic':
              theme.apply(
                angular.extend(baseTheme, {
                  ...data.merchant_styles,
                  navBg: '#F0F3F4',
                }),
              );
              break;

            case 'bob':
              theme.apply(
                angular.extend(baseTheme, {
                  navBg: '#FF5D27',
                  primary: '#F04E00',
                }),
              );
              break;

            case 'axis':
              theme.apply(
                angular.extend(baseTheme, {
                  primary: '#97144d',
                  navBgUrl: data.background_image_url,
                  bgCover: false,
                  logoVisible: false,
                }),
              );
              break;

            case 'rzp':
              break;

            default:
              theme.apply(
                angular.extend(baseTheme, {
                  ...data.merchant_styles,
                  navBgUrl: data.background_image_url,
                }),
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
          primary: '#528ff0',
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

        () => {
          $localStorage.settings = $scope.app.settings;
        },
        true,
      );

      // eslint-disable-next-line no-shadow
      function isSmartDevice($window) {
        // Adapted from http://www.detectmobilebrowsers.com
        const ua = $window.navigator.userAgent || $window.navigator.vendor || $window.opera;
        // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
        return /iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/.test(ua);
      }
      $scope.flag = false;
      tracking.pushEvents({
        event_name: 'landed',
        event_type: 'success',
      });
    },
  ]) //Application mode change controller
  .controller('modeCtrl', [
    '$scope',
    'modeFactory',
    'user',
    '$modal',

    ($scope, modeFactory, user) => {
      $scope.modes = modeFactory.getModes;
      $scope.mode = modeFactory.getMode;

      $scope.selectMode = (mode) => {
        user.identity().then((data) => {
          const userData = data;

          if (mode == 'live' && parseInt(userData.activated, 10) !== 1) {
            // empty
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

    ($scope, $http, $state, $user) => {
      $user.identity(true).then((data) => {
        /*global $ */
        $scope.merchants = $.map(data.merchants || [], (v) => {
          return v;
        });
      });

      $scope.initRoleSelector = (element) => {
        element.on('select2:select', (e) => {
          const merchantId = e.params.data.id;

          if (merchantId) {
            const request = $http.get(`/settings/merchants/switch/${merchantId}`);
            request

              .success((data) => {
                if (data.success) {
                  location.reload();
                } else {
                  $scope.alerts.addAlert('danger', null, true);
                }
              })

              .error(() => {
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

    ($scope, $modalInstance) => {
      $scope.ok = () => {
        $modalInstance.close();
      };

      $scope.cancel = () => {
        $modalInstance.dismiss('cancel');
      };
    },
  ])
  .controller('confirmModalCtrl', [
    '$scope',
    '$modalInstance',
    'message',

    ($scope, $modalInstance, message) => {
      $scope.message = message;

      $scope.ok = () => {
        $modalInstance.close();
      };

      $scope.cancel = () => {
        $modalInstance.dismiss('cancel');
      };
    },
  ]); //Rest controllers loaded from /public/js/controllers/*.js by grunt

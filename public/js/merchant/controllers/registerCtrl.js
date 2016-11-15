"use strict";
//Registration Controller
app.controller('RegisterCtrl', [
  '$scope',
  '$http',
  '$state',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  '$analytics',
  '$location',
  '$window',
  '$cookies',
  function ($scope, $http, $state, alertsFactory, user, transformRequestAsFormPost, $analytics, $location, $window, $cookies) {
    $scope.data = {};

    if ($location.search().email) {
      
      $scope.data.email = $location.search().email;

      // XHR to save this email in a generic table
      // so that even if the user doesn't signup we can
      // re-target him.
      var request = $http({
        method: 'post',
        url: '/user/track_lead',
        data: { email: $scope.data.email }
      });

      request.success(function (data) {
        if (data.success) {
          // do nothing
        }
      }).error(function () {
        // nothing to do
      });
    }

    if($location.search().invitation) {
      $scope.data.invitation = $location.search().invitation;
    }
    // We only track referers if they are registering a business
    else {
      if (typeof $window.google_trackConversion === 'function') {
        $window.google_trackConversion({
          google_conversion_id : 928471290,
          google_conversion_language : "en",
          google_conversion_format : "3",
          google_conversion_color : "ffffff",
          google_conversion_label : "CM9fCLm40GMQ-rHdugM",
          google_remarketing_only : false
        });
      }
    }

    //Intialise alerts and scope functions
    $scope.alerts = alertsFactory.getHandler();
    $scope.agree = false;

    // Referrer is set only if present
    if ($location.search().ref) {
      $scope.data.ref = $location.search().ref;
    }

    if (typeof $state.current.data.ref !== 'undefined') {
      $scope.data.ref = $state.current.data.ref;
    }

    $scope.submit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }
      if (!$scope.agree) {
        $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);
        return true;
      }
      if (window.location.hostname !== 'dashboard.razorpay.com' && window.location.hostname !== 'betadashboard.razorpay.com' && !$scope.data.captcha) {
        $scope.data.captcha = 'Faked';
      }
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/user/register',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          $cookies.show_rzp_welcome_guide = true;
          $analytics.eventTrack('signUp', {
            id: data.data.id,
            name: data.data.name,
            email: data.data.email
          });
          if(data.data.login) {
            user.identity(true);
            $state.go('app.dashboard');
          }
          else {
            $scope.alerts.addAlert('success', 'Registration Successful. Please check your inbox for confirmation email from Razorpay.', true);
          }
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (error) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };

    $scope.submitLead = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }
      if (!$scope.agree) {
        $scope.alerts.addAlert('danger', 'You must agree to the terms & conditions for using our service', true);
        return true;
      }
      if (window.location.hostname !== 'dashboard.razorpay.com' && window.location.hostname !== 'betadashboard.razorpay.com' && !$scope.data.captcha) {
        $scope.data.captcha = 'Faked';
      }
      $scope.alerts.resetAlerts();
      var request = $http({
        method: 'post',
        url: '/user/register_lead',
        transformRequest: transformRequestAsFormPost,
        data: $scope.data
      });
      request.success(function (data) {
        if (data.success) {
          $cookies.show_rzp_welcome_guide = true;
          $analytics.eventTrack('signUp', {
            id: data.data.id,
            name: data.data.name,
            email: data.data.email
          });
          if(data.data.login) {
            user.identity(true);
            $state.go('app.dashboard');
          }
          else {
            $scope.alerts.addAlert('success', 'Registration Successful. Please check your inbox for confirmation email from Razorpay.', true);
          }
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (error) {
            $scope.alerts.addAlert('danger', error);
          });
        }
      }).error(function () {
        $scope.alerts.addAlert('danger', null, true);
      });
    };
  }
]);

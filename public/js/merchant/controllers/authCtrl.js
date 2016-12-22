//Signin Controller
app.controller('AuthCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  '$location',
  'alertsFactory',
  'user',
  'transformRequestAsFormPost',
  '$analytics',
  '$window',
  '$cookies',
  function ($scope, $http, $state, $stateParams, $location, alertsFactory, user, 
    transformRequestAsFormPost, $analytics, $window, $cookies) {

    $scope.data = {};
    $scope.alerts = alertsFactory.getHandler();
    $scope.right = false;
    $scope.signup = {
      currentStep: 1, // 0, 1, 2, 3
      currentSubStep: 0, // 0, 1, 2, 3, 4
      data: {
        email: $location.search().email || '',
        password: '',
        captcha: null,
      }
    }

    $scope.goToStep = function (step, subStep) {
      $scope.signup.currentStep = step;
      $scope.signup.currentSubStep = subStep;
    }

    $scope.createAccount = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }

      if (window.location.hostname !== 'dashboard.razorpay.com' 
          && window.location.hostname !== 'betadashboard.razorpay.com' 
          && !$scope.signup.data.captcha) {
        $scope.signup.data.captcha = 'Faked';
      }
    }

    $scope.goToSigninLayout = function () {
      $scope.right = true;
      const toRoute = 'access.signin';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }

    $scope.goToSignupLayout = function () {
      $scope.right = false;
      const toRoute = 'access.signup';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }


  }
]);

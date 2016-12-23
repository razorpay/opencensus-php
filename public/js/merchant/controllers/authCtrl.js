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
    
    // todo check route and go to login/signup/forgotpwd layout

    $scope.toArray = function (obj) {
      if (!obj) {
        return [];
      }
      return Object.keys(obj);
    }
    $scope.data = {};
    $scope.alerts = alertsFactory.getHandler();
    $scope.right = false;
    $scope.signup = {
      currentStep: 2, // 0, 1, 2, 3
      currentSubStep: 0, // 0, 1, 2, 3, 4
      data: {
        email: $location.search().email || '',
        password: '',
        captcha: null,
      },
      merchantData: {
        business_type: null,
        monthly_transaction: null,
        role: null,
        department: null,
        business_name: '',
        phone: '',
        person_name: '',
      },
      details: {
        business_type: {
          1 : 'Proprietership',
          2 : 'Individual',
          3 : 'Partnership',
          4 : 'Private Limited',
          5 : 'Public Limited',
          6 : 'LLP',
          7 : 'NGO',
          8 : 'Educational Institutes',
          9 : 'Trust',
          10: 'Society',
        },

        monthly_transaction: {
          1: 'Less than 1 Lac',
          2: '1 Lac to 10 Lacs',
          3: '10 Lacs to 1 Crore',
          4: 'More than 1 Crore'
        },

        role: {
          1: 'Founder / Co-founder',
          2: 'C-level / SVP',
          3: 'VP / Director / Head',
          4: 'Manager',
          5: 'Individual Contributor',
          6: 'Others',
        },

        department: {
          1: 'Engineering',
          2: 'Product',
          3: 'Business',
          4: 'Finance',
          5: 'Strategy',
          6: 'Others',
        },
      },
      showMore: false,
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
      // todo call signup api here
      var payload = {
        method: 'post',
        url: '/user/register',
        transformRequest: transformRequestAsFormPost,
        data: $scope.signup.data
      }

      payload.data.password_confirmation = payload.data.password
      payload.data.business_name = ''

      var request = $http(payload);
      request.success(function (data) {
        if (data.success) {
          $scope.goToStep(1)
        }
        // todo show error in alert
      })
    }

    $scope.sendDetails = function () {
      var payload = {
        method: 'post',
        url: '/user/pre_signup',
        transformRequest: transformRequestAsFormPost,
        data: $scope.signup.merchantData
      }
      debugger
      var request = $http(payload);
      request.success(function (data) {
        debugger
      })
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

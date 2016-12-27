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

    $scope.toArray = function (obj) {
      if (!obj) {
        return [];
      }
      return Object.keys(obj);
    }
    $scope.data = {};
    $scope.alerts = alertsFactory.getHandler();
    $scope.right = false; // login layout ? right is true : right is false
    
    // signup state container
    $scope.signup = {
      currentStep: 0, // 0, 1, 2, 3
      currentSubStep: 0, // 0, 1, 2, 3, 4
      data: {
        email: $location.search().email || '',
        password: '',
        captcha: null,
      },
      merchantData: {
        business_type: null,
        monthly_transaction: 1,
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

    $scope.goToSignupStep = function (step, subStep) {
      $scope.signup.currentStep = step;
      if (subStep !== undefined) {
        $scope.signup.currentSubStep = subStep;
      }
    }

    $scope.goToLoginStep = function (step, subStep) {
      $scope.login.currentStep = step;
      if (subStep !== undefined) {
        $scope.login.currentSubStep = subStep;
      }
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

      // todo show spinner
      var payload = {
        method: 'post',
        url: '/user/register',
        transformRequest: transformRequestAsFormPost,
        data: $scope.signup.data
      }

      payload.data.password_confirmation = payload.data.password
      payload.data.business_name = ''

      var request = $http(payload);
      showSpinner()
      request.success(function (data) {
        hideSpinner()
        if (data.success) {
          // todo hide login button because user is logged in
          hideLoginBtn()
          $scope.goToSignupStep(1)
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
    }

    function showSpinner() {
      $('.loading-animation').addClass('active')
    }

    function hideSpinner() {
      $('.loading-animation').removeClass('active')
    }

    function hideLoginBtn() {
      $('.btn-layout-change').hide()
    }

    function goToPostSignup () {
      $scope.goToSignupStep(2)    
    }
    // todo get post-signup details and check whether post-signup steps are needed

    $scope.sendDetails = function () {
      var payload = {
        method: 'post',
        url: '/user/pre_signup',
        transformRequest: transformRequestAsFormPost,
        data: $scope.signup.merchantData
      }
      var request = $http(payload);
      // todo show spinner
      request.success(function (data) {
        // todo handle success
        if (data.success || 1) {
          if ($scope.signup.currentSubStep == 4) {
            $scope.goToSignupStep(3)
          } else {
            $scope.goToSignupStep(2, $scope.signup.currentSubStep + 1)
          }
        }
      })
    }

    $scope.goToSigninLayout = function (noTransition) {
      $scope.goToSignupStep(0); // reset signup step
      $scope.right = true;
      const toRoute = 'access.signin';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }

    $scope.goToSignupLayout = function () {
      $scope.goToLoginStep(0); // reset login step
      $scope.right = false;
      const toRoute = 'access.signup';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }

    // login state container
    $scope.login = {
      data: {
        email: '',
        password: '',
      },
      currentStep: 0, // 0 -> login/otp, 1 -> forgotpwd
      currentSubStep: 0, // 0 -> email+pwd, 1 -> otp
    }

    if (['access.signin', 'access.forgotpwd'].indexOf($state.current.name) !== -1) {
      $('.auth-container').addClass('no-transition')
      $scope.right = true;
      setTimeout(function () {
        $('.auth-container').removeClass('no-transition')
      }, 200)
      if ($state.current.name === 'access.forgotpwd') {
        $scope.login.currentStep = 1;
      }
    }

    $scope.goToForgotPwd = function () {
      const toRoute = 'access.forgotpwd';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
      $scope.login.currentStep = 1;  
    }

  }
]);

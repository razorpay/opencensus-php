//Signin Controller
app.controller('AuthCtrl', [
  '$scope',
  '$http',
  '$state',
  '$stateParams',
  '$location',
  'alertsFactory',
  'user',
  'organization',
  'transformRequestAsFormPost',
  '$analytics',
  '$window',
  '$cookies',
  function ($scope, $http, $state, $stateParams, $location, alertsFactory, user, 
    organization, transformRequestAsFormPost, $analytics, $window, $cookies) {
    $scope.toArray = function (obj) {
      if (!obj) {
        return [];
      }
      return Object.keys(obj);
    }
    $scope.data = {};
    $scope.alerts = alertsFactory.getHandler();
    $scope.right = false; // login layout ? right is true : right is false
    
    $scope.organization = {};
    organization.fetchCurrentOrg().then(function (data) {
      $scope.login_logo = data.login_logo_url || 'img/logo_full.png'; 
      $scope.organization = data;
    });

    // $scope.signupDisabled = true;
    $scope.isLoggedIn = false;

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
        transaction_volume: null,
        role: null,
        department: null,
        business_name: '',
        phone: '',
        contact_name: '',
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

        transaction_volume: {
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
          // hide login button because user is logged in
          hideLoginBtn()
          $scope.isLoggedIn = true;
          $scope.goToSignupStep(1)
          $state.transitionTo('access.pre_signup', {}, {
            notify: false,
          });
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

    $scope.sendDetails = function () {
      // pushToDrip()
      var payload = {
        method: 'post',
        url: '/user/pre_signup',
        transformRequest: transformRequestAsFormPost,
        data: $scope.signup.merchantData
      }
      var request = $http(payload);
      // todo show spinner
      showSpinner()
      request.success(function (data) {
        // todo handle success
        hideSpinner()
        if (data.success) {
          if ($scope.signup.currentSubStep == 4) {
            goToVerification()
          } else {
            $scope.goToSignupStep(2, $scope.signup.currentSubStep + 1)
          }
        }
      })
    }

    function goToVerification() {      
      if (!$scope.right) {
        $scope.goToSignupStep(3)
      } else {
        $scope.goToLoginStep(3)
      }
    }

    function pushToDrip() {
      const payload = {email: $scope.signup.data.email}
      if (!payload.email) {
        return
      }
      const keys = ['business_type','transaction_volume','role','department','business_name','phone','contact_name']
      keys.forEach(function (key){
        if ($scope.signup.merchantData[key]) {
          payload[key] = $scope.signup.details[key] 
          // multi select field
          ? $scope.signup.details[key][$scope.signup.merchantData[key]] 
          // string field
          : $scope.signup.merchantData[key]
        }
      })
      try {
        // try-catch, since there could be tracker blocking scripts
        _dcq.push(["identify", payload]);
      } catch (e) {}
    }

    $scope.resendVerificationEmail = function () {
      // todo pick data from login if on login side
      var source = $scope.right ? 'login' : 'signup';
      var data = {
        email: $scope[source].data.email,
        password: $scope[source].data.password
      }
      var payload = {
        method: 'post',
        url: '/user/resend',
        transformRequest: transformRequestAsFormPost,
        data: data
      }
      var request = $http(payload);
      // todo verify fields to be sent in api
      request.success(function (data) {
        $scope.alerts.resetAlerts();
        if (data.success) {
          $scope.alerts.addAlert('success', 'Confirmation mail re-sent, please check your inbox.', true);
        } else {
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
    }


    $scope.goToSigninLayout = function (noTransition) {
      $scope.goToSignupStep(0); // reset signup step
      $scope.goToLoginStep(1); // reset login step
      $scope.right = true;
      const toRoute = 'access.signin';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
    }

    $scope.goToSignupLayout = function () {
      $scope.goToSignupStep(0); // reset signup step
      $scope.goToLoginStep(1); // reset login step
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
      currentStep: 1, // 2 -> questions, 1 -> login, 0 -> forgotpwd
      currentSubStep: 0, // 0 -> email+pwd
    }

    if ($state.current.name === 'access.pre_signup') {
      Object.assign($scope.signup.merchantData, (user.getIdentity() && user.getIdentity().pre_signup))
    }

    if (['access.signin', 'access.forgotpwd', 'access.pre_signup'].indexOf($state.current.name) !== -1) {
      $scope.right = true;
      if ($state.current.name === 'access.signin') {
        if (!user.isAuthenticated()) {
          $scope.login.currentStep = 1;
        } else {
          $state.transitionTo('access.pre_signup', {}, {
            notify: false,
          });
          $scope.isLoggedIn = true;
          $scope.login.data.email = (user.getIdentity() && user.getIdentity().email)
          if (!user.isPreSignupDone()) {
            $scope.login.currentStep = 2;
          } else {
            // email not verified case
            $scope.login.currentStep = 3;
          }
        }
      } else if ($state.current.name === 'access.forgotpwd') {
        $scope.login.currentStep = 0;
      } else if ($state.current.name === 'access.pre_signup') {
        if (!user.isAuthenticated()) {
          $state.transitionTo('access.signin', {}, {
            notify: false,
          });
          $scope.login.currentStep = 1;
        } else {
          $scope.isLoggedIn = true;
          $scope.login.currentStep = 2;
        }
      }
    }

    $scope.goToForgotPwd = function () {
      const toRoute = 'access.forgotpwd';
      $state.transitionTo(toRoute, {}, {
        notify: false,
      });
      $scope.login.currentStep = 0;
    }

    $scope.sendLoginCredentials = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }

      // todo show spinner
      var payload = {
        method: 'post',
        url: '/user/signin',
        transformRequest: transformRequestAsFormPost,
        data: $scope.login.data
      }

      var request = $http(payload);
      showSpinner()
      request.success(function (data) {
        hideSpinner()
        if (data.success) {
          // todo hide login button because user is logged in
          hideLoginBtn()
          // check questions have been answered or not
          user.identity(true).then(function(user) {
            if (user.isPreSignupDone) {
              var role = user.merchants[user.id].pivot.role;

              switch (role) {
                case 'support':
                  $state.go('app.payments.list');
                  break;
                case 'sellerapp':
                  $state.go('app.invoices');
                  break;
                default:
                  $state.go('app.dashboard');
              }
            } else {
              $state.transitionTo('access.pre_signup', {}, {
                notify: false,
              });
              Object.assign($scope.signup.merchantData, user.pre_signup)
              $scope.isLoggedIn = true;
              $scope.login.currentStep = 2;  
            }
          });
          // $scope.goToSignupStep(1)
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
    }

    $scope.forgotPwdSubmit = function ($valid) {
      if (!$valid) {
        $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
        return true;
      }

      // todo show spinner
      var payload = {
        method: 'post',
        url: '/user/password/reset',
        transformRequest: transformRequestAsFormPost,
        data: {
          email: $scope.login.data.email
        }
      }

      var request = $http(payload);
      request.success(function (data) {
        if (data.success) {
          $scope.alerts.addAlert('success', 'Reset request sent. Please check your inbox for verification email from Razorpay.');
        } else {
          $scope.alerts.resetAlerts();
          angular.forEach(data.errors, function (value, key) {
            $scope.alerts.addAlert('danger', value);
          });
        }
      })
    }

    $scope.logoutAndGoToLogin = function () {
      var request = $http({
        method: 'get',
        url: '/user/logout'
      });
      request.finally(function () {
        user.identity(true);
        $scope.isLoggedIn = false;
        $state.transitionTo('access.signin', {}, {
          notify: false,
        });
        $scope.login.currentStep = 1; 
        $scope.right = true; 
      });
      return request;
    }

  }
]).directive('overrideTab', ['$window', function ($window) {
    return function (scope, element, attrs) {
      element.bind('keydown', function (e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode == 9) {
          e.preventDefault();
        }
      });
    };
  }]);
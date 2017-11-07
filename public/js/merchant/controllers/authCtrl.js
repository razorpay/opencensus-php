'use strict';
//Signin Controller
app
  .controller('AuthCtrl', [
    '$scope',
    '$timeout',
    '$http',
    '$state',
    '$stateParams',
    '$location',
    'alertsFactory',
    'user',
    'organization',
    'transformRequestAsFormPost',
    '$window',
    '$localStorage',
    function(
      $scope,
      $timeout,
      $http,
      $state,
      $stateParams,
      $location,
      alertsFactory,
      user,
      organization,
      transformRequestAsFormPost,
      $window,
      $localStorage
    ) {
      $scope.toArray = function(obj) {
        if (!obj) {
          return [];
        }
        return Object.keys(obj);
      };
      $scope.data = {};
      $scope.alerts = alertsFactory.getHandler();
      $scope.rightLayout = false; // login layout ? right is true : right is false
      $scope.lockme = false; // only turns true for lockme route

      $scope.organization = {};
      $scope.isOrgCheckDone = false;
      organization.fetchCurrentOrg().then(function(data) {
        $scope.login_logo = data.login_logo_url || 'img/logo_full.png';
        $scope.isOrgCheckDone = true;
        $scope.organization = data;
      });
      $scope.forms = {};

      $scope.isLoggedIn = false;

      // signup state container
      $scope.signup = {
        currentStep: 0, // 0, 1, 2
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
          contact_mobile: '',
          contact_name: '',
        },
        details: {
          business_type: {
            1: 'Private Limited',
            2: 'Proprietorship',
            3: 'Partnership',
            4: 'Individual',
            5: 'Not yet registered',
            6: 'Public Limited',
            7: 'LLP',
            8: 'Educational Institutes',
            9: 'Trust / Society',
            10: 'NGO',
            11: 'Other',
          },

          transaction_volume: {
            1: "Haven't started processing yet",
            2: 'Less than 5 Lac',
            3: '5 Lacs to 25 Lacs',
            4: '25 Lacs to 50 Lacs',
            5: '50 Lacs to 1 Crore',
            6: 'More than 1 Crore',
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
      };

      $scope.goToSignupStep = function(step, subStep) {
        $scope.signup.currentStep = step;
        if (subStep !== undefined) {
          $scope.signup.currentSubStep = subStep;
        }
      };

      $scope.goToLoginStep = function(step, subStep) {
        $scope.login.currentStep = step;
        if (subStep !== undefined) {
          $scope.login.currentSubStep = subStep;
        }
      };

      // Referrer is set only if present
      if ($location.search().ref) {
        $scope.signup.data.ref = $location.search().ref;
      }

      if (typeof $state.current.data.ref !== 'undefined') {
        $scope.signup.data.ref = $state.current.data.ref;
      }

      if ($location.search().invitation) {
        $scope.signup.data.invitation = $location.search().invitation;

        // Get invitation details
        var data = {
          route_name: 'invitation_fetch_by_token',
          url_params: {
            '{token}': $scope.signup.data.invitation,
          },
        };
        var request = $http.get('/guest/generic', {
          params: data,
        });
        request
          .success(function(data) {
            if (data.success) {
              $scope.signup.data.email = data.data.email;
              $scope.lock_email = data.data.email ? true : false;
            } else {
              $state.transitionTo('access.signin');
            }
          })
          .error(function() {});
      } else if ($location.search().merchant_invitation) {
        // heimdall specific
        $scope.signup.data.merchant_invitation = $location.search().merchant_invitation;

        // Get invitation details
        $http({
          url: '/admin/generic',

          method: 'GET',

          params: {
            route_name: 'admin_lead_verify',

            url_params: {
              '{token}': $scope.signup.data.merchant_invitation,
            },
          },
        })
          .success(function(data) {
            if (data.success) {
              var form_data = data.data.form_data;

              $scope.signup.data.email = data.data.email;
              $scope.lock_email = data.data.email ? true : false;
              $scope.signup.merchantData.business_name =
                form_data.merchant_name;
              $scope.signup.merchantData.contact_name = form_data.contact_name;
              $scope.merchant_invitation = true;
            }
          })
          .error(function() {});
      } else {
        // We only track referers if they are registering a business
        if (typeof $window.google_trackConversion === 'function') {
          $window.google_trackConversion({
            google_conversion_id: 928471290,
            google_conversion_language: 'en',
            google_conversion_format: '3',
            google_conversion_color: 'ffffff',
            google_conversion_label: 'CM9fCLm40GMQ-rHdugM',
            google_remarketing_only: false,
          });
        }
      }

      $scope.createAccount = function($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        pushToDrip();

        if (
          window.location.hostname !== 'dashboard.razorpay.com' &&
          window.location.hostname !== 'betadashboard.razorpay.com' &&
          !$scope.signup.data.captcha
        ) {
          $scope.signup.data.captcha = 'Faked';
        }

        var payload = {
          method: 'post',
          url: '/user/register',
          transformRequest: transformRequestAsFormPost,
          data: $scope.signup.data,
        };

        payload.data.password_confirmation = payload.data.password;
        payload.data.business_name = payload.data.business_name || '';

        $scope.alerts.resetAlerts();
        var request = $http(payload);
        showSpinner();
        request.success(function(data) {
          if (data.success) {
            $localStorage.new_user_signup = true;
            $scope.signup.account_type = $scope.signup.data.invitation
              ? 'team_member'
              : 'merchant';
            trackDrip('account_created');
            pushToDrip();
            $scope.isLoggedIn = true;
            user.identity(true).then(function(data) {
              if (data.user.confirmed) {
                $scope.goToDashboard(data.user.merchants[0].role);
              } else {
                hideSpinner();
                $state.transitionTo(
                  'access.pre_signup',
                  {},
                  {
                    notify: false,
                  }
                );
                $scope.goToSignupStep(1);
              }
            });
          } else {
            hideSpinner();
            if (
              data.errors &&
              data.errors[0] &&
              data.errors[0].indexOf('email has already been taken') !== -1
            ) {
              trackDrip('error_email_taken');
            }

            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      function showSpinner() {
        $('.loading-animation').addClass('active');
      }

      function hideSpinner() {
        $('.loading-animation').removeClass('active');
      }

      $scope.goToDashboard = function(role) {
        location.hash = '/app';
        location.reload();
      };

      $scope.sendDetails = function() {
        pushToDrip();
        var payload = {
          method: 'post',
          url: '/user/pre_signup',
          transformRequest: transformRequestAsFormPost,
          data: $scope.signup.merchantData,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        showSpinner();

        request.success(function(data) {
          hideSpinner();
          if (data.success) {
            trackDrip('signup_flow_completed');
            pushToDrip();
            // if verification is already done, go to dashboard (call /user again to check)
            user.identity(true).then(function(userDetails) {
              // user.authorize and then if email verified
              if (user.isVerified()) {
                var role = userDetails.merchants[userDetails.id].role;
                $scope.goToDashboard(role);
              } else {
                goToVerification();
              }
            });
            // else
          } else {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      $scope.quickSendDetails = function(detailField, key) {
        $scope.signup.merchantData[detailField] = key;
        pushToDrip();
        var payload = {
          method: 'post',
          url: '/user/pre_signup',
          transformRequest: transformRequestAsFormPost,
          data: $scope.signup.merchantData,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();

        $timeout(function() {
          $scope.goToSignupStep(1, $scope.signup.currentSubStep + 1);
        }, 200);
        request.success(function(data) {
          if (!data.success) {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
          $('.business-type-substep').scrollTop(0);
          $timeout(function() {
            $scope.signup.showMore = false;
          }, 200);
        });
      };

      function goToVerification() {
        if (!$scope.rightLayout) {
          $scope.goToSignupStep(2);
        } else {
          $scope.goToLoginStep(3);
        }
      }

      var trackDrip = function(action) {
        if (!action) return;
        if (location.host !== 'dashboard.razorpay.com') return;
        try {
          _dcq.push(['track', action]);
        } catch (e) {}
      };

      var pushToDrip = (function() {
        var dataSent = {};
        return function(tag) {
          // send only on production
          if (location.host !== 'dashboard.razorpay.com') {
            return;
          }
          var data = {};
          var payload = {};
          var keys = [
            'business_type',
            'transaction_volume',
            'role',
            'department',
            'business_name',
            'contact_mobile',
            'contact_name',
          ];
          keys.forEach(function(key) {
            if ($scope.signup.merchantData[key]) {
              data[key] = $scope.signup.details[key]
                ? $scope.signup.details[key][$scope.signup.merchantData[key]]
                : $scope.signup.merchantData[key];
            }
          });
          if ($scope.signup.account_type)
            data.account_type = $scope.signup.account_type;
          data.source = $location.search().utm_source || document.referrer;

          for (var key in data) {
            if (data[key] !== dataSent[key]) {
              payload[key] = data[key];
              dataSent[key] = data[key];
            }
          }
          payload.email = $scope.signup.data.email;
          if (!payload.email) {
            return;
          }
          if (tag) {
            payload.tags = [tag];
          }
          try {
            /* global _dcq */
            // try-catch, since there could be tracker blocking scripts
            _dcq.push(['identify', payload]);
          } catch (e) {}
        };
      })();

      // creates Drip lead if email present in params
      pushToDrip('email_only');

      $scope.goToSigninLayout = function() {
        $scope.goToSignupStep(0); // reset signup step
        $scope.goToLoginStep(1); // reset login step
        $scope.rightLayout = true;
        $scope.login.data.email = $scope.signup.data.email;
        $scope.alerts.resetAlerts();
        var toRoute = 'access.signin';
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          }
        );
      };

      $scope.goToSignupLayout = function() {
        $scope.goToSignupStep(0); // reset signup step
        $scope.goToLoginStep(1); // reset login step
        $scope.rightLayout = false;
        $scope.signup.data.email = $scope.login.data.email;
        $scope.email_not_verified = false;
        $scope.alerts.resetAlerts();
        var toRoute = 'access.signup';
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          }
        );
      };

      // login state container
      $scope.login = {
        data: {
          email: '',
          password: '',
        },
        currentStep: 1, // 3 -> verification, 2 -> questions, 1 -> login, 0 -> forgotpwd
        currentSubStep: 0, // 0 -> email+pwd, 1 -> provision for OTP screen
      };

      if (
        [
          'access.signin',
          'access.forgotpwd',
          'access.pre_signup',
          'access.lockme',
        ].indexOf($state.current.name) !== -1
      ) {
        $scope.rightLayout = true;
        if (
          $state.current.name === 'access.signin' ||
          $state.current.name === 'access.lockme'
        ) {
          if (!user.isAuthenticated()) {
            $scope.login.currentStep = 1;
            if ($state.current.name === 'access.lockme') {
              $scope.lockme = true;
              $scope.login.data.email = $stateParams.email;
            }
          } else {
            user.identity().then(function(userDetails) {
              $scope.isLoggedIn = true;
              $scope.login.data.email = userDetails.email;
              if (!user.isPreSignupDone()) {
                if (userDetails) {
                  Object.assign(
                    $scope.signup.merchantData,
                    userDetails.pre_signup
                  );
                }
                goToRelevantQuestion();
                $scope.login.currentStep = 2;
                $state.transitionTo(
                  'access.pre_signup',
                  {},
                  {
                    notify: false,
                  }
                );
              } else if (!user.isVerified()) {
                goToVerification();
              } else {
                var role = userDetails.merchants[userDetails.id].role;
                $scope.goToDashboard(role);
              }
            });
          }
        } else if ($state.current.name === 'access.forgotpwd') {
          $scope.login.currentStep = 0;
        } else if ($state.current.name === 'access.pre_signup') {
          if (!user.isAuthenticated()) {
            $state.transitionTo(
              'access.signin',
              {},
              {
                notify: false,
              }
            );
            $scope.login.currentStep = 1;
          } else {
            user.identity().then(function(userDetails) {
              // if pre sign up pending
              $scope.isLoggedIn = true;
              $scope.login.data.email = userDetails.email;
              if (!user.isPreSignupDone()) {
                Object.assign(
                  $scope.signup.merchantData,
                  userDetails.pre_signup
                );
                $scope.login.currentStep = 2;
                goToRelevantQuestion();
              } else if (!user.isVerified()) {
                goToVerification();
              }
            });
          }
        }
      } else if ($state.current.name === 'access.signup') {
        if (user.isAuthenticated()) {
          $state.transitionTo(
            'access.pre_signup',
            {},
            {
              notify: false,
            }
          );
          var userDetails = user.getIdentity();
          $scope.rightLayout = true;
          $scope.isLoggedIn = true;
          $scope.login.data.email = userDetails.email;
          Object.assign($scope.signup.merchantData, userDetails.pre_signup);
          $scope.login.currentStep = 2;
          goToRelevantQuestion();
        }
      }

      function goToRelevantQuestion() {
        var merchantData = $scope.signup.merchantData;
        $scope.noTransition = true;
        if (!merchantData.business_type) {
          $scope.signup.currentSubStep = 0;
        } else if (!merchantData.transaction_volume) {
          $scope.signup.currentSubStep = 1;
        } else if (!merchantData.role) {
          $scope.signup.currentSubStep = 2;
        } else if (!merchantData.department) {
          $scope.signup.currentSubStep = 3;
        } else {
          $scope.signup.currentSubStep = 4;
        }
        $timeout(function() {
          $scope.noTransition = false;
        }, 0);
      }

      $scope.goToForgotPwd = function() {
        var toRoute = 'access.forgotpwd';
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          }
        );
        $scope.login.currentStep = 0;
      };

      $scope.sendLoginCredentials = function($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        var payload = {
          method: 'post',
          url: '/user/signin',
          transformRequest: transformRequestAsFormPost,
          data: $scope.login.data,
        };

        var request = $http(payload);
        showSpinner();
        $scope.alerts.resetAlerts();
        request.success(function(data) {
          if (data.success) {
            // check questions have been answered or not
            user.identity(true).then(function(userDetails) {
              if (user.isVerified() && user.isPreSignupDone()) {
                var role =
                  userDetails.merchants &&
                  userDetails.merchants[userDetails.id].role;
                if ($state.params.next !== undefined) {
                  var next = $state.params.next;
                  var parser = document.createElement('a');
                  parser.href = $state.params.next;

                  var hostname = parser.hostname || window.location.hostname;

                  if (/^(beta-auth|auth).razorpay.(com|dev)$/.test(hostname)) {
                    window.location.href = parser.href;
                    return false;
                  }
                }
                $scope.goToDashboard(role);
              } else {
                $scope.isLoggedIn = true;
                hideSpinner();
                if (userDetails) {
                  $scope.login.data.email = userDetails.email;
                  Object.assign(
                    $scope.signup.merchantData,
                    userDetails.pre_signup
                  );
                }
                if (!user.isPreSignupDone()) {
                  $scope.email_not_verified = false;
                  goToRelevantQuestion();
                  $scope.login.currentStep = 2;
                  $state.transitionTo(
                    'access.pre_signup',
                    {},
                    {
                      notify: false,
                    }
                  );
                } else if (!user.isVerified()) {
                  goToVerification();
                }
              }
            });
          } else {
            hideSpinner();
            if (data.errors[0].includes('email not confirmed')) {
              // go to email not verified screen
              $scope.email_not_verified = true;
              $scope.login.currentStep = 2;
            } else {
              angular.forEach(data.errors, function(value, key) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          }
        });
      };

      $scope.resendVerificationEmail = function() {
        var payload = {
          method: 'post',
          url: '/user/resend',
          transformRequest: transformRequestAsFormPost,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        request.success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert('success', 'Verification email resent.');
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      $scope.forgotPwdSubmit = function($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        var data = {
          route_name: 'user_reset_password_create',
          body: {
            email: $scope.login.data.email,
          },
        };
        var request = $http({
          method: 'post',
          url: '/guest/generic',
          data: data,
        });
        request.success(function(data) {
          if (data.success) {
            $scope.alerts.addAlert(
              'success',
              'Reset request sent. Please check your inbox for verification email from Razorpay.'
            );
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }
        });
      };

      $scope.logoutAndGoToLogin = function() {
        var request = $http({
          method: 'get',
          url: '/user/logout',
        });
        request.finally(function() {
          user.identity(true);
          // go to login
          $scope.rightLayout = true;
          $state.transitionTo(
            'access.signin',
            {},
            {
              notify: false,
            }
          );

          // reset scope variables
          $scope.alerts.resetAlerts();
          $scope.isLoggedIn = false;
          $scope.lockme = false;
          $scope.login.currentStep = 1;
          $scope.login.data.email = $scope.signup.data.email;
          $scope.signup.currentStep = 0;
          $scope.signup.currentSubStep = 0;
          $scope.signup.data.email = '';
          $scope.signup.data.password = '';
          $scope.signup.data.captcha = null;
          $scope.signup.merchantData.business_type = null;
          $scope.signup.merchantData.transaction_volume = null;
          $scope.signup.merchantData.role = null;
          $scope.signup.merchantData.department = null;
          $scope.signup.merchantData.business_name = '';
          $scope.signup.merchantData.contact_mobile = '';
          $scope.signup.merchantData.contact_name = '';
          $scope.email_not_verified = false;
          $scope.forms.signupForm.$setPristine();
          $scope.forms.detailsForm.$setPristine();
        });
        return request;
      };
    },
  ])
  .directive('overrideTab', [
    '$window',
    function() {
      return function(scope, element) {
        element.bind('keydown', function(e) {
          var keyCode = e.keyCode || e.which;
          if (keyCode == 9) {
            e.preventDefault();
          }
        });
      };
    },
  ]);

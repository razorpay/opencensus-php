'use strict';

//Signin Controller
app
  .factory('authCallbacks', [
    function() {
      var signinCallback = null;

      return {
        getSigninCallback: function() {
          return signinCallback;
        },
        setSigninCallback: function(cb) {
          return typeof cb === 'function' && (signinCallback = cb);
        },
      };
    },
  ])
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
    'utils',
    'isHostedInBB',
    'appHost',
    'authCallbacks',
    '$sce',
    '$filter',
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
      $localStorage,
      utils,
      isHostedInBB,
      appHost,
      authCallbacks,
      $sce,
      $filter
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

      /*
        $scope.login = {
          data: {email: $location.search().email || ''}
        };
      */

      $scope.organization = {};
      $scope.isOrgCheckDone = false;
      organization.fetchCurrentOrg().then(function(data) {
        $scope.login_logo = data.login_logo_url || 'img/logo_full.png';
        $scope.isOrgCheckDone = true;
        $scope.organization = data;
        $scope.isOrgRZP = $scope.organization.custom_code === 'rzp';
        $scope.isOrgHDFC = $scope.organization.custom_code === 'hdfc';
      });
      $scope.forms = {};

      $scope.isLoggedIn = false;

      // Less restrictive url regex
      $scope.websiteRegex = /^((http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*))?$/gi;

      // signup state container
      var email = $location.search().email;
      var role = $location.search().r;
      try {
        email = atob(decodeURIComponent(email));
      } catch (e) {
        email = '';
      }
      $scope.signup = {
        currentStep: 0, // 0, 1, 2
        currentSubStep: 0, // 0, 1, 2, 3, 4
        settings: {
          partner_intent: role === 'partner',
        },
        data: {
          email: email,
          password: '',
          captcha: null,
        },
        merchantData: {
          business_type: null,
          transaction_volume: null,
          department: null,
          business_name: '',
          business_website: '',
          contact_mobile: '',
          contact_name: '',
        },
        details: {
          business_type: {
            1: {
              name: 'Not Yet Registered',
              value: 11,
            },
            // 2: {
            //   name: 'Individual',
            //   isIndividual: true,
            //   value: 2,
            // },
            3: {
              name: 'Proprietorship',
              value: 1,
            },
            4: {
              name: 'Private Limited',
              value: 4,
            },
            5: {
              name: 'Partnership',
              value: 3,
            },
            6: {
              name: 'Public Limited',
              value: 5,
            },
            7: {
              name: 'LLP',
              value: 6,
            },
            9: {
              name: 'Trust',
              value: 9,
            },
            10: {
              name: 'Society',
              value: 10,
            },
            11: {
              name: 'NGO',
              value: 7,
            },
          },

          transaction_volume: {
            1: "Haven't started processing yet",
            2: 'Less than 5 Lac',
            3: '5 Lacs to 25 Lacs',
            4: '25 Lacs to 50 Lacs',
            5: '50 Lacs to 1 Crore',
            6: 'More than 1 Crore',
          },

          department: {
            1: {
              name: 'Founder/Proprietor',
              value: '7',
            },
            2: {
              name: 'Tech/Engineering',
              value: '1',
            },
            3: {
              name: 'Business',
              value: '3',
            },
          },
        },
        showMore: false,
        // disable signup submission before captcha in prod
        submissionDisabled: $location.host() === 'dashboard.razorpay.com',
      };
      console.log($scope.signup);
      // wait for recaptcha response
      $scope.$watch('signup.data.captcha', function(newVal) {
        if (newVal && newVal.length !== 0) {
          $scope.signup.submissionDisabled = false;
        }
      });

      $scope.goToSignupStep = function(step, subStep) {
        $scope.signup.currentStep = step;
        if (subStep !== undefined) {
          // for individual upon press take him to step 4
          if ($scope.canSkipIntermediateScreens()) {
            $scope.signup.currentSubStep = 0;
          } else {
            $scope.signup.currentSubStep = subStep;
          }
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
        $http
          .get(
            '/user/api/live/invitations/token/' + $scope.signup.data.invitation
          )
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
        $http
          .get(
            '/user/api/live/admin-lead/verify/' +
              $scope.signup.data.merchant_invitation
          )
          .success(function(data) {
            if (data.success) {
              var form_data = data.data.form_data;

              $scope.signup.data.email = data.data.email;
              $scope.lock_email = data.data.email ? true : false;
              $scope.signup.merchantData.business_name =
                form_data.merchant_name;
              $scope.signup.merchantData.business_website =
                form_data.business_website;
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
        window.rzpAnalytics({
          name: 'facebook',
          event: 'signup_start',
        });

        window.rzpAnalytics({
          name: 'linkedIn',
          value: {
            conversionId: '987388',
          },
        });

        window.rzpAnalytics({
          name: 'linkedIn',
          value: {
            txn_id: 'o1u9x',
          },
        });

        window.trackHubs({
          name: 'create_contact',
          data: {
            email: $scope.signup.data.email,
          },
        });

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

        var data = $scope.signup.data;

        var payload = {
          method: 'post',
          url: '/user/register',
          data: data,
          headers: {
            'Content-Type': 'application/json',
          },
        };

        payload.data.password_confirmation = payload.data.password;

        payload.data.business_name = payload.data.business_name || '';

        payload.data.partner_intent = $scope.signup.settings.partner_intent;

        // Business name cannot be empty or null. Same as quickSendDetails
        if (!payload.data.business_name) {
          delete payload.data.business_name;
        }

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
            if (window.ga) {
              window.ga('set', '&uid', btoa(payload.data.email));
              window.ga(
                'send',
                'event',
                'Signup - Email Password',
                'Click - Create Account (Success)'
              );
            }

            window.trackHubs({
              id: 'SIGNUP_COMPLETE',
            });

            $scope.isLoggedIn = true;
            user.identity(true).then(function(data) {
              var signinSuccessCb = authCallbacks.getSigninCallback();

              if (signinSuccessCb) {
                signinSuccessCb(data);
              }

              if (data.user.confirmed) {
                if (signinSuccessCb) {
                  signinSuccessCb(data);
                } else {
                  $scope.goToDashboard();
                }
              } else {
                hideSpinner();
                window.rzpQ.push(
                  window.rzpQ
                    .now()
                    .onbr()
                    .success('signup.display_signup_page')
                );
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

            window.trackHubs({
              id: 'SIGNUP_FAILED',
              value: data.errors[0],
            });

            if (
              data.errors &&
              data.errors[0] &&
              data.errors[0].indexOf('email has already been taken') !== -1
            ) {
              trackDrip('error_email_taken');
              window.rzpQ.push(
                window.rzpQ
                  .now()
                  .onbr()
                  .failed('signup.submit_email')
              );
            }

            window.ga &&
              window.ga(
                'send',
                'event',
                'Signup - Email Password',
                'Click - Create Account (Error)',
                JSON.stringify(data.errors)
              );

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

      $scope.goToDashboard = function() {
        location.hash = '/app';
        location.reload();
      };

      $scope.sendDetails = function() {
        if (
          $scope.signup.merchantData.business_website &&
          ($scope.forms.detailsForm.business_website.$valid ||
            $scope.forms.detailsForm.business_website.$error.pattern)
        ) {
          $scope.signup.merchantData.business_website = utils.autoPrefixUrls(
            $scope.signup.merchantData.business_website
          );
        }

        // IMPORTANT: DO NOT REMOVE THESE (USED FOR MARKETING PURPOSES - TRACK SIGNUP ATTEMPTS)
        window.ga && ga('send', 'event', 'sign-up-form-success');
        window.ga && ga('old.send', 'event', 'sign-up-form-success');

        pushToDrip();
        invokeAdroll();
        invokeGtag();
        invokeBing();

        // Fire linkedin Pixel.
        var i = new Image();
        i.src =
          'https://dc.ads.linkedin.com/collect/?pid=155571&conversionId=391804&fmt=gif';

        // Fire Quora pixel.
        i = new Image();
        i.src =
          'https://q.quora.com/_/ad/0b40045f43e5492d916199b03c35aa48/pixel?tag=ViewContent&noscript=1';

        // Fire Twitter pixel
        i = new Image();
        i.src =
          'https://analytics.twitter.com/i/adsct?txn_id=o1tr7&p_id=Twitter&tw_sale_amount=0&tw_order_quantity=0';

        if ($scope.coupon.val !== '' && $scope.coupon.status === 'success') {
          $scope.signup.merchantData.coupon_code = $scope.coupon.val;
        }
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
            window.ga &&
              window.ga('send', 'event', 'Signup - Steps', 'Click - Finish');

            window.rzpAnalytics({
              name: 'facebook',
              event: 'signup_complete',
            });

            window.rzpAnalytics({
              name: 'taboola',
              event: 'signup_complete',
            });

            updateHubSpotContactProperty();

            // if verification is already done, go to dashboard (call /user again to check)
            user.identity(true).then(function(userDetails) {
              // user.authorize and then if email verified
              if (user.isVerified()) {
                $scope.goToDashboard();
              } else {
                goToVerification();
              }
            });
            // else
          } else {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);

              setTimeout(function() {
                var alertEle = $('.pre_signup_alert');

                window.ga &&
                  ga(
                    'send',
                    'event',
                    'Signup - Steps',
                    'Click - Finish',
                    JSON.stringify(data.errors)
                  );

                alertEle[0] &&
                  $('.auth-substep.name-substep').animate(
                    {
                      scrollTop: alertEle.offset().top,
                    },
                    500
                  );
              }, 100);
            });
          }
        });
      };

      $scope.quickSendDetails = function(detailField, key) {
        $scope.signup.merchantData[detailField] = key;

        var reqPayload = Object.assign({}, $scope.signup.merchantData);
        // Business name cannot be empty or null
        if (!reqPayload.business_name) {
          delete reqPayload['business_name'];
        }

        pushToDrip();
        var payload = {
          method: 'post',
          url: '/user/pre_signup',
          transformRequest: transformRequestAsFormPost,
          data: reqPayload,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        $timeout(function() {
          // if individuval in business type take him directly to last screen
          if (
            $scope.signup.currentSubStep == 0 &&
            $scope.canSkipIntermediateScreens()
          ) {
            $scope.signup.currentSubStep = $scope.signup.currentSubStep + 3;
          } else {
            $scope.signup.currentSubStep = $scope.signup.currentSubStep + 1;
          }
        }, 200);
        request.success(function(data) {
          if (!data.success) {
            angular.forEach(data.errors, function(value) {
              $scope.alerts.addAlert('danger', value);
            });
          }

          updateHubSpotContactProperty();

          $('.business-type-substep').scrollTop(0);
          $timeout(function() {
            $scope.signup.showMore = false;
          }, 200);
        });
      };

      function goToVerification() {
        //reset coupons
        $scope.removeCoupon();

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

      /**
       * The script is a tiny bit modified than what AdRoll gives,
       * specifically onload event listener part.
       */
      var invokeAdroll = function invokeAdroll() {
        adroll_adv_id = 'TJ37WOXRMNBN3E7GBHOOXB';
        adroll_pix_id = 'KCGQOUBQ5VFKRM3XB5PB2U';

        (function() {
          var _onload = function() {
            // Use only on prod.
            if (window.location.hostname !== 'dashboard.razorpay.com') {
              return;
            }

            if (
              document.readyState &&
              !/loaded|complete/.test(document.readyState)
            ) {
              setTimeout(_onload, 10);
              return;
            }
            if (!window.__adroll_loaded) {
              __adroll_loaded = true;
              setTimeout(_onload, 50);
              return;
            }
            var scr = document.createElement('script');
            (
              (document.getElementsByTagName('head') || [null])[0] ||
              document.getElementsByTagName('script')[0].parentNode
            ).appendChild(scr);
            var host =
              'https:' == document.location.protocol
                ? 'https://s.adroll.com'
                : 'http://a.adroll.com';
            scr.setAttribute('async', 'true');
            scr.type = 'text/javascript';
            scr.onload = function() {
              __adroll.record_user({
                adroll_segments: 'ef374af4',
              });
            };
            scr.src = host + '/j/roundtrip.js';
          };
          _onload();
        })();
      };

      /**
       * Invoke Bing for conversion tracking.
       */
      var invokeBing = function invokeBing() {
        if (window.location.hostname !== 'dashboard.razorpay.com') {
          return;
        }
        window.uetq = window.uetq || [];
        window.uetq.push({
          ec: 'bing',
          ea: 'click',
          el: 'connecttobing',
          ev: 1,
        });
      };

      /**
       * Invokes GTAG for conversion tracking.
       */
      var invokeGtag = function invokeGtag() {
        if (window.location.hostname !== 'dashboard.razorpay.com') {
          return;
        }
        gtag('event', 'conversion', {
          send_to: 'AW-928471290/9KxkCP-1vIYBEPqx3boD',
        });
      };

      // creates Drip lead if email present in params
      pushToDrip('email_only');
      $scope.onPrivacyClick = function() {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.click_other_links', {
              source: 'privacy',
            })
        );
      };
      $scope.onTermsClick = function() {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.click_other_links', {
              source: 'terms',
            })
        );
      };
      $scope.slectTrack = function(type) {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.select_option', {
              source: type,
            })
        );
      };
      $scope.onInputFocus = function(type) {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.fill_pre_signup_form', {
              source: type,
            })
        );
      };
      $scope.onFinishClick = function(type) {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.finish_signup', {
              source: type,
            })
        );
      };

      $scope.onCreateClick = function() {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .initiated('signup.create_account')
        );
      };
      $scope.goToSigninLayout = function() {
        $scope.goToSignupStep(0); // reset signup step
        $scope.goToLoginStep(1); // reset login step
        $scope.rightLayout = true;
        $scope.login.data.email = $scope.signup.data.email;
        $scope.alerts.resetAlerts();
        var toRoute = 'access.signin';
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .success('signup.click_other_links', {
              source: 'sign_in',
            })
        );
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          }
        );
        return $scope.onShowSignin && $scope.onShowSignin();
      };

      $scope.goToSignupLayout = function(data) {
        var signupData = data || {};

        $scope.goToSignupStep(0); // reset signup step
        $scope.goToLoginStep(1); // reset login step
        $scope.rightLayout = false;
        $scope.signup.data.email = signupData.email || $scope.login.data.email;
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
        return $scope.onShowSignup && $scope.onShowSignup();
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
              $scope.signup.settings.partner_intent =
                userDetails.partner_intent;
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
                $scope.goToDashboard();
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
              $scope.signup.settings.partner_intent =
                userDetails.partner_intent;
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
        } else if ($scope.canSkipIntermediateScreens()) {
          $scope.signup.currentSubStep = 3;
        } else if (!merchantData.transaction_volume) {
          $scope.signup.currentSubStep = 1;
        } else if (!merchantData.department) {
          $scope.signup.currentSubStep = 2;
        } else {
          $scope.signup.currentSubStep = 3;
        }
        $timeout(function() {
          $scope.noTransition = false;
        }, 0);
      }

      $scope.canSkipIntermediateScreens = function() {
        return (
          $scope.signup.settings.partner_intent ||
          $scope.signup.merchantData.business_type == 11
        );
      };
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
            user
              .identity(true)
              .then(function(userDetails) {
                var signinSuccessCb = authCallbacks.getSigninCallback();

                if (signinSuccessCb) {
                  signinSuccessCb(userDetails);
                }

                if (user.isVerified() && user.isPreSignupDone()) {
                  // parse query parameters to object
                  // ?next=foo&q=bar → { next: 'foo', q: 'bar' }
                  var queryParams = location.search
                    .slice(1)
                    .split(/=|&/)
                    .reduce(function(map, param, index, array) {
                      if (index % 2) {
                        map[array[index - 1]] = param;
                      }
                      return map;
                    }, {});

                  if (queryParams.next) {
                    var parser = document.createElement('a');
                    parser.href = decodeURIComponent(queryParams.next);

                    var hostname = parser.hostname || location.hostname;

                    if (/razorpay\.(com|dev|in)$/.test(hostname)) {
                      location.href = parser.href;
                      if (parser.origin === location.origin && parser.hash) {
                        parser.search = '';
                        history.pushState(null, null, parser.href);
                        location.reload();
                      }
                      return false;
                    }
                  }

                  if (!signinSuccessCb) {
                    $scope.goToDashboard();
                  }
                } else {
                  $scope.isLoggedIn = true;
                  hideSpinner();
                  if (userDetails) {
                    $scope.login.data.email = userDetails.email;
                    $scope.signup.settings.partner_intent =
                      userDetails.partner_intent;
                    Object.assign(
                      $scope.signup.merchantData,
                      userDetails.pre_signup
                    );
                  }
                  if (!user.isPreSignupDone()) {
                    $scope.email_not_verified = false;
                    goToRelevantQuestion();
                    $scope.login.currentStep = 2;
                    $scope.signup.settings.partner_intent =
                      userDetails.partner_intent;

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
              })
              .catch(function(errors) {
                hideSpinner();
                $scope.alerts.addAlert('danger', errors[0]);
              });
          } else {
            hideSpinner();
            const firstError = data.errors[0];
            if (typeof firstError === 'string') {
              // errors to be displayed directly
              if (firstError.includes('email not confirmed')) {
                // go to email not verified screen
                $scope.email_not_verified = true;
                $scope.login.currentStep = 2;
              } else {
                angular.forEach(data.errors, function(value) {
                  if (typeof value === 'string') {
                    $scope.alerts.addAlert('danger', value);
                  }
                });
              }
            } else if (
              typeof firstError === 'object' &&
              !!firstError.internal_error_code
            ) {
              $scope.handleErrorsWithInternalCode(firstError);
            }
          }
        });
      };

      $scope.resendVerificationEmail = function() {
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .success('signup.email_verification', {
              source: 'sign_in',
            })
        );

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

          window.ga &&
            window.ga(
              'send',
              'event',
              'Signup - Steps',
              'Click - Resend Verification Email',
              JSON.stringify(data.errors)
            );
        });
      };

      $scope.forgotPwdSubmit = function($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }
        showSpinner();
        var data = {
          email: $scope.login.data.email,
        };
        var request = $http({
          method: 'post',
          url: '/user/api/live/users/reset-password',
          data: data,
        });
        request.success(function(data) {
          hideSpinner();
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
          $scope.signup.merchantData.department = null;
          $scope.signup.merchantData.business_name = '';
          $scope.signup.merchantData.business_website = '';
          $scope.signup.merchantData.contact_mobile = '';
          $scope.signup.merchantData.contact_name = '';
          $scope.email_not_verified = false;
          $scope.forms.signupForm.$setPristine();
          $scope.forms.detailsForm.$setPristine();
        });
        return request;
      };

      $scope.trackContactUsClick = function(e) {
        window.ga &&
          window.ga('send', 'event', 'Signup - Steps', 'Click - Contact Us');
        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .success('signup.click_other_links', {
              source: 'contact_us',
            })
        );
      };

      /*
       * stepName: at which step name
       * sourceLabel: from which CTA, step
       * */
      $scope.trackStepClicksOnMore = function(stepName, sourceLabel) {
        if (!stepName || !sourceLabel) {
          return;
        }

        window.ga &&
          window.ga(
            'send',
            'event',
            'Signup - Steps',
            'Step - ' + stepName,
            sourceLabel
          );
      };

      /*
       * toStepName: to which link the back click points
       * */
      $scope.trackBackClick = function(toStepName) {
        if (!toStepName) {
          return;
        }

        window.ga &&
          window.ga(
            'send',
            'event',
            'Signup - Steps',
            'Click - Back',
            toStepName
          );

        window.rzpQ.push(
          window.rzpQ
            .now()
            .onbr()
            .success('signup.back_action', {
              source: toStepName,
            })
        );
      };

      if (isHostedInBB) {
        var supportedEvents = {
          signin: 'signin',
          signup: 'signup',
          signinSuccess: 'signinSuccess',
          onShowSignin: 'onShowSignin',
          onShowSignup: 'onShowSignup',
        };

        window.RZP.rpcServer &&
          window.RZP.rpcServer(
            appHost,
            [
              {
                name: supportedEvents.signin,
                callback: function() {
                  $scope.goToSigninLayout();
                },
              },
              {
                name: supportedEvents.signup,
                callback: function(userDetails) {
                  $scope.goToSignupLayout(userDetails);
                },
              },
              {
                name: supportedEvents.signinSuccess,
                hasReply: true,
                callback: function(reply) {
                  authCallbacks.setSigninCallback(function(userData) {
                    reply({ user: userData });
                  });
                },
              },
              {
                name: supportedEvents.onShowSignin,
                hasReply: true,
                callback: function(reply) {
                  $scope.onShowSignin = reply;
                  return $scope.rightLayout && reply($scope.login);
                },
              },
              {
                name: supportedEvents.onShowSignup,
                hasReply: true,
                callback: function(reply) {
                  $scope.onShowSignup = reply;
                  return !$scope.rightLayout && reply();
                },
              },
            ],
            'auth'
          );
      }

      $scope.coupon = {
        val: '',
        allowInput: false,
        disabled: false,
        status: '',
        msg: '',
        isValidating: false,
        shouldRender: false,
      };

      $scope.onGotCouponCodeClick = function() {
        $scope.allowInput = true;
        window.ga &&
          window.ga(
            'send',
            'event',
            'Signup - Steps',
            'Click- Got a coupon code'
          );
      };

      $scope.onCouponChange = function() {
        $scope.coupon.val = $filter('uppercase')($scope.coupon.val);
      };

      $scope.clearCoupon = function() {
        $scope.coupon.val = '';
      };

      $scope.removeCoupon = function() {
        $scope.coupon.val = '';
        $scope.coupon.disabled = false;
        $scope.coupon.status = '';
        $scope.coupon.msg = '';
        delete $scope.signup.merchantData.coupon_code;
      };

      $scope.validateCoupon = function() {
        if ($scope.coupon.val === '') {
          return;
        }

        $scope.coupon.disabled = true;
        $scope.coupon.isValidating = true;

        window.ga &&
          window.ga(
            'send',
            'event',
            'Signup - Steps',
            'Click - Coupon Apply',
            $scope.coupon.val
          );

        var payload = {
          method: 'post',
          url: 'user/coupons/validate',
          data: {
            code: $scope.coupon.val,
          },
        };

        $http(payload).success(function(response) {
          var msg = '',
            status = '';

          if (response.success) {
            var today = new Date();
            var amount = response.data.credit_amount / 100;
            var expiryDate = new Date(
              today.setDate(today.getDate() + response.data.expire_days)
            ).toDateString();

            msg = $sce.trustAsHtml(
              'Transactions worth amount ' +
                '<strong>₹' +
                amount +
                '</strong>' +
                '/- will be free of charge' +
                (response.data.expire_days ? ' till ' + expiryDate : '.')
            );

            status = 'success';
            window.ga &&
              window.ga(
                'send',
                'event',
                'Signup - Steps',
                'Coupon - Response',
                'Success | ' + $scope.coupon.val
              );
          } else {
            msg = $sce.trustAsHtml(response.errors[0]);
            status = 'failure';
            window.ga &&
              window.ga(
                'send',
                'event',
                'Signup - Steps',
                'Coupon - Response',
                'Fail | ' + $scope.coupon.val + ' | ' + response.errors[0]
              );
          }
          $scope.coupon.isValidating = false;
          $scope.coupon.msg = msg;

          $scope.coupon.status = status;
        });
      };

      $scope.handleErrorsWithInternalCode = function(error) {
        switch (error.internal_error_code) {
          case 'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED': {
            $scope.goToLoginStep(4);
            break;
          }

          case 'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP': {
            $scope.login.data.otp = '';
            $scope.alerts.addAlert('danger', error.description, true);
            break;
          }

          case 'BAD_REQUEST_LOCKED_USER_LOGIN': {
            $scope.goToLoginStep(5);
          }
        }
      };

      function shouldRenderCouponCode() {
        var coupon_code = $location.search().coupon_code;
        var shouldRender = false;

        if (isHostedInBB) {
          shouldRender = false;
        }

        // always render via url
        if (!isHostedInBB && coupon_code && coupon_code.length > 0) {
          $scope.coupon.val = coupon_code;
          shouldRender = true;
        }

        $scope.coupon.shouldRender = shouldRender;
      }

      shouldRenderCouponCode();

      $scope.$watch('signup.currentSubStep', function(newVal) {
        if (newVal === 3) {
          if (
            !isHostedInBB &&
            !$scope.coupon.shouldRender &&
            user.getTreatment('coupons')
          ) {
            $scope.coupon.shouldRender = true;
          }

          if ($scope.coupon.shouldRender && $scope.coupon.val.length > 0) {
            $scope.validateCoupon();
            $scope.onGotCouponCodeClick();
          }
        }
      });

      // Updating contact properties on hubspot
      function updateHubSpotContactProperty() {
        var merchantData = $scope.signup.merchantData,
          details = $scope.signup.details,
          department = null,
          business_type = null,
          transaction_volume =
            details.transaction_volume[merchantData.transaction_volume],
          business_type_list = Object.keys(details.business_type),
          department_list = Object.keys(details.department);

        for (var key in business_type_list) {
          idx = business_type_list[key];

          if (details.business_type[idx].value === merchantData.business_type) {
            business_type = details.business_type[idx].name;

            break;
          }
        }

        for (var key in department_list) {
          idx = department_list[key];

          if (details.department[idx].value === merchantData.department) {
            department = details.department[idx].name;

            break;
          }
        }

        window.trackHubs({
          name: 'update_property',
          data: {
            email: $scope.signup.data.email,
            signup_business_type: business_type,
            signup_transaction_volume: transaction_volume,
            signup_department: department,
            signup_business_name: merchantData.business_name,
            signup_business_website: merchantData.business_website,
            signup_contact_mobile: merchantData.contact_mobile,
            signup_contact_name: merchantData.contact_name,
          },
        });
      }
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

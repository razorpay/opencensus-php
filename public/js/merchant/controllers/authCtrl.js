'use strict';

//Signin Controller
app
  .factory('authCallbacks', [
    function () {
      var signinCallback = null;

      return {
        getSigninCallback: function () {
          return signinCallback;
        },
        setSigninCallback: function (cb) {
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
    'tracking',
    function (
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
      $filter,
      tracking,
    ) {
      $scope.toArray = function (obj) {
        if (!obj) {
          return [];
        }
        return Object.keys(obj);
      };
      const COOKIE_POLICY_DOCS = {
        CHROME:
          'https://support.google.com/chrome/answer/95647?co=GENIE.Platform%3DDesktop&hl=en-GB',
        FIREFOX:
          'https://support.mozilla.org/en-US/kb/websites-say-cookies-are-blocked-unblock-them',
        SAFARI: 'https://support.apple.com/en-in/guide/safari/sfri11471/mac',
      };
      const BROWSERS = {
        CHROME: 'Chrome',
        FIREFOX: 'Firefox',
        SAFARI: 'Safari',
      };
      const isMerchantX = window.location.href.includes('merchant=x');
      const eventTypes = { twitterAgency: 'twitterAgency' };
      $scope.data = {};
      $scope.alerts = alertsFactory.getHandler();
      $scope.rightLayout = false; // login layout ? right is true : right is false
      $scope.lockme = false; // only turns true for lockme route
      $scope.eventsMode = 'live';
      $scope.showTopbar = false;
      $scope.isSignupDisplayEventFired = false;
      $scope.showGAuthPopup = false;
      $scope.showCookieErrorPopup = false;
      $scope.showKnowMore = false;
      $scope.currentService = serviceName();

      $scope.organization = {};
      $scope.isOrgCheckDone = false;
      organization.fetchCurrentOrg().then(function (data) {
        $scope.login_logo = data.login_logo_url || 'img/logo_full.png';
        $scope.isOrgCheckDone = true;
        $scope.organization = data;
        $scope.isOrgRZP = $scope.organization.custom_code === 'rzp';
        $scope.isOrgHDFC = $scope.organization.custom_code === 'hdfc';
        $scope.isOrgAXIS = $scope.organization.custom_code === 'axis';
      });
      $scope.forms = {};

      $scope.isLoggedIn = false;
      $scope.showCompanyName = false;

      // Less restrictive url regex
      $scope.websiteRegex = /^((http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*))?$/gi;
      $scope.emailRegex = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

      // signup state container
      var email = $location.search().email;
      var role = $location.search().r;
      var referral_code = $location.search().referral_code;
      $scope.checkboxCaptcha = 'Faked';
      try {
        email = atob(decodeURIComponent(email));
      } catch (e) {
        email = '';
      }

      var isProd = window.location.hostname.endsWith('razorpay.com');
      var isAxisBankUATEnv = window.location.hostname.includes('dashboard-axis.stage.razorpay.in');
      var isLoginWithGoogle = false;

      $scope.signup = {
        currentStep: 0, // 0, 1, 2
        currentSubStep: 0, // 0, 1, 2, 3, 4
        settings: {
          partner_intent: role === 'partner',
        },
        mid: '',
        userid: '',
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
              name: 'Not Registered',
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
        },
        showMore: false,

        // disable signup/login submission before captcha only in prod
        submissionDisabled: isProd || isAxisBankUATEnv,
        isWhatsAppOptIn: true,
      };

      // login state container
      $scope.login = {
        data: {
          email: '',
          password: '',
        },
        currentStep: 1, // 3 -> verification, 2 -> questions, 1 -> login, 0 -> forgotpwd
        currentSubStep: 0, // 0 -> email+pwd, 1 -> provision for OTP screen
        isCaptchaSuccess: false,
      };

      $scope.secondFA = {
        data: {
          otp: '',
        },
      };

      if (window.location.hash.includes('/access/signup')) {
        window.analytics && window.analytics.init(['hubspot']);
      }

      $scope.handleWhatsAppOpIn = function () {
        $scope.signup.isWhatsAppOptIn = !$scope.signup.isWhatsAppOptIn;
      };

      const whatsAppOptIn = function () {
        var payload = {
          method: 'post',
          url: '/user/whatsapp/opt_in',
          data: {
            source: 'pg.onboarding.presignup',
          },
        };
        $http(payload);
      };

      $scope.loadCaptcha = function (loadCheckbox = false) {
        if (!loadCheckbox && !isProd && !isAxisBankUATEnv) return; //Disable invisible captcha for staging
        renderRecaptchaScript(loadCheckbox);
        if (loadCheckbox) {
          let checkboxCaptchaElement = document.getElementById('checkbox-recaptcha');
          checkboxCaptchaElement.setAttribute('data-sitekey', window.CHECKBOX_CAPTCHA_SITE_KEY);
          window.grecaptcha &&
            grecaptcha.render('checkbox-recaptcha', {
              sitekey: window.CHECKBOX_CAPTCHA_SITE_KEY,
            });
        }
      };

      const checkScriptExists = function (url) {
        if (document.scripts) {
          let hasScript = function (script) {
            return script.getAttribute('src') === url;
          };
          return Array.from(document.scripts).some(hasScript);
        }
      };

      $scope.inlineEmailError = '';
      $scope.inlinePasswordError = '';

      $scope.init = function () {
        initializeGAPI();
      };

      const pushPromMetric = ({ flow, label }) => {
        if (!window.rzpQMetrics) return;

        window.rzpQMetrics.immediate = true;

        window.rzpQMetrics.push({
          type: 'metrics',
          properties: {
            name: 'device.metrics',
            labels: [
              {
                type: 'dashboard_' + flow,
                source: label,
              },
            ],
          },
        });

        window.rzpQMetrics.immediate = false;
      };

      /**
       * Datalake event object for google oauth events
       * @type {{mode: string, service: (string), sessionId: *, version: number}}
       * version: 1.1 - to differentiate google auth events or normal login
       * mode: live - to differentiate between live and test
       * service: PG/X/Partner
       */
      const gauthEventObj = {
        mode: $scope.eventsMode,
        sessionId: window.session_id,
        service: $scope.currentService,
        version: 1.1,
        ref_url: $location.search().utm_source || document.referrer,
        url: document.location.href,
      };

      const fireDLFailureEvents = function (eventName, properties) {
        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ.now().onbr().failed(eventName, Object.assign(gauthEventObj, properties)),
          );
      };

      const fireDLSuccessEvents = function (eventName, properties) {
        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ.now().onbr().success(eventName, Object.assign(gauthEventObj, properties)),
          );
      };

      const fireDLInitiatedEvents = function (eventName, properties) {
        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ.now().onbr().initiated(eventName, Object.assign(gauthEventObj, properties)),
          );
      };

      const fireGauthLoginEvt = function (email, error) {
        fireDLFailureEvents('login.login', { emailId: email, error, method: 'google_oauth' });
      };

      $scope.closeGauthPopUp = function () {
        $scope.showGAuthPopup = false;
      };

      $scope.closeCookieErrorPopup = function () {
        $scope.showCookieErrorPopup = false;
      };

      /**
       * Function works with top 3 browsers only
       * @return {string}
       */
      const getBrowser = function () {
        const userAgent = navigator.userAgent;
        let browserName = navigator.appName;

        if (userAgent.indexOf('Chrome') !== -1) {
          browserName = BROWSERS.CHROME;
        } else if (userAgent.indexOf('Safari') !== -1) {
          browserName = BROWSERS.SAFARI;
        } else if (userAgent.indexOf('Firefox') !== -1) {
          browserName = BROWSERS.FIREFOX;
        }
        return browserName;
      };

      const browser = getBrowser();
      /**
       * When 3rd party cookies are disabled in browser,
       * this function will take user to the official doc on
       * how to enable it.
       */
      $scope.handleKnowMore = function () {
        let docsLink;
        if (browser === BROWSERS.CHROME) docsLink = COOKIE_POLICY_DOCS.CHROME;
        if (browser === BROWSERS.SAFARI) docsLink = COOKIE_POLICY_DOCS.SAFARI;
        if (browser === BROWSERS.FIREFOX) docsLink = COOKIE_POLICY_DOCS.FIREFOX;
        $scope.showCookieErrorPopup = false;
        window.open(docsLink, '_blank');
      };

      $scope.onShowGAuthFromPopUp = function () {
        $scope.showGAuthPopup = false;

        fireDLInitiatedEvents('login.try_another_account');
        var googleAuthInstance = window.gapi.auth2.getAuthInstance();
        googleAuthInstance.signOut().then(function () {
          $scope.onLoginWithGoogle();
        });

        googleAuthInstance.disconnect();
      };

      $scope.onLoginWithGoogle = function () {
        initializeGAPI(true);
      };

      var googleAuthObj;
      var isInitiatedEventFired = false;
      const initializeGAPI = (triggerButton) => {
        $scope.showCookieErrorPopup = false;

        let button = document.getElementById('gauth');

        if (triggerButton) {
          updateSpinnerState('show');
          if (!isInitiatedEventFired) {
            fireDLInitiatedEvents('login.google_oauth');
            fireDLInitiatedEvents('login.login', { method: 'google_oauth' });
            pushPromMetric({ flow: 'login', label: 'login_initiate' });
            isInitiatedEventFired = true;
          }
        }

        if (!googleAuthObj) {
          window.gapi &&
            window.gapi.load('auth2', function () {
              // Retrieve the singleton for the GoogleAuth library and set up the client.
              window.gapi.auth2
                .init({
                  client_id: window.OAUTH_CLIENT_ID,
                })
                .then(
                  function (res) {
                    googleAuthObj = res;
                    attachSignin(button);
                    if (triggerButton) {
                      button.click();
                      updateSpinnerState('show');
                    }
                  },
                  function (error) {
                    updateSpinnerState('hide');
                    if (error.details.includes('Cookies are not enabled in current environment')) {
                      if (triggerButton) {
                        $scope.showCookieErrorPopup = true;

                        if (
                          browser === BROWSERS.CHROME ||
                          browser === BROWSERS.SAFARI ||
                          browser === BROWSERS.FIREFOX
                        ) {
                          $scope.showKnowMore = true;
                        }

                        fireDLFailureEvents('login.google_oauth', {
                          emailId: email,
                          error: error.details,
                        });
                      }
                    } else {
                      $scope.alerts.addAlert('danger', error.details);
                    }
                    $scope.$apply();
                  },
                );
            });
        } else {
          attachSignin(button);
          if (triggerButton) {
            if (!isInitiatedEventFired) {
              fireDLInitiatedEvents('login.google_oauth');
              fireDLInitiatedEvents('login.login', { method: 'google_oauth' });
              pushPromMetric({ flow: 'login', label: 'login_initiate' });
            }
            button.click();
            updateSpinnerState('show');
          }
        }
      };

      const attachSignin = (element) => {
        googleAuthObj.attachClickHandler(
          element,
          {},
          function (googleUser) {
            $scope.idToken = googleUser.getAuthResponse().id_token;
            const email = googleUser.getBasicProfile().getEmail();
            $scope.googleAuthEmail = email;

            fireDLSuccessEvents('login.google_oauth', { emailId: email });

            var payload = {
              method: 'post',
              url: '/user/oauth-signin',
              data: {
                email,
                id_token: $scope.idToken,
                oauth_provider: 'google',
              },
              headers: {
                'Content-Type': 'application/json',
              },
            };
            let request = $http(payload);
            request
              .success(function (data) {
                if (data.success) {
                  isLoginWithGoogle = true;
                  user
                    .identity(true)
                    .then(function (userDetails) {
                      userIdentitySuccess(userDetails);
                    })
                    .catch(function (errors) {
                      updateSpinnerState('hide');
                      fireGauthLoginEvt(email, errors[0]);
                      $scope.alerts.addAlert('danger', errors[0]);
                    });
                } else {
                  updateSpinnerState('hide');

                  if (data.errors && data.errors.length) {
                    var firstError = data.errors[0];

                    if (typeof firstError === 'object' && !!firstError.internal_error_code) {
                      $scope.handleErrorsWithInternalCode(firstError);
                    } else if (firstError.includes('Razorpay Account Not Found')) {
                      if (isMerchantX) {
                        $scope.alerts.addAlert('danger', `No account found for ${email}`);
                      } else {
                        $scope.googleAuthEmail = email;
                        $scope.showGAuthPopup = true;
                      }
                      fireDLSuccessEvents('login.account_not_found_modal', {
                        emailId: email,
                        method: 'google_oauth',
                      });
                      fireGauthLoginEvt(email, firstError);
                    } else {
                      fireGauthLoginEvt(email, firstError);
                      $scope.alerts.addAlert('danger', firstError);
                    }
                  }
                }
              })
              .error(function (errors) {
                updateSpinnerState('hide');
                fireGauthLoginEvt(email, errors[0]);
                $scope.alerts.addAlert('danger', errors[0]);
              });
          },
          function (errors) {
            updateSpinnerState('hide');
            fireDLFailureEvents('login.google_oauth', { emailId: email, error: errors.error });
          },
        );
      };

      const getCookie = function (name) {
        const cookieName = `${name}=`;
        const cookieArray = document.cookie.split(';');
        for (let i = 0; i < cookieArray.length; i++) {
          let cookie = cookieArray[i];
          while (cookie.charAt(0) === ' ') cookie = cookie.substring(1, cookie.length);
          if (cookie.indexOf(cookieName) === 0)
            return cookie.substring(cookieName.length, cookie.length);
        }
        return false;
      };

      /**
       * util function to set cookie
       * @param name
       * @param value
       * @param expiryDays - in days
       */
      const setCookie = function (name, value, expiryDays = 365) {
        const date = new Date();
        date.setDate(date.getDate() + expiryDays);
        const expires = date.toUTCString();
        const domain = isProd ? 'razorpay.com' : 'razorpay.in';
        document.cookie = `${name}=${value};domain=${domain};expires=${expires};`;
      };

      const readUTMsCookie = function () {
        const rzpUtmCookie = getCookie('rzp_utm');
        let utms = {};
        let parsedCookie = JSON.parse(rzpUtmCookie);
        utms.firstUtm =
          parsedCookie && parsedCookie.attributions ? parsedCookie.attributions[0] : '';
        utms.lastUtm =
          parsedCookie && parsedCookie.attributions ? parsedCookie.attributions[1] : '';
        utms.firstPage = parsedCookie && parsedCookie.first_page ? parsedCookie.first_page : '';
        utms.finalPage = parsedCookie && parsedCookie.final_page ? parsedCookie.final_page : '';
        utms.website = parsedCookie && parsedCookie.website ? parsedCookie.website : '';
        return utms;
      };

      $scope.onCreateAccountWithGoogle = function (email) {
        updateSpinnerState('show');
        fireDLInitiatedEvents('login.create_account', { emailId: email });
        $scope.showGAuthPopup = false;

        var payload = {
          method: 'post',
          url: '/user/oauth-register',
          data: {
            email,
            id_token: $scope.idToken,
            oauth_provider: 'google',
          },
          headers: {
            'Content-Type': 'application/json',
          },
        };

        if ($scope.signup.settings.partner_intent) {
          payload.data.partner_intent = $scope.signup.settings.partner_intent;
        }

        let request = $http(payload);
        request.success(function (data) {
          if (data.success) {
            user
              .identity(true)
              .then(function (userDetails) {
                userIdentitySuccess(userDetails);
              })
              .catch(function (errors) {
                updateSpinnerState('hide');
                $scope.alerts.addAlert('danger', errors[0]);
              });
          } else {
            updateSpinnerState('hide');
            $scope.showGAuthPopup = false;
            if (data.errors && data.errors.length) {
              $scope.alerts.addAlert('danger', data.errors[0]);
            }
          }
        });
      };

      const userIdentitySuccess = function (userDetails) {
        setCookie('midExists', !!userDetails.current);
        var signinSuccessCb = authCallbacks.getSigninCallback();

        setExperimentsFlags(userDetails.experiments); // set razorX experiment flags
        if (signinSuccessCb) {
          signinSuccessCb(userDetails);
        }

        if (user.isVerified() && user.isPreSignupDone()) {
          // parse query parameters to object
          // ?next=foo&q=bar → { next: 'foo', q: 'bar' }
          var queryParams = location.search
            .slice(1)
            .split(/=|&/)
            .reduce(function (map, param, index, array) {
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

          updateSpinnerState('hide');

          if (!signinSuccessCb) {
            $scope.goToDashboard(userDetails);
          }
        } else {
          $scope.isLoggedIn = true;

          if (userDetails) {
            $scope.login.data.email = userDetails.email;
            $scope.signup.settings.partner_intent = userDetails.partner_intent;
            Object.assign($scope.signup.merchantData, userDetails.pre_signup);
          }
          if (!user.isPreSignupDone()) {
            $scope.email_not_verified = false;
            $scope.signup.settings.partner_intent = userDetails.partner_intent;

            if ($scope.currentService === 'X') {
              $state.transitionTo(
                'access.pre_signup',
                {},
                {
                  notify: false,
                },
              );
            } else {
              location.href = location.origin + '/signup?screen=business_details';
            }
          }
        }
      };

      $scope.goToSignupStep = function (step, subStep) {
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

      $scope.goToLoginStep = function (step, subStep) {
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
          .get('/user/api/live/invitations/token/' + $scope.signup.data.invitation)
          .success(function (data) {
            if (data.success) {
              $scope.signup.data.email = data.data.email;
              $scope.lock_email = data.data.email ? true : false;
            } else {
              $state.transitionTo('access.signin');
              $scope.showTopbar = false;
            }
          })
          .error(function () {});
      } else if ($location.search().merchant_invitation) {
        // heimdall specific
        $scope.signup.data.merchant_invitation = $location.search().merchant_invitation;

        // Get invitation details
        $http
          .get('/user/api/live/admin-lead/verify/' + $scope.signup.data.merchant_invitation)
          .success(function (data) {
            if (data.success) {
              var form_data = data.data.form_data;

              $scope.signup.data.email = data.data.email;
              $scope.lock_email = data.data.email ? true : false;
              $scope.signup.merchantData.business_name = form_data.merchant_name;
              $scope.signup.merchantData.business_website = form_data.business_website;
              $scope.signup.merchantData.contact_name = form_data.contact_name;
              $scope.merchant_invitation = true;
            }
          })
          .error(function () {});
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

      /**
       * Get current service name ie either X/PG/Partner/Other
       */
      function serviceName() {
        var service = 'PG';
        if (isMerchantX) {
          service = 'X';
        } else if (role === 'partner') {
          service = 'Partner';
        } else if (
          referral_code ||
          $location.search().invitation ||
          $location.search().merchant_invitation
        ) {
          service = 'Other';
        }
        return service;
      }

      $scope.createAccount = function ($valid) {
        window.rzpAnalytics({
          name: 'facebook',
          event: 'signup_start',
        });

        window.rzpAnalytics({
          name: 'quora',
          event: 'GenerateLead',
        });

        window.rzpAnalytics({
          name: 'reddit',
          event: 'Lead',
        });

        window.rzpAnalytics({
          name: 'linkedIn',
          value: {
            conversionId: '987388',
          },
        });

        window.rzpAnalytics({
          name: 'twitter',
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

        if ($scope.signup.settings.partner_intent) {
          window.rzpAnalytics({
            name: 'facebook',
            event: 'partner_signup_start',
          });

          window.rzpAnalytics({
            name: 'linkedIn',
            value: {
              conversionId: '1668332',
            },
          });

          window.rzpAnalytics({
            eventCategory: 'Partner Onboarding',
            eventAction: 'Email and Password',
            eventLabel: 'Partner Onboarding | Click Create Account',
          });
        }

        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        pushToDrip();

        signup($scope.checkboxCaptcha);
      };

      const signup = function (captchaVal) {
        $scope.signup.data.captcha = captchaVal;

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
        updateSpinnerState('show');
        request.success(function (data) {
          if (data.success) {
            $localStorage.new_user_signup = true;
            $scope.signup.account_type = $scope.signup.data.invitation ? 'team_member' : 'merchant';
            trackDrip('account_created');
            pushToDrip();
            if (window.ga) {
              window.ga('set', '&uid', btoa(payload.data.email));
              window.ga(
                'send',
                'event',
                'Signup - Email Password',
                'Click - Create Account (Success)',
              );
              window.ga('set', {
                dimension1: 'live',
                dimension2: data.data.name,
                dimension3: data.data.user_id,
                dimension4: data.data.email,
                dimension5: data.data.role,
              });
            }

            window.trackHubs({
              id: 'SIGNUP_COMPLETE',
            });

            $scope.isLoggedIn = true;
            user.identity(true).then(function (data) {
              var signinSuccessCb = authCallbacks.getSigninCallback();

              setExperimentsFlags(data.experiments);

              if (signinSuccessCb) {
                signinSuccessCb(data);
              }

              if (data.user.confirmed) {
                if (signinSuccessCb) {
                  signinSuccessCb(data);
                } else {
                  $scope.goToDashboard(data);
                }
              } else {
                $scope.signup.userid = data.user.id;
                $scope.signup.mid = data.current;
                setCookie('midExists', !!data.current);
                window.rzpQ &&
                  window.rzpQ.push(
                    window.rzpQ.now().onbr().success('signup.create_account', {
                      mode: $scope.eventsMode,
                      version: 1,
                      emailId: data.user.email,
                      userid: data.user.id,
                      mid: data.current,
                    }),
                  );

                updateSpinnerState('hide');
                $state.transitionTo(
                  'access.pre_signup',
                  {},
                  {
                    notify: false,
                  },
                );
                $scope.showTopbar = true;
                $scope.goToSignupStep(1);
              }
            });
          } else {
            updateSpinnerState('hide');
            var signupError = 'Something went wrong. Please try again.';
            if (data.errors && data.errors.length) {
              signupError = data.errors[0].includes('Internal Server Error')
                ? signupError
                : data.errors[0];
            }

            window.trackHubs({
              id: 'SIGNUP_FAILED',
              value: signupError,
            });

            window.rzpQ &&
              window.rzpQ.push(
                window.rzpQ.now().onbr().failed('signup.create_account', {
                  mode: $scope.eventsMode,
                  version: 1,
                  emailId: payload.data.email,
                  error: signupError,
                }),
              );

            let firstError = '';
            if (data.errors && data.errors.length) {
              firstError = data.errors[0];
            }
            if (typeof firstError === 'object' && !!firstError.internal_error_code) {
              $scope.goToSigninLayout();
              $scope.handleErrorsWithInternalCode(firstError);
            } else {
              if (firstError && firstError.indexOf('email has already been taken') !== -1) {
                trackDrip('error_email_taken');
                window.rzpQ.push(
                  window.rzpQ
                    .now()
                    .onbr()
                    .failed('signup.submit_email', { mode: $scope.eventsMode }),
                );
              }

              window.ga &&
                window.ga(
                  'send',
                  'event',
                  'Signup - Email Password',
                  'Click - Create Account (Error)',
                  JSON.stringify(data.errors),
                );

              angular.forEach(data.errors, function (value) {
                $scope.alerts.addAlert('danger', value);
              });
            }
          }
        });
      };

      function updateSpinnerState(state) {
        const loaders = document.querySelectorAll('.loading-animation');
        for (let i = 0; i < loaders.length; i++) {
          state === 'show'
            ? loaders[i].classList.add('active')
            : loaders[i].classList.remove('active');
        }
      }

      $scope.goToDashboard = function (data) {
        setCookie('midExists', !!data.current);

        pushPromMetric({ flow: 'login', label: 'login_success' });

        fireDLSuccessEvents('login.login', {
          source: 'sign_in',
          method: isLoginWithGoogle ? 'google_oauth' : 'email',
          emailId: data.user.email,
          userid: data.user.id,
          mid: data.current,
        });

        location.hash = '';
        location.pathname = '/app';
        location.reload();
      };

      $scope.onContactUs = function () {
        tracking.pushEvents({
          event_name: 'non_login_actions',
          event_type: 'initiated',
          properties: {
            action: 'click contact us',
          },
        });
      };

      $scope.handlePromotionClick = function (data) {
        tracking.pushEvents({
          event_name: 'non_login_actions',
          event_type: 'initiated',
          properties: {
            action: 'click Promotion ' + data.order + ' CTA',
            promotion_title: data.title,
          },
        });
      };

      $scope.onResetEmailChange = function () {
        tracking.pushEvents({
          event_name: 'send_password_reset_link',
          event_type: 'initiated',
          properties: {
            action: 'type email id',
          },
        });
      };

      $scope.sendDetails = function () {
        if (
          $scope.signup.merchantData.business_website &&
          ($scope.forms.detailsForm.business_website.$valid ||
            $scope.forms.detailsForm.business_website.$error.pattern)
        ) {
          $scope.signup.merchantData.business_website = utils.autoPrefixUrls(
            $scope.signup.merchantData.business_website,
          );
        }

        pushToDrip();
        invokeAdroll();
        invokeGtag();
        invokeBing();

        window.rzpAnalytics({
          name: 'twitter',
          value: {
            txn_id: 'o1tr7',
          },
        });

        window.rzpAnalytics({
          name: eventTypes.twitterAgency,
          value: {
            txn_id: 'o4ux5',
          },
        });

        window.rzpAnalytics({
          name: 'linkedIn',
          value: {
            conversionId: '391804',
          },
        });

        if ($scope.coupon.val !== '' && $scope.coupon.status === 'success') {
          $scope.signup.merchantData.coupon_code = $scope.coupon.val;
        }
        if (Boolean(referral_code)) {
          $scope.signup.merchantData['referral_code'] = referral_code;
        }
        var payload = {
          method: 'post',
          url: '/user/pre_signup',
          transformRequest: transformRequestAsFormPost,
          data: $scope.signup.merchantData,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        updateSpinnerState('show');

        request.success(function (data) {
          updateSpinnerState('hide');
          if (data.success) {
            if ($scope.signup.isWhatsAppOptIn) {
              whatsAppOptIn();
            }

            trackDrip('signup_flow_completed');
            pushToDrip();
            sendSignUpCompleteEvents();
            updateHubSpotContactProperty();
            setCookie('midExists', !!$scope.signup.mid);

            // if verification is already done, go to dashboard (call /user again to check)
            user.identity(true).then(function (userDetails) {
              // user.authorize and then if email verified

              var signinSuccessCb = authCallbacks.getSigninCallback();

              if (user.isVerified()) {
                if (signinSuccessCb) {
                  signinSuccessCb(userDetails);
                } else {
                  $scope.goToDashboard(userDetails);
                }
              } else {
                goToVerification();
              }
            });
            // else
          } else {
            window.rzpQ &&
              window.rzpQ.push(
                window.rzpQ
                  .now()
                  .onbr()
                  .failed('signup.finish_signup', {
                    mode: $scope.eventsMode,
                    emailId: $scope.signup.data.email,
                    mid: $scope.signup.mid,
                    userid: $scope.signup.userid,
                    error: data.errors ? data.errors[0] : '',
                    version: 1,
                  }),
              );
            angular.forEach(data.errors, function (value) {
              $scope.alerts.addAlert('danger', value);

              setTimeout(function () {
                let alertEle = document.querySelector('.pre_signup_alert');
                window.ga &&
                  ga(
                    'send',
                    'event',
                    'Signup - Steps',
                    'Click - Finish',
                    JSON.stringify(data.errors),
                  );
                alertEle && alertEle.scrollIntoView();
              }, 100);
            });
          }
        });
      };

      $scope.quickSendDetails = function (detailField, key) {
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
        $timeout(function () {
          // if individuval in business type take him directly to last screen
          if ($scope.signup.currentSubStep == 0 && $scope.canSkipIntermediateScreens()) {
            $scope.signup.currentSubStep = $scope.signup.currentSubStep + 2;
          } else {
            $scope.signup.currentSubStep = $scope.signup.currentSubStep + 1;
          }
        }, 200);
        request.success(function (data) {
          if (!data.success) {
            angular.forEach(data.errors, function (value) {
              $scope.alerts.addAlert('danger', value);
            });
          }

          updateHubSpotContactProperty();
          let businessTypeEle = document.querySelector('.business-type-substep');
          businessTypeEle && businessTypeEle.scrollIntoView();

          $timeout(function () {
            $scope.signup.showMore = false;
          }, 200);
        });
      };

      /**
       * Fire pixels for company-website AB test
       */
      function trackCompanyAB(isSignupCompleted) {
        if (typeof isSignupCompleted === 'undefined') {
          isSignupCompleted = false;
        }

        if (!window.ga || !$scope.signup.merchantData.business_type) return;

        var regOrUnreg = '',
          gaLabel = '',
          gaAction = '',
          companyNameAsked = '';
        if ($scope.signup.merchantData.business_type == 11) {
          regOrUnreg = 'Unregistered';
        } else {
          regOrUnreg = 'Registered';
        }
        if (isSignupCompleted) {
          gaLabel = 'Signup Successful';
        } else {
          gaLabel = 'Last screen displayed';
        }
        if (!$scope.showCompanyName) {
          companyNameAsked = 'NOT ';
        }
        gaAction = regOrUnreg + ' and ' + companyNameAsked + 'asked for company name';

        window.ga && window.ga('send', 'event', 'Company AB', gaAction, gaLabel);
      }

      function goToVerification() {
        //reset coupons
        $scope.removeCoupon();

        if (!$scope.rightLayout) {
          $scope.goToSignupStep(2);
          trackCompanyAB(true); // fire after all signup steps completed
        } else {
          $scope.goToLoginStep(3);
        }
      }

      /**
       * Set signup razorX flags for AB tests
       */
      var setExperimentsFlags = function (experiments) {
        if (!experiments) {
          return;
        }
        var hideCompanyAB = experiments['hide_company_name'];
        if (hideCompanyAB && hideCompanyAB.result) {
          if (hideCompanyAB.result === 'on') {
            $scope.showCompanyName = false;
          } else {
            $scope.showCompanyName = true;
          }
        }
      };

      var trackDrip = function (action) {
        if (!action) return;
        if (location.host !== 'dashboard.razorpay.com') return;
        try {
          _dcq.push(['track', action]);
        } catch (e) {}
      };

      var pushToDrip = (function () {
        var dataSent = {};
        return function (tag) {
          // send only on production
          if (location.host !== 'dashboard.razorpay.com') {
            return;
          }
          var data = {};
          var payload = {};
          var keys = [
            'business_type',
            'transaction_volume',
            'business_name',
            'contact_mobile',
            'contact_name',
          ];
          keys.forEach(function (key) {
            if ($scope.signup.merchantData[key]) {
              data[key] = $scope.signup.details[key]
                ? $scope.signup.details[key][$scope.signup.merchantData[key]]
                : $scope.signup.merchantData[key];
            }
          });
          if ($scope.signup.account_type) data.account_type = $scope.signup.account_type;
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

        (function () {
          var _onload = function () {
            // Use only on prod.
            if (window.location.hostname !== 'dashboard.razorpay.com') {
              return;
            }

            if (document.readyState && !/loaded|complete/.test(document.readyState)) {
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
            scr.onload = function () {
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
      $scope.onPrivacyClick = function () {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.click_other_links', {
            source: 'privacy',
            mode: $scope.eventsMode,
          }),
        );
      };
      $scope.onTermsClick = function () {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.click_other_links', {
            source: 'terms',
            mode: $scope.eventsMode,
          }),
        );
      };
      $scope.slectTrack = function (type) {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.select_option', {
            source: type,
            mode: $scope.eventsMode,
          }),
        );
      };
      $scope.onInputFocus = function (type) {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.fill_pre_signup_form', {
            source: type,
            mode: $scope.eventsMode,
          }),
        );
      };
      $scope.onFinishClick = function (type) {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.finish_signup', {
            source: type,
            mode: $scope.eventsMode,
            mid: $scope.signup.mid,
            emailId: $scope.signup.data.email,
            userid: $scope.signup.userid,
            version: 1,
          }),
        );

        if ($scope.signup.settings.partner_intent) {
          window.rzpAnalytics({
            name: 'facebook',
            event: 'partner_signup_complete',
          });

          window.rzpAnalytics({
            name: 'linkedIn',
            value: {
              conversionId: '1668316',
            },
          });

          window.rzpAnalytics({
            eventCategory: 'Partner Onboarding',
            eventAction: 'Contact Details',
            eventLabel: 'Partner Onboarding | Fill & Finish',
          });
        }
      };

      $scope.onCreateClick = function () {
        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('signup.create_account', { mode: $scope.eventsMode }),
        );
      };
      $scope.goToSigninLayout = function () {
        $scope.goToSignupStep(0); // reset signup step
        $scope.goToLoginStep(1); // reset login step
        $scope.rightLayout = true;
        $scope.login.data.email = $scope.signup.data.email;
        $scope.alerts.resetAlerts();
        var toRoute = 'access.signin';
        window.rzpQ.push(
          window.rzpQ.now().onbr().success('signup.click_other_links', {
            source: 'sign_in',
            mode: $scope.eventsMode,
          }),
        );
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          },
        );
        $scope.showTopbar = false;
        removeRecaptchaScript();
        return $scope.onShowSignin && $scope.onShowSignin();
      };

      // full page redirect to signup for running experiment
      $scope.goToSignup = function () {
        tracking.pushEvents({
          event_name: 'non_login_actions',
          event_type: 'initiated',
          properties: {
            action: 'click signup',
          },
        });
        document.body.style.display = 'none'; // remove flicker during redirection

        // doing reload to trigger the Google Optimize experiment
        window.location.href = '#/access/signup';
        location.reload();
      };

      // client redirection to signup
      $scope.goToSignupLayout = function (data) {
        var signupData = data || {};
        if (!data) {
          tracking.pushEvents({
            event_name: 'non_login_actions',
            event_type: 'initiated',
            properties: {
              action: 'click signup',
            },
          });
        }
        displaySignupEvent();
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
          },
        );
        $scope.showTopbar = true;
        return $scope.onShowSignup && $scope.onShowSignup();
      };

      if (
        ['access.signin', 'access.forgotpwd', 'access.pre_signup', 'access.lockme'].indexOf(
          $state.current.name,
        ) !== -1
      ) {
        $scope.rightLayout = true;
        if ($state.current.name === 'access.signin' || $state.current.name === 'access.lockme') {
          if ($state.current.name === 'access.signin') {
            let utm = readUTMsCookie();

            pushPromMetric({ flow: 'login', label: 'login_landed' });

            fireDLSuccessEvents('login.display_login', {
              source: 'sign_in',
              first_utm: utm.firstUtm,
              last_utm: utm.lastUtm,
              ref_url: $location.search().utm_source || document.referrer,
              first_page: utm.firstPage,
              final_page: utm.finalPage,
              website: utm.website,
            });
          }

          if (!user.isAuthenticated()) {
            $scope.login.currentStep = 1;
            if ($state.current.name === 'access.lockme') {
              $scope.lockme = true;
              $scope.login.data.email = $stateParams.email;
            }
          } else {
            user.identity().then(function (userDetails) {
              var signinSuccessCb = authCallbacks.getSigninCallback();
              $scope.isLoggedIn = true;
              $scope.login.data.email = userDetails.email;
              $scope.signup.mid = userDetails.current;
              $scope.signup.userid = userDetails.user.id;
              $scope.signup.data.email = userDetails.email;
              $scope.signup.settings.partner_intent = userDetails.partner_intent;
              if (!user.isPreSignupDone()) {
                if (userDetails) {
                  Object.assign($scope.signup.merchantData, userDetails.pre_signup);
                }
                goToRelevantQuestion();
                $scope.login.currentStep = 2;
                $state.transitionTo(
                  'access.pre_signup',
                  {},
                  {
                    notify: false,
                  },
                );
                $scope.showTopbar = true;
              } else if (!user.isVerified()) {
                goToVerification();
              } else {
                if (signinSuccessCb) {
                  signinSuccessCb(userDetails);
                } else {
                  $scope.goToDashboard(userDetails);
                }
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
              },
            );
            $scope.showTopbar = false;
            $scope.login.currentStep = 1;
          } else {
            user.identity().then(function (userDetails) {
              setExperimentsFlags(userDetails.experiments);
              // if pre sign up pending
              $scope.isLoggedIn = true;
              $scope.login.data.email = userDetails.email;
              $scope.signup.mid = userDetails.current;
              $scope.signup.userid = userDetails.user.id;
              $scope.signup.data.email = userDetails.email;
              $scope.signup.settings.partner_intent = userDetails.partner_intent;
              if (!user.isPreSignupDone()) {
                Object.assign($scope.signup.merchantData, userDetails.pre_signup);

                $scope.login.currentStep = 2;
                goToRelevantQuestion();
              } else if (!user.isVerified()) {
                goToVerification();
              }
            });
          }
        }
      } else if ($state.current.name === 'access.signup') {
        $scope.showTopbar = true;
        if (user.isAuthenticated()) {
          $state.transitionTo(
            'access.pre_signup',
            {},
            {
              notify: false,
            },
          );
          $scope.showTopbar = true;
          var userDetails = user.getIdentity();
          $scope.rightLayout = true;
          $scope.isLoggedIn = true;
          $scope.login.data.email = userDetails.email;
          $scope.signup.mid = userDetails.current;
          $scope.signup.userid = userDetails.user.id;
          $scope.signup.data.email = userDetails.email;
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
          $scope.signup.currentSubStep = 2;
        } else if (!merchantData.transaction_volume) {
          $scope.signup.currentSubStep = 1;
        } else {
          $scope.signup.currentSubStep = 2;
        }
        $timeout(function () {
          $scope.noTransition = false;
        }, 0);
      }

      $scope.canSkipIntermediateScreens = function () {
        return (
          $scope.signup.settings.partner_intent || $scope.signup.merchantData.business_type == 11
        );
      };
      $scope.goToForgotPwd = function () {
        tracking.pushEvents({
          event_name: 'non_login_actions',
          event_type: 'initiated',
          properties: {
            action: 'click Forgot Password',
          },
        });
        var toRoute = 'access.forgotpwd';
        $state.transitionTo(
          toRoute,
          {},
          {
            notify: false,
          },
        );
        $scope.showTopbar = false;
        $scope.login.currentStep = 0;
        $scope.alerts.resetAlerts();
      };

      window.onloadCallback = function () {
        grecaptcha.render('login-recaptcha', {
          sitekey: window.INVISIBLE_CAPTCHA_SITE_KEY,
          callback: onCaptchaSubmit,
          'error-callback': onCaptchaError,
        });
      };

      const renderRecaptchaScript = function (loadCheckbox) {
        let captchaScript = 'https://www.google.com/recaptcha/api.js';
        if (!loadCheckbox)
          captchaScript = captchaScript.concat('?onload=onloadCallback&render=explicit');
        if (!checkScriptExists(captchaScript)) {
          let script = document.createElement('script');
          script.src = captchaScript;
          script.id = 'recaptcha';
          script.async = true;
          script.defer = true;
          document.documentElement.appendChild(script);
        }
      };

      const removeRecaptchaScript = function () {
        let element = document.getElementById('recaptcha');
        if (element) element.parentNode.removeChild(element);
      };

      const clearErrors = function () {
        $scope.inlineEmailError = '';
        $scope.inlinePasswordError = '';
      };

      $scope.sendLoginCredentials = function ($valid) {
        clearErrors();

        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return false;
        }

        if (!$scope.emailRegex.test($scope.login.data.email)) {
          $scope.inlineEmailError = 'Please enter a valid email id';
          return false;
        }

        if (!$scope.login.data.password) {
          $scope.inlinePasswordError = 'Please enter a valid password';
          return false;
        }

        fireDLInitiatedEvents('login.native_auth', { emailId: $scope.login.data.email });

        /**
         * Condition to disable captcha on staging & during running automated test suites on production
         * isTestEnv will be passed from test suites run for production
         * window.parent = X (opening PG in iframe)
         */
        if (window.parent.isTestEnv || window.isTestEnv || (!isProd && !isAxisBankUATEnv)) {
          login('Faked');
        } else if (isProd || isAxisBankUATEnv) {
          updateSpinnerState('show');
          if (window.grecaptcha && window.grecaptcha.execute) {
            grecaptcha.reset();
            grecaptcha.execute();

            /**
             * To hide the spinner if user clicks outside the captcha challenge box
             * There is no captcha close callback to handle this
             * So this is a work around :)
             */
            setTimeout(function () {
              updateSpinnerState('hide');
            }, 5000);
          } else {
            /**
             * - For users where captcha script didnt load, we'll wait for 3sec for the
             * script to load else call login with `Faked`.
             * - For whitelisted merchants, the captcha script will be blocked on there end
             * and login will happen with `Faked` value after 5 sec timeout.
             * - For non-whitelisted merchants, if script loads between 3 sec else
             * `Faked` val to Login api.
             */
            setTimeout(function () {
              if (window.grecaptcha && window.grecaptcha.execute) {
                grecaptcha.reset();
                grecaptcha.execute();
              } else {
                login('Faked');
              }
              updateSpinnerState('hide');
            }, 5000);
          }
        }
      };

      const login = function (captchaVal) {
        fireDLInitiatedEvents('login.login', { method: 'email' });
        pushPromMetric({ flow: 'login', label: 'login_initiate' });

        $scope.login.data.captcha = captchaVal;

        var payload = {
          method: 'post',
          url: '/user/signin',
          transformRequest: transformRequestAsFormPost,
          data: $scope.login.data,
        };

        payload.headers = {
          'X-RECAPTCHA-MODE': 'invisible',
        };

        var request = $http(payload);
        updateSpinnerState('show');
        $scope.alerts.resetAlerts();
        request.success(function (data) {
          if (data.success) {
            if (payload.data.otp && payload.data.otp.length) {
              window.rzpQ.push(
                window.rzpQ.now().onbr().success('login.2fa_otp', {
                  source: 'sign_in',
                  sessionId: window.session_id,
                  emailId: $scope.login.data.email,
                  mode: $scope.eventsMode,
                }),
              );
              pushPromMetric({ flow: 'login', label: 'login_success_2fa' });
            }
            $scope.successFullSignin();
          } else {
            if (payload.data.otp && payload.data.otp.length) {
              window.rzpQ.push(
                window.rzpQ.now().onbr().failed('login.2fa_otp', {
                  source: 'sign_in',
                  sessionId: window.session_id,
                  emailId: $scope.login.data.email,
                  mode: $scope.eventsMode,
                  error: data.errors[0],
                }),
              );
            }
            updateSpinnerState('hide');
            window.grecaptcha && grecaptcha.reset();
            var firstError = data.errors[0];

            if (typeof firstError === 'string') {
              // errors to be displayed directly
              if (firstError.includes('email not confirmed')) {
                // go to email not verified screen
                $scope.email_not_verified = true;
                $scope.login.currentStep = 2;
              } else {
                $scope.alerts.addAlert('danger', firstError);
                fireDLFailureEvents('login.login', {
                  emailId: $scope.login.data.email,
                  error: firstError,
                  method: 'email',
                  captcha: $scope.login.data.captcha === 'Faked' ? 'Faked' : '',
                });
              }
            } else if (typeof firstError === 'object' && !!firstError.internal_error_code) {
              $scope.handleErrorsWithInternalCode(firstError);
            }
          }
        });
      };

      /**
       * This func is being called as captcha callback
       * @param val
       */
      onCaptchaSubmit = function (val) {
        tracking.pushEvents({
          event_name: 'recaptcha',
          event_type: 'success',
        });
        login(val);
      };

      /**
       * Attempt captcha load once again if script fails
       */
      var isCaptchaReloadAttempted = false;
      onCaptchaError = function () {
        if (!isCaptchaReloadAttempted) {
          $scope.loadCaptcha();
          isCaptchaReloadAttempted = true;
        } else {
          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ.now().onbr().failed('recaptcha', {
                error: 'Could not connect to captcha.',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
                version: 1,
              }),
            );
        }
      };

      /**
       * This func is being called as checkbox captcha callback
       * @param val
       */
      onCheckboxCaptchaSubmit = function (val) {
        $scope.login.isCaptchaSuccess = true;
        $scope.signup.submissionDisabled = false;
        $scope.checkboxCaptcha = val;
        $scope.$apply();
      };

      onCheckboxCaptchaExpire = function () {
        $scope.signup.submissionDisabled = true;
        $scope.login.isCaptchaSuccess = false;
        $scope.checkboxCaptcha = '';
        $scope.$apply();
      };

      $scope.successFullSignin = function () {
        // check questions have been answered or not
        user
          .identity(true)
          .then(function (userDetails) {
            var signinSuccessCb = authCallbacks.getSigninCallback();

            setExperimentsFlags(userDetails.experiments); // set razorX experiment flags
            if (signinSuccessCb) {
              signinSuccessCb(userDetails);
            }

            if (user.isVerified() && user.isPreSignupDone()) {
              // parse query parameters to object
              // ?next=foo&q=bar → { next: 'foo', q: 'bar' }
              var queryParams = location.search
                .slice(1)
                .split(/=|&/)
                .reduce(function (map, param, index, array) {
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
                $scope.goToDashboard(userDetails);
              }
            } else {
              $scope.isLoggedIn = true;
              updateSpinnerState('hide');
              if (userDetails) {
                $scope.login.data.email = userDetails.email;
                $scope.signup.settings.partner_intent = userDetails.partner_intent;
                Object.assign($scope.signup.merchantData, userDetails.pre_signup);
              }
              if (!user.isPreSignupDone()) {
                $scope.email_not_verified = false;
                goToRelevantQuestion();
                $scope.login.currentStep = 2;
                $scope.signup.settings.partner_intent = userDetails.partner_intent;

                $state.transitionTo(
                  'access.pre_signup',
                  {},
                  {
                    notify: false,
                  },
                );
              } else if (!user.isVerified()) {
                goToVerification();
              }
            }
          })
          .catch(function (errors) {
            updateSpinnerState('hide');

            fireDLFailureEvents('login.login', {
              emailId: $scope.login.data.email,
              error: errors[0],
              method: 'email',
            });

            $scope.alerts.addAlert('danger', errors[0]);
          });
      };

      $scope.updateMobileFromLogin = function ($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        const data = {
          contact_mobile: $scope.login.data.contact_mobile,
        };

        const payload = {
          method: 'PATCH',
          url: '/user/2fa/contact',
          data: data,
        };

        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('login.2fa_change_mobile_number', {
            source: 'sign_in',
            sessionId: window.session_id,
            emailId: $scope.login.data.email,
            mode: $scope.eventsMode,
          }),
        );

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        updateSpinnerState('show');

        request.success(function (data) {
          updateSpinnerState('hide');

          if (data.success) {
            $scope.goToLoginStep(4);
          } else {
            $scope.alerts.addAlert(data.errors[0]);
          }
        });
      };

      $scope.verifyMobile = function ($valid) {
        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }

        pushPromMetric({ flow: 'login', label: 'login_initiate_2fa' });

        const data = {
          email: $scope.login.data.email,
          password: $scope.login.data.password,
          otp: $scope.login.data.otp,
        };

        const payload = {
          method: 'POST',
          url: '/user/2fa_setup/verify-mobile',
          data: data,
        };

        var request = $http(payload);
        updateSpinnerState('show');
        $scope.alerts.resetAlerts();

        request.success(function (data) {
          if (data.success) {
            $scope.successFullSignin();
            window.rzpQ.push(
              window.rzpQ.now().onbr().success('login.2fa_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
              }),
            );
            pushPromMetric({ flow: 'login', label: 'login_success_2fa' });
          } else {
            updateSpinnerState('hide');
            $scope.alerts.addAlert('danger', data.errors[0]);
            window.rzpQ.push(
              window.rzpQ.now().onbr().failed('login.2fa_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
                error: data.errors[0],
              }),
            );
          }
        });
      };

      $scope.verify2FaOtp = function ($valid) {
        const data = {
          otp: $scope.secondFA.data.otp,
        };

        const payload = {
          url: '/user/2fa/otp-verify',
          method: 'POST',
          data: data,
        };

        pushPromMetric({ flow: 'login', label: 'login_initiate_2fa' });

        const request = $http(payload);
        updateSpinnerState('show');
        $scope.alerts.resetAlerts();
        request.success(function (data) {
          if (data.success) {
            window.rzpQ.push(
              window.rzpQ.now().onbr().success('login.2fa_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
              }),
            );
            pushPromMetric({ flow: 'login', label: 'login_success_2fa' });
            $scope.successFullSignin();
          } else {
            window.rzpQ.push(
              window.rzpQ.now().onbr().failed('login.2fa_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
                error: data.errors[0],
              }),
            );
            updateSpinnerState('hide');
            const firstError = data.errors[0];
            if (typeof firstError === 'object' && !!firstError.internal_error_code) {
              $scope.handleErrorsWithInternalCode(firstError);
            } else {
              $scope.alerts.addAlert('danger', data.errors[0]);
            }
          }
        });
      };

      $scope.resendOtp = function () {
        const payload = {
          method: 'post',
          url: '/user/2fa/otp-resend',
        };

        window.rzpQ.push(
          window.rzpQ.now().onbr().initiated('login.2fa_resend_otp', {
            source: 'sign_in',
            sessionId: window.session_id,
            emailId: $scope.login.data.email,
            mode: $scope.eventsMode,
          }),
        );

        var request = $http(payload);
        $scope.login.resendingOtp = true;
        $scope.alerts.resetAlerts();
        request.success(function (data) {
          if (data.success) {
            $scope.login.resendingOtp = false;
            window.rzpQ.push(
              window.rzpQ.now().onbr().success('login.2fa_resend_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
              }),
            );
          } else {
            $scope.alerts.addAlert(data.errors[0]);
            window.rzpQ.push(
              window.rzpQ.now().onbr().initiated('login.2fa_resend_otp', {
                source: 'sign_in',
                sessionId: window.session_id,
                emailId: $scope.login.data.email,
                mode: $scope.eventsMode,
                error: data.errors[0],
              }),
            );
          }
        });
      };

      $scope.resendVerificationEmail = function () {
        window.rzpQ.push(
          window.rzpQ.now().onbr().success('signup.email_verification', {
            source: 'sign_in',
            mode: $scope.eventsMode,
          }),
        );

        var payload = {
          method: 'post',
          url: '/user/resend',
          transformRequest: transformRequestAsFormPost,
        };

        var request = $http(payload);
        $scope.alerts.resetAlerts();
        request.success(function (data) {
          if (data.success) {
            $scope.alerts.addAlert('success', 'Verification email resent.');
          } else {
            $scope.alerts.resetAlerts();
            angular.forEach(data.errors, function (value) {
              $scope.alerts.addAlert('danger', value);
            });
          }

          window.ga &&
            window.ga(
              'send',
              'event',
              'Signup - Steps',
              'Click - Resend Verification Email',
              JSON.stringify(data.errors),
            );
        });
      };

      $scope.forgotPwdSubmit = function ($valid) {
        tracking.pushEvents({
          event_name: 'send_password_reset_link',
          event_type: 'initiated',
          properties: {
            action: 'send reset link',
          },
        });

        if (!$valid) {
          $scope.alerts.addAlert('danger', 'Please fill all the fields', true);
          return true;
        }
        updateSpinnerState('show');
        var data = {
          email: $scope.login.data.email,
        };
        var request = $http({
          method: 'post',
          url: '/user/api/live/users/reset-password',
          data: data,
        });
        request.success(function (data) {
          updateSpinnerState('hide');
          if (data.success) {
            var message =
              'We have sent a reset password link to your email. Didn’t receive the email? Check email address again or look in your spam folder.';

            $scope.alerts.addAlert('success', message);
          } else {
            $scope.alerts.resetAlerts();
            $scope.alerts.addAlert(
              'danger',
              'Something went wrong, please try again after sometime.',
            );
          }
        });
      };

      function displaySignupEvent() {
        if (!$scope.isSignupDisplayEventFired) {
          window.rzpQ &&
            window.rzpQ.push(
              window.rzpQ.now().onbr().success('signup.display_signup_page', {
                mode: $scope.eventsMode,
                version: 1,
                service: $scope.currentService,
              }),
            );
          $scope.isSignupDisplayEventFired = true;
        }
      }

      // Fire hotjar trigger for survey
      function hotjarSurvey() {
        window.hj =
          window.hj ||
          function () {
            (hj.q = hj.q || []).push(arguments);
          };
        setTimeout(function () {
          if (document.getElementById('email').value === '') {
            window.hj('trigger', 'signup-no-email-survey');
          }
        }, 4500);
      }

      if ($location.path().includes('access/signup')) {
        hotjarSurvey();
        displaySignupEvent();
      }

      $scope.logoutAndGoToLogin = function () {
        var request = $http({
          method: 'post',
          url: '/user/logout',
        });
        request.finally(function () {
          user.identity(true);
          // go to login
          $scope.rightLayout = true;
          $state.transitionTo(
            'access.signin',
            {},
            {
              notify: false,
            },
          );
          $scope.showTopbar = false;

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

      $scope.trackContactUsClick = function (e) {
        window.ga && window.ga('send', 'event', 'Signup - Steps', 'Click - Contact Us');
        window.rzpQ.push(
          window.rzpQ.now().onbr().success('signup.click_other_links', {
            source: 'contact_us',
            mode: $scope.eventsMode,
          }),
        );
      };

      $scope.trackBannerClick = function (e) {
        window.ga && window.ga('send', 'event', 'Website - Banner', 'Click - Top Banner');
      };

      $scope.timerFunction = function () {
        var deadline = new Date('April 1, 2020 00:00:00').getTime();
        var daysEls = document.querySelector('.topbar-container .days .number');
        var hoursEls = document.querySelector('.topbar-container .hours .number');
        var minutesEls = document.querySelector('.topbar-container .minutes .number');
        var secondsEls = document.querySelector('.topbar-container .seconds .number');
        var timer = document.querySelector('.topbar-container .top-timer');
        var now, timeLeft, days, hours, minutes, seconds;

        var interval = setInterval(function () {
          now = new Date().getTime();
          timeLeft = deadline - now;
          days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
          hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
          minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
          seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

          days = days.toString();
          hours = hours.toString();
          minutes = minutes.toString();
          seconds = seconds.toString();

          daysEls.innerHTML = days.length === 1 ? '0' + days : days;
          hoursEls.innerHTML = hours.length === 1 ? '0' + hours : hours;
          minutesEls.innerHTML = minutes.length === 1 ? '0' + minutes : minutes;
          secondsEls.innerHTML = seconds.length === 1 ? '0' + seconds : seconds;

          if (timeLeft < 0) {
            timer.classList.add('hide');
            clearInterval(interval);
          }
        }, 1000);
      };

      /*
       * stepName: at which step name
       * sourceLabel: from which CTA, step
       * */
      $scope.trackStepClicksOnMore = function (stepName, sourceLabel) {
        if (!stepName || !sourceLabel) {
          return;
        }

        window.ga &&
          window.ga('send', 'event', 'Signup - Steps', 'Step - ' + stepName, sourceLabel);
      };

      /*
       * toStepName: to which link the back click points
       * */
      $scope.trackBackClick = function (toStepName) {
        if (!toStepName) {
          return;
        }

        window.ga && window.ga('send', 'event', 'Signup - Steps', 'Click - Back', toStepName);

        window.rzpQ.push(
          window.rzpQ.now().onbr().success('signup.back_action', {
            source: toStepName,
            mode: $scope.eventsMode,
          }),
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
                callback: function () {
                  $scope.goToSigninLayout();
                },
              },
              {
                name: supportedEvents.signup,
                callback: function (userDetails) {
                  $scope.goToSignupLayout(userDetails);
                },
              },
              {
                name: supportedEvents.signinSuccess,
                hasReply: true,
                callback: function (reply) {
                  authCallbacks.setSigninCallback(function (userData) {
                    reply({ user: userData });
                  });
                },
              },
              {
                name: supportedEvents.onShowSignin,
                hasReply: true,
                callback: function (reply) {
                  $scope.onShowSignin = reply;
                  return $scope.rightLayout && reply($scope.login);
                },
              },
              {
                name: supportedEvents.onShowSignup,
                hasReply: true,
                callback: function (reply) {
                  $scope.onShowSignup = reply;
                  return !$scope.rightLayout && reply();
                },
              },
            ],
            'auth',
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

      $scope.onGotCouponCodeClick = function () {
        $scope.allowInput = true;
        window.ga && window.ga('send', 'event', 'Signup - Steps', 'Click- Got a coupon code');
      };

      $scope.onCouponChange = function () {
        $scope.coupon.val = $filter('uppercase')($scope.coupon.val);
      };

      $scope.clearCoupon = function () {
        $scope.coupon.val = '';
      };

      $scope.removeCoupon = function () {
        $scope.coupon.val = '';
        $scope.coupon.disabled = false;
        $scope.coupon.status = '';
        $scope.coupon.msg = '';
        delete $scope.signup.merchantData.coupon_code;
      };

      $scope.validateCoupon = function () {
        if ($scope.coupon.val === '') {
          return;
        }

        $scope.coupon.disabled = true;
        $scope.coupon.isValidating = true;

        window.ga &&
          window.ga('send', 'event', 'Signup - Steps', 'Click - Coupon Apply', $scope.coupon.val);

        var payload = {
          method: 'post',
          url: 'user/coupons/validate',
          data: {
            code: $scope.coupon.val,
          },
        };

        $http(payload).success(function (response) {
          var msg = '',
            status = '';

          if (response.success) {
            var today = new Date();
            var amount = response.data.credit_amount / 100;
            var expiryDate = new Date(
              today.setDate(today.getDate() + response.data.expire_days),
            ).toDateString();

            msg = $sce.trustAsHtml(
              'Transactions worth amount ' +
                '<strong>₹' +
                amount +
                '</strong>' +
                '/- will be free of charge' +
                (response.data.expire_days ? ' till ' + expiryDate : '.'),
            );

            status = 'success';
            window.ga &&
              window.ga(
                'send',
                'event',
                'Signup - Steps',
                'Coupon - Response',
                'Success | ' + $scope.coupon.val,
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
                'Fail | ' + $scope.coupon.val + ' | ' + response.errors[0],
              );
          }
          $scope.coupon.isValidating = false;
          $scope.coupon.msg = msg;

          $scope.coupon.status = status;
        });
      };

      $scope.handleErrorsWithInternalCode = function (error) {
        switch (error.internal_error_code) {
          case 'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED': {
            $scope.goToLoginStep(4);
            break;
          }

          case 'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED': {
            $scope.goToLoginStep(5);
            break;
          }

          case 'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP': {
            $scope.login.data.otp = '';
            $scope.alerts.addAlert('danger', error.description, true);
            break;
          }

          case 'BAD_REQUEST_LOCKED_USER_LOGIN': {
            $scope.goToLoginStep(7);
            break;
          }
        }
      };

      function shouldAutoApplyOffer() {
        if ($scope.currentService === 'X') return false;
        var startDate = new Date('September 09, 2020 00:00:01').getTime();
        var endDate = new Date('September 30, 2020 00:00:59').getTime();
        var now = new Date().getTime();
        if (now > startDate && now < endDate) {
          return true;
        }
        return false;
      }

      function shouldRenderCouponCode() {
        var coupon_code = $location.search().coupon_code;
        var shouldRender = false;

        if (!coupon_code && shouldAutoApplyOffer()) {
          coupon_code = 'SURGESEPT';
        }

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

      $scope.$watch('signup.currentSubStep', function (newVal) {
        if (newVal === 2) {
          if (!isHostedInBB && !$scope.coupon.shouldRender && user.getTreatment('coupons')) {
            $scope.coupon.shouldRender = true;
          }

          if ($scope.coupon.shouldRender && $scope.coupon.val.length > 0) {
            $scope.validateCoupon();
            $scope.onGotCouponCodeClick();
          }
        }
      });

      $scope.$watch('signup.merchantData.business_type', function () {
        trackCompanyAB(false);
      });

      function isUnregisteredBusiness(bizType) {
        return bizType === 11;
      }

      function sendSignUpCompleteEvents() {
        var businessType = Number($scope.signup.merchantData.business_type);

        var ec = 'Signup - Steps';
        var ea = 'Click - Finish';
        var el = isUnregisteredBusiness(businessType) ? 'Unregistered' : 'Registered';

        var facebookEvents = ['signup_complete'];

        if (isUnregisteredBusiness(businessType)) {
          facebookEvents.push('signup_complete_unreg');
        } else {
          facebookEvents.push('signup_complete_reg');
        }

        window.ga && window.ga('send', 'event', ec, ea, el);

        // IMPORTANT: DO NOT REMOVE THESE (USED FOR MARKETING PURPOSES - TRACK SIGNUP ATTEMPTS)
        window.ga && ga('send', 'event', 'sign-up-form-success');
        window.ga && ga('old.send', 'event', 'sign-up-form-success');

        window.rzpQ &&
          window.rzpQ.push(
            window.rzpQ.now().onbr().success('signup.finish_signup', {
              mode: $scope.eventsMode,
              emailId: $scope.signup.data.email,
              mid: $scope.signup.mid,
              userid: $scope.signup.userid,
              version: 1,
              isWhatsAppOptIn: $scope.signup.isWhatsAppOptIn,
            }),
          );

        facebookEvents.forEach(function (event) {
          window.rzpAnalytics({
            name: 'facebook',
            event: event,
          });
        });

        window.rzpAnalytics({
          name: 'quora',
          event: 'CompleteRegistration',
        });

        window.rzpAnalytics({
          name: 'reddit',
          event: 'SignUp',
        });
      }

      // Updating contact properties on hubspot
      function updateHubSpotContactProperty() {
        var merchantData = $scope.signup.merchantData,
          details = $scope.signup.details,
          department = null,
          business_type = null,
          transaction_volume = details.transaction_volume[merchantData.transaction_volume],
          business_type_list = Object.keys(details.business_type);

        for (var key in business_type_list) {
          idx = business_type_list[key];

          if (details.business_type[idx].value === merchantData.business_type) {
            business_type = details.business_type[idx].name;

            break;
          }
        }

        window.trackHubs({
          name: 'update_property',
          data: {
            email: $scope.signup.data.email,
            signup_business_type: business_type,
            signup_transaction_volume: transaction_volume,
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
    function () {
      return function (scope, element) {
        element.bind('keydown', function (e) {
          var keyCode = e.keyCode || e.which;
          if (keyCode == 9) {
            e.preventDefault();
          }
        });
      };
    },
  ])
  .directive('alert', [
    '$window',
    '$timeout',
    function ($timeout) {
      return {
        transclude: true,
        restrict: 'E',
        template: `
            <div class="alert alert-{{type}} alert-dismissable" role="alert">
              <button type="button" class="close" ng-click="close()">
                <span aria-hidden="true">×</span>
              </button>
            <div ng-transclude></div></div>
          `,
        scope: {
          type: '@',
          close: '&',
        },
      };
    },
  ]);

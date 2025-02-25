import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import hasKey from '@razorpay/universe-utils/hasKey';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import { Formik } from 'formik';
import TextInput from '../../shared/TextInput';
import Button from '../../shared/Button';
import Screen from '../../shared/Screen/';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useLocationQuery from '../../shared/useLocationQuery';
import GoogleAuthCookieModal from '../../shared/GoogleAuth/GoogleAuthCookieModal';
import AccNotFoundModal from '../../shared/GoogleAuth/GoogleAuthAccNotFoundModal';
import Separator from '../../shared/Separator';
import { authApiResponse } from '../../api/apiHelpers';
import {
  redirectTo,
  goToDashboard,
  goToScreen,
  screenMap,
  SIGNIN,
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  authMethods,
  authModes,
  setMidCookie,
  canRedirectToEasyDashboard,
} from '../screenHelpers';
import {
  G_AUTH_TYPES,
  ONE_TAP_SELECTOR,
  EMAIL_ALREADY_TAKEN,
} from '../../shared/GoogleAuth/GoogleAuthConstants';
import {
  resizeOneTapIframe,
  initOneTap,
  loadingGauthScript,
} from '../../shared/GoogleAuth/GoogleAuthHelpers';
import { ButtonView, OneTapView, OneTapWrapper } from '../../shared/GoogleAuth/GoogleAuthViews';
import {
  handleErrorCodes,
  UNRECOGNIZED_ERROR,
  gAuthInitCallStatus,
  gAuthTypes,
} from '../SignIn/signInHelpers';
import signInEvents from '../SignIn/signInEvents';
import captureException, { sentryFlows } from '../../shared/captureException';
import { getSignMethod, validateSignInStep1Form } from '../../utils/validationService';
import featureFlags from '../../utils/featureFlags';
import signUpEvents from '../SignUp/signUpEvents';
import { getCaptchaVariant } from '../../utils/captchaService';
import TermsAndConditionModal from '../../shared/TermsAndConditionModal';
import { evaluateExperiment, experimentDataMap } from '../../utils/splitz';

// eslint-disable-next-line max-statements
const SingInGoogle = ({ handleNext, handleEffect, ...props }) => {
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();

  const [showCookieModal, setShowCookieModal] = useState(false);
  const [isOneTapEnabled, setIsOneTapEnabled] = useState(false);
  const [isInlineOneTap, setIsInlineOneTap] = useState(true);
  const [isGauthTypeDecided, setIsGauthTypeDecided] = useState(false);
  const [isPageLoadEventFired, setPageLoadEvent] = useState(false);
  const [otpApiError, setOtpApiError] = useState(false);
  const [showAccNotFound, setShowAccNotFound] = useState(false);
  const [loginEmail, setLoginEmail] = useState(null);

  const oneTapWrapperRef = useRef(null);
  const emailOrNumberRef = useRef(null);
  const isGauthBtnInitCalled = useRef(gAuthInitCallStatus.NOT_CALLED);
  const isGauthOneTapInitCalled = useRef(gAuthInitCallStatus.NOT_CALLED);
  const gAuthEmail = useRef(null);
  const gAuthToken = useRef(null);

  const easyOnboardingEventProperty = {
    type: authMethods.GAUTH,
    merchantCountry: 'IN',
    easyOnboarding: true,
  };

  // prettier-ignore
  const {
    authClientId,
    isOneTapOn,
    isOneTapScriptFailed,
    isFocusOnField,
    isGoogleOauthEnabled,
    orgName
  } = props;

  let showSeparator = !isOneTapOn;
  let showGauthButton = !isOneTapOn;
  let showOneTap = isOneTapOn;
  // only either of them should have value at a time. When one is intialized other is initialized with empty string
  const emailOrNumber = state.user.mobileNumber || state.user.email;

  if (isOneTapOn && isGauthTypeDecided && isGoogleOauthEnabled) {
    showSeparator = isInlineOneTap || !isOneTapEnabled;
    showGauthButton = !isOneTapEnabled;
    showOneTap = isOneTapEnabled;
  }

  if (!isPageLoadEventFired && window.rzpQ) {
    signInEvents.trackPageLoad();
    setPageLoadEvent(true);
  }

  const getGauthType = (googleAuthType) => {
    let gAuthType;
    if (googleAuthType) {
      gAuthType = googleAuthType;
    } else if (isGauthTypeDecided && isOneTapEnabled) {
      gAuthType = gAuthTypes.ONE_TAP;
    } else {
      gAuthType = gAuthTypes.BUTTON;
    }
    return gAuthType;
  };

  const isEasyAuthExpEnabled = evaluateExperiment(experimentDataMap.oauth_easy_onboarding);
  const signupCampaign =
    orgName === 'rzp' && isEasyAuthExpEnabled ? { signup_campaign: 'easy_onboarding' } : {};

  const proceedWithSignIn = async () => {
    const { loginDetails, signInGoogleAuthType } = state;
    setLoggedInViaInStorage(loginDetails?.user?.loggedInVia, loginEmail);
    const response = await actions.getUserDetails();
    signInEvents.bindSegmentUserIdentity(response.user);
    setMidCookie(response?.user?.mid, response?.user?.id);
    actions.showLoader();

    if (response.user.isConfirmed && response.user.isPreSignUpComplete) {
      signInEvents.trackSignInSuccess({
        email: loginEmail,
        method: authMethods.GAUTH,
        googleAuthVariant: signInGoogleAuthType,
        userId: response.user.id,
        mid: response.user.mid,
      });

      if (state.user.redirectUrl) {
        redirectTo(state.user.redirectUrl);
      } else {
        goToDashboard();
      }
    } else if (canRedirectToEasyDashboard(response.user, orgName)) {
      // if user signup with oauth and singup_campaign is easy_onboarding redirect to easy dashborad
      redirectTo('https://easy.razorpay.com/onboarding');
    } else {
      actions.hideLoader();
      if (!response.user.isPreSignUpComplete) {
        goToOnboardingScreen({ user: response.user, navigate, locationQuery });
      } else {
        goToScreen({ screen: screenMap.verifyEmail, navigate, locationQuery });
      }
    }
  };

  const handleTncModalClose = () => {
    actions.hideTncPopup();
  };

  const handleAcceptTnC = async () => {
    try {
      actions.hideTncPopup();
      await actions.updateTermsAndConditions(true);
      proceedWithSignIn();
    } catch (error) {
      snackbar.error(error?.message);
    }
  };

  const loginHandler = useCallback(
    async (tokenId, email, googleAuthType) => {
      const gAuthType = getGauthType(googleAuthType);
      actions.updateState({
        authMethod: authMethods.GAUTH,
        authMode: authModes.GAUTH,
        signInGoogleAuthType: gAuthType,
      });
      try {
        signInEvents.trackSignInInitiate({
          email,
          method: authMethods.GAUTH,
          googleAuthVariant: gAuthType,
        });
        const loginResponse = await actions.loginWithGoogle(email, tokenId, SIGNIN);
        // store email and use inside proceedWithSignIn
        setLoginEmail(email);
        if (loginResponse?.user?.show_tnc_popup) {
          actions.showTncPopup();
        } else {
          proceedWithSignIn();
        }
      } catch (error) {
        actions.hideLoader();
        // redirect to concerned flow via handleErrorCodes or continue and log the error accordingly
        const errorType = handleErrorCodes({
          error,
          navigate,
          locationQuery,
          updateUser: actions.updateUser,
        });
        if (errorType === authApiResponse.accountNotFound) {
          gAuthEmail.current = email;
          gAuthToken.current = tokenId;
          setShowAccNotFound(true);
          signInEvents.trackGoogleAccountNotFoundSuccess({
            email,
            method: authMethods.GAUTH,
            googleAuthVariant: gAuthType,
          });
        } else if (errorType === UNRECOGNIZED_ERROR) {
          // if login api fails show G_AUTH_TYPES.button as oneTap disappears after error
          setIsOneTapEnabled(false);
          signInEvents.trackSignInFailure({
            email,
            method: authMethods.GAUTH,
            googleAuthVariant: gAuthType,
            error: error.message,
            actualError: error.actualError,
          });
          captureException(error, {
            flow: sentryFlows.SIGNIN_WITH_GOOGLE,
            email,
          });
          snackbar.error(error?.message);
        }
      }
    },
    // locationQuery is not set in dependency as its updating frequently even when location query remains same
    [actions, locationQuery, snackbar, state.user.redirectUrl],
  );

  const signUpHandler = async (tokenId, email) => {
    const gAuthType = getGauthType();
    actions.updateState({
      authMethod: authMethods.GAUTH,
      authMode: authModes.GAUTH,
      signInGoogleAuthType: gAuthType,
    });

    try {
      signInEvents.trackCreateAccountInitiate({
        email,
        method: authMethods.GAUTH,
        googleAuthVariant: gAuthType,
      });
      const signupResponse = await actions.registerUserWithGoogle(email, tokenId, signupCampaign);
      setLoggedInViaInStorage(signupResponse.user.loggedInVia, email);

      const response = await actions.getUserDetails();
      signUpEvents.bindSegmentUserIdentity(response.user, locationQuery);

      let easyOnboardingProperties = {};
      const shouldRedirectToEasyDashboard = canRedirectToEasyDashboard(response.user, orgName);

      if (shouldRedirectToEasyDashboard) {
        easyOnboardingProperties = {
          ...easyOnboardingEventProperty,
          merchantId: response?.user?.mid,
          userId: response?.user?.id,
        };
      }
      actions.showLoader();

      signInEvents.trackCreateAccountSuccess({
        email,
        method: authMethods.GAUTH,
        googleAuthVariant: gAuthType,
        mid: response?.user?.mid,
        easyOnboardingProperties,
      });

      if (response.user.isConfirmed && response.user.isPreSignUpComplete) {
        if (state.user.redirectUrl) {
          redirectTo(state.user.redirectUrl);
        } else {
          goToDashboard(state.signUpSource);
        }
      } else if (canRedirectToEasyDashboard(response.user, orgName)) {
        // if user signup with oauth and singup_campaign is easy_onboarding redirect to easy dashborad
        redirectTo('https://easy.razorpay.com/onboarding');
      } else {
        actions.hideLoader();
        if (!response.user.isPreSignUpComplete) {
          goToOnboardingScreen({ user: response.user, navigate, locationQuery });
        } else {
          goToScreen({ screen: screenMap.verifyEmail, navigate, locationQuery });
        }
      }
    } catch (error) {
      actions.hideLoader();
      if (error.message.includes(EMAIL_ALREADY_TAKEN)) {
        loginHandler(tokenId, email);
      } else {
        signInEvents.trackCreateAccountFailure({
          email,
          method: authMethods.GAUTH,
          googleAuthVariant: gAuthType,
          error: error.message,
          easyOnboardingProperties: easyOnboardingEventProperty,
        });
        // In case of onetap, if signup api fails show G_AUTH_TYPES.button as fallback
        setIsOneTapEnabled(false);
        snackbar.error(error?.message);

        captureException(error, {
          flow: sentryFlows.SIGNIN_WITH_GOOGLE,
          email,
        });
      }
    }
  };

  // This method is called upon load of OneTap and
  // also on actions such as closing of onetap, hiding of onetap, etc.
  const oneTapNotifyCallback = useCallback((notification) => {
    const notifyType = notification.getMomentType();
    if (notifyType === 'display') {
      if (notification.isDisplayed()) {
        signInEvents.trackDisplayGoogleAuthSuccess({
          googleAuthVariant: gAuthTypes.ONE_TAP,
        });
        setIsOneTapEnabled(true);
        const oneTapIframe = oneTapWrapperRef.current.querySelector(`iframe`);
        if (oneTapIframe) {
          resizeOneTapIframe(oneTapIframe, oneTapWrapperRef);
        } else {
          setIsInlineOneTap(false);
        }
      } else {
        setIsOneTapEnabled(false);
        signInEvents.trackDisplayGoogleAuthSuccess({
          googleAuthVariant: gAuthTypes.BUTTON,
          oneTapHideReason: notification.getNotDisplayedReason(),
        });
      }
    } else if (notifyType === 'skipped') {
      setIsOneTapEnabled(false);
      if (
        notification.getSkippedReason() === 'tap_outside' ||
        notification.getSkippedReason() === 'user_cancel'
      ) {
        signInEvents.trackOneTapClose();
      } else {
        signInEvents.trackDisplayGoogleAuthSuccess({
          googleAuthVariant: gAuthTypes.ONE_TAP,
          oneTapHideReason: notification.getNotDisplayedReason(),
        });
      }
    }
    setIsGauthTypeDecided(true);
  }, []);

  // This method is called upon a response from Google server on selecting an email from OneTap.
  // "tokenId" is a JWT(https://developers.google.com/identity/gsi/web/reference/js-reference#credential)
  const gAuthCallback = useCallback(
    (response) => {
      const tokenId = response.credential;
      /**
       * Records the method of authentication by the user. More info - https://developers.google.com/identity/gsi/web/reference/js-reference#select_by
       * @type {string}
       */
      const method = response.select_by;
      if (tokenId) {
        try {
          const email = JSON.parse(atob(tokenId.split('.')[1])).email;
          signInEvents.trackGoogleAuthSuccess({
            email,
            googleAuthVariant: method,
          });
          loginHandler(tokenId, email, method);
        } catch (e) {
          captureException(e, {
            flow: sentryFlows.SIGNIN_GOOGLE_ONE_TAP_CALLBACK,
          });
        }
      }
      setIsOneTapEnabled(false);
    },
    [loginHandler],
  );

  const initGauthOneTap = useCallback(() => {
    if (isOneTapOn) {
      initOneTap(SIGNIN, authClientId, gAuthCallback, oneTapNotifyCallback);
    }
  }, [isOneTapOn, authClientId, gAuthCallback, oneTapNotifyCallback]);

  const initGauthBtn = useCallback(async () => {
    const googleAuthScript = await loadingGauthScript();

    if (googleAuthScript) {
      googleAuthScript.accounts.id.initialize({
        client_id: authClientId,
        cancel_on_tap_outside: false,
        context: 'signin',
        callback: gAuthCallback,
        prompt_parent_id: ONE_TAP_SELECTOR,
      });

      // Google button render fallback
      googleAuthScript.accounts.id.renderButton(document.getElementById(G_AUTH_TYPES.button), {
        theme: 'filled_blue',
        size: 'large',
      });
    }
  }, [authClientId]);

  // must be called only once as it initializes gauth scripts
  useEffect(() => {
    if (isGauthBtnInitCalled.current === gAuthInitCallStatus.NOT_CALLED && isGoogleOauthEnabled) {
      isGauthBtnInitCalled.current = gAuthInitCallStatus.CALLED;
      initGauthBtn();
      handleEffect(); // used in testing
    }
  }, [initGauthBtn, isGoogleOauthEnabled, handleEffect]);

  // must be called only once as it initializes gauth scripts
  useEffect(() => {
    if (
      isGauthOneTapInitCalled.current === gAuthInitCallStatus.NOT_CALLED &&
      isGoogleOauthEnabled
    ) {
      isGauthOneTapInitCalled.current = gAuthInitCallStatus.CALLED;
      initGauthOneTap();
      handleEffect(); // used in testing
    }
  }, [initGauthOneTap, isGoogleOauthEnabled, handleEffect]);

  // focus on email input
  useEffect(() => {
    if (isFocusOnField) {
      emailOrNumberRef.current.focus();
    } else {
      emailOrNumberRef.current.blur();
    }
  }, [isFocusOnField]);

  // enable button when onetap script failed to load
  useEffect(() => {
    if (isOneTapScriptFailed) {
      setIsOneTapEnabled(false);
      setIsGauthTypeDecided(true);
    }
  }, [isOneTapScriptFailed]);

  const handleNextClick = async (values) => {
    const signInMethod = getSignMethod(values.emailOrNumber, SIGNIN);
    if (!signInMethod) {
      // Invalid email or number
      // This is never supposed to get called as the validation happens on frontend.
      // User cannot click next, if the number or email isn't valid
      return;
    }

    if (featureFlags.ENABLE_MOBILE_OTP_FLOW && signInMethod === authMethods.PHONE_NUMBER) {
      signInEvents.trackSignInNativeInitiate({
        captchaVariant: getCaptchaVariant(),
        method: signInMethod,
      });
      let otpApiResp;
      try {
        otpApiResp = await actions.sendLoginOTP({ mobileNumber: values.emailOrNumber });
      } catch (error) {
        const errorType = handleErrorCodes({
          error,
          navigate,
          locationQuery,
          updateUser: actions.updateUser,
        });
        if (error.code === authApiResponse.contactNotVerified) {
          actions.updateUser({
            email: '',
            mobileNumber: values.emailOrNumber,
            isMobileNumberVerified: false,
          });
          goToScreen({ screen: screenMap.verifyMobile, navigate, locationQuery });
          return;
        } else if (errorType === UNRECOGNIZED_ERROR) {
          signInEvents.trackSignInNativeFailure({
            error: error.message,
          });
          captureException(error, {
            flow: sentryFlows.SIGNUP_WITH_MOBILE_OTP,
          });
          setOtpApiError(error.message);
          return;
        }
      }
      if (window.google) {
        window.google.accounts.id.cancel(); // cancel ongoing google onetap signup
      }
      actions.updateUser({
        email: '',
        mobileNumber: values.emailOrNumber,
        isMobileNumberVerified: true,
        otpToken: otpApiResp.data.token,
      });
      actions.setAuthMethodAndAuthMode({
        authMethod: authMethods.PHONE_NUMBER,
        authMode: authModes.OTP,
      });

      goToScreen({ screen: screenMap.signInMobile, navigate, locationQuery });
    } else {
      // email password login
      if (window.google) {
        window.google.accounts.id.cancel(); // cancel ongoing google onetap signup
      }
      signInEvents.trackSignInNativeInitiate({
        email: values.emailOrNumber,
        captchaVariant: getCaptchaVariant(),
        method: signInMethod,
      });
      actions.setAuthMethodAndAuthMode({
        authMethod: authMethods.EMAIL,
        authMode: authModes.PASSWORD,
      });
      actions.updateUser({
        email: values.emailOrNumber,
        mobileNumber: '',
        otpToken: '',
      });

      goToScreen({ screen: screenMap.signInEmail, navigate, locationQuery });
    }
    handleNext(signInMethod); // used in testing
  };

  const handleCookieModalClose = () => {
    setShowCookieModal(false);
  };

  const handleAccModalClose = () => {
    setShowAccNotFound(false);
  };

  const retryLogin = () => {
    setShowAccNotFound(false);
    signInEvents.trackTryAnotherAccountPopInitiated();
    loginHandler(gAuthToken.current, gAuthEmail.current);
  };

  const handleCreateAccount = () => {
    setShowAccNotFound(false);
    // if easy onboarding signupCampaign is not empty then fire initiated event
    if (!isEmpty(signupCampaign)) {
      signInEvents.trackEasyOnboardingGoogleAuthSignupInitiated();
    }
    signUpHandler(gAuthToken.current, gAuthEmail.current);
  };

  return (
    <>
      <Formik
        initialValues={{ emailOrNumber }}
        onSubmit={handleNextClick}
        validate={validateSignInStep1Form}
        enableReinitialize
      >
        {(formikProps) => {
          return (
            <Size height="100%">
              <form onSubmit={formikProps.handleSubmit}>
                <Screen>
                  <Screen.Content>
                    <Space padding={[5, 0, 3, 0]}>
                      <View>
                        <Heading weight="bold" size="xlarge">
                          Login to Dashboard
                        </Heading>
                      </View>
                    </Space>
                    <Space padding={[1, 0, 1.5, 0]}>
                      <View>
                        <TextInput
                          name="emailOrNumber"
                          width="auto"
                          variant="filled"
                          type={featureFlags.ENABLE_MOBILE_OTP_FLOW ? 'text' : 'email'}
                          autoCapitalize="none"
                          value={formikProps.values.emailOrNumber}
                          label={
                            featureFlags.ENABLE_MOBILE_OTP_FLOW
                              ? 'Email or Mobile Number'
                              : 'Your email'
                          }
                          placeholder={featureFlags.ENABLE_MOBILE_OTP_FLOW ? '' : 'example@xyz.com'}
                          ref={emailOrNumberRef}
                          errorText={
                            otpApiError ||
                            (formikProps.errors.emailOrNumber &&
                            hasKey(formikProps.touched, 'emailOrNumber')
                              ? formikProps.errors.emailOrNumber
                              : undefined)
                          }
                          onChange={(value) => {
                            if (otpApiError) {
                              setOtpApiError(false);
                            }
                            formikProps.setFieldValue('emailOrNumber', value);
                          }}
                          onBlur={(value) => {
                            formikProps.setFieldTouched('emailOrNumber', value);
                          }}
                        />
                      </View>
                    </Space>
                    <Space padding={[2.5, 0]}>
                      <View>
                        <Button
                          type="submit"
                          size="medium"
                          variant="primary"
                          block
                          disabled={!formikProps.isValid || formikProps.isSubmitting}
                        >
                          Next
                        </Button>
                      </View>
                    </Space>
                    <View style={{ display: !showSeparator ? 'none' : '' }}>
                      <Separator />
                    </View>
                    <Space padding={[2.25, 0, 0, 0]}>
                      <OneTapWrapper
                        ref={oneTapWrapperRef}
                        style={{ display: !showOneTap ? 'none' : '' }}
                      >
                        <OneTapView id={ONE_TAP_SELECTOR} />
                      </OneTapWrapper>
                    </Space>
                    <Space padding={[2.5, 0]}>
                      <ButtonView
                        style={{
                          display: !showGauthButton ? 'none' : 'flex',
                          justifyContent: 'center',
                        }}
                        id={G_AUTH_TYPES.button}
                      />
                    </Space>
                  </Screen.Content>
                </Screen>
              </form>
            </Size>
          );
        }}
      </Formik>
      {showCookieModal ? (
        <GoogleAuthCookieModal closeModal={handleCookieModalClose} context={SIGNIN} />
      ) : null}
      {showAccNotFound ? (
        <AccNotFoundModal
          closeModal={handleAccModalClose}
          handleRetry={retryLogin}
          handleCreateAccount={handleCreateAccount}
          email={gAuthEmail.current}
        />
      ) : null}
      {state?.showTncPopup ? (
        <TermsAndConditionModal closeModal={handleTncModalClose} handleAccept={handleAcceptTnC} />
      ) : null}
    </>
  );
};

SingInGoogle.propTypes = {
  authClientId: PropTypes.string,
  isOneTapOn: PropTypes.bool,
  isOneTapScriptFailed: PropTypes.bool,
  isFocusOnField: PropTypes.bool,
  handleNext: PropTypes.func,
  handleEffect: PropTypes.func,
  isGoogleOauthEnabled: PropTypes.bool,
};

SingInGoogle.defaultProps = {
  handleNext: () => {},
  handleEffect: () => {},
  isGoogleOauthEnabled: true,
};

export default SingInGoogle;

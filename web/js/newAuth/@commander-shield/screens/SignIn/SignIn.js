import React, { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import { Formik } from 'formik';
import ReCaptchaV2 from 'react-google-recaptcha';
import ChevronLeft from '@razorpay/blade-old/src/icons/ChevronLeft';
import { useGoogleReCaptcha as useReCaptchaV3 } from 'react-google-recaptcha-v3';
import Text from '@razorpay/blade-old/src/atoms/Text';
import captureException, {
  captureSigninException,
  sentryFlows,
} from '../../shared/captureException';
import Button from '../../shared/Button';
import OTPInput from '../../shared/OTPInput';
import PasswordInput from '../../shared/PasswordInput';
import Separator from '../../shared/Separator';
import {
  EmailNumberChangeView,
  ScreenForm,
  ScreenHeading,
  SpaceView,
  CenteredView,
} from '../../shared/ScreenViews';
import {
  screenMap,
  goToScreen,
  goToDashboard,
  redirectTo,
  SIGNIN,
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  authMethods,
  authModes,
  OTP_FIELD_FORMIK_KEYNAME,
  setMidCookie,
  isJKOmniEnabled,
} from '../screenHelpers';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useLocationQuery from '../../shared/useLocationQuery';
import { validateSignInStep2Form } from '../../utils/validationService';
import { commanderShieldConfig } from '../../config/config';
import { isCaptchaV3Enabled } from '../../utils/captchaService';
import { authApiResponse } from '../../api/apiHelpers';
import accountBlockEvents from '../AccountBlock/accountBlockEvents';
import TermsAndConditionModal from '../../shared/TermsAndConditionModal';
import {
  sendGeoLocationCaptureSuccessEvent,
  sendGeoLocationFailureEvent,
} from '../../js/signInAnalytics';
import { handleErrorCodes, UNRECOGNIZED_ERROR } from './signInHelpers';
import signInEvents from './signInEvents';
import SignInOptions from './SignInOptions';

const __ENV__ = commanderShieldConfig[SHIELD_STAGE];
export const captchaValidationFailedError =
  'Captcha validation Failed, Please refresh page and try again.';
const captchaLowScoreError = 'Captcha score low, Please try again.';
const geoLocationError = 'Location access is required to proceed with login';

/* eslint-disable complexity */
const SignIn = ({
  handleFieldFocus,
  isGoogleOauthEnabled,
  handleTestSubmit,
  skipCaptcha,
  orgName,
}) => {
  const { state, actions } = useUserContext();
  const navigate = useNavigate();
  const snackbar = useSnackbar();
  const locationQuery = useLocationQuery();
  const { executeRecaptcha: executeRecaptchaV3 } = useReCaptchaV3();
  const [otpInputError, setOTPInputError] = React.useState('');
  const [locationError, setLocationError] = React.useState(false);
  const invisibleCaptchaRef = useRef(null);
  // if captcha v3 returns Validation Failed, we set this to true
  const [isCaptchaV3ValidationFailed, setIsCaptchaV3ValidationFailed] = useState(false);
  const signInMethod = state.authMethod ?? authMethods.EMAIL;
  const emailOrNumber =
    signInMethod === authMethods.PHONE_NUMBER ? state.user.mobileNumber : state.user.email;

  const handleLoginNextSteps = (user = {}, email) => {
    try {
      signInEvents.bindSegmentUserIdentity(user);
      signInEvents.trackSignInSuccess({
        email,
        method: signInMethod,
        userId: user.id,
        mid: user.mid,
      });
      actions.showLoader();

      if (user.isPreSignUpComplete) {
        if (signInMethod === authMethods.PHONE_NUMBER || user.isConfirmed) {
          if (state.user.redirectUrl) {
            redirectTo(state.user.redirectUrl);
          } else {
            goToDashboard();
          }
        } else {
          actions.hideLoader();
          goToScreen({ screen: screenMap.verifyEmail, navigate, locationQuery });
        }
      } else {
        actions.hideLoader();
        goToOnboardingScreen({
          user,
          navigate,
          locationQuery,
        });
      }
    } catch (error) {
      captureSigninException(error);
    }
  };

  const proceedWithSignIn = async (cb) => {
    const { loginDetails, user } = state;
    setLoggedInViaInStorage(loginDetails?.user?.loggedInVia, user?.email);
    const response = await actions.getUserDetails();
    // if org is JNK and omni_enabled user is logged in trigger the callback for geolocation access
    if (isJKOmniEnabled(response?.user?.features, orgName)) {
      cb?.(response?.user);
    } else {
      setMidCookie(response?.user?.mid, response?.user?.id);
      handleLoginNextSteps(response.user, user?.email);
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

  const authenticateUser = async (values, cb) => {
    try {
      let loginResponse;
      signInEvents.trackSignInInitiate({ method: signInMethod });
      if (signInMethod === authMethods.PHONE_NUMBER) {
        loginResponse = await actions.loginWithOTP({
          mobileNumber: values.mobileNumber,
          otp: values.otp,
          otpToken: values.otpToken,
          isMobileNumberVerified: state.user.isMobileNumberVerified,
          captcha: values.captcha || 'Faked',
          captchaMode: values.captchaMode,
        });
      } else if (signInMethod === authMethods.EMAIL && state.authMode === authModes.OTP) {
        loginResponse = await actions.loginWithOTP({
          email: values.email,
          otp: values.otp,
          otpToken: values.otpToken,
          isMobileNumberVerified: state.user.isMobileNumberVerified,
          captcha: values.captcha || 'Faked',
          captchaMode: values.captchaMode,
        });
      } else {
        loginResponse = await actions.login({
          email: values.email,
          password: values.password,
          captcha: values.captcha || 'Faked',
          captchaMode: values.captchaMode,
        });
      }
      if (values.captchaMode === 'v3') {
        signInEvents.trackCaptchaSuccess({ captchaMode: values.captchaMode, method: signInMethod });
      }
      if (loginResponse?.user?.show_tnc_popup) {
        actions.showTncPopup();
      } else {
        await proceedWithSignIn(cb);
      }
    } catch (error) {
      captureSigninException(error);
      actions.hideLoader();
      if (values.captchaMode === 'v3') {
        if (error.message === captchaLowScoreError) {
          // When v3 returns with low score, we can't be sure whether the user is bot or human.
          // Thus, we trigger v2 to help with the final differentiation.
          invisibleCaptchaRef.current.reset();
          invisibleCaptchaRef.current.execute();
          return; // we don't want rest of the failure events to fire in this case so we return here
        } else if (error.message === captchaValidationFailedError) {
          signInEvents.trackCaptchaV3Failure({
            email: values.email,
            error: error.message,
            method: signInMethod,
          });
          setIsCaptchaV3ValidationFailed(true);
          // Trigger invisible captcha as a fallback
          invisibleCaptchaRef.current.reset();
          invisibleCaptchaRef.current.execute();
          return;
        } else {
          // When there is error, but it is not the fault of v3 (e.g errors like email already exists, validation failures, etc)
          signInEvents.trackCaptchaSuccess({
            captchaMode: values.captchaMode,
            method: signInMethod,
          });
        }
      }

      invisibleCaptchaRef.current?.reset();
      if (error.message === authApiResponse.accountBlocked) {
        accountBlockEvents.trackInitiated({
          email: values.email,
          method: signInMethod,
          flow: `${signInMethod}_signin`,
        });
      }

      if (error.code === authApiResponse.signinIncorrectOTP) {
        const remainingAttempts = error.internalData?.max_attempts_remaining;
        if (remainingAttempts && remainingAttempts <= 3) {
          const attemptIsPlural = remainingAttempts > 1 ? 'attempts are' : 'attempt is';
          setOTPInputError(
            `Last ${remainingAttempts} ${attemptIsPlural} remaining. If you don't get it right, your account will get locked.`,
          );
        }
      }

      if (
        handleErrorCodes({ error, navigate, locationQuery, updateUser: actions.updateUser }) ===
        UNRECOGNIZED_ERROR
      ) {
        if (signInMethod === authMethods.PHONE_NUMBER) {
          captureException(error, {
            flow: sentryFlows.SIGNUP_WITH_MOBILE_OTP,
          });
        } else {
          captureException(error, {
            flow: sentryFlows.SIGNUP_WITH_EMAIL_PASSWORD,
            email: values.email,
          });
        }
        signInEvents.trackSignInFailure({
          email: values.email,
          error: error.message,
          actualError: error.actualError,
          method: signInMethod,
        });
        snackbar.error(error?.message);
      }
    }
  };

  const handleGeoLocationCapture = async ({ user, email }) => {
    if (navigator.geolocation) {
      actions.showLoader();
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          sendGeoLocationCaptureSuccessEvent('geo_location_capture', {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            user_id: user.id,
            mid: user.mid,
          });
          actions.hideLoader();
          handleLoginNextSteps(user, email);
        },
        (error) => {
          setLocationError(true);
          sendGeoLocationFailureEvent('geo_location_capture', {
            error: error.message,
            user_id: user.id,
            mid: user.mid,
          });
          actions.hideLoader();
          // Since cookies are already set by server hence on refresh user will remain logged in only
          // calling the logout api to make sure user stays logged out if geolocation permission is not given
          actions.logout();
        },
      );
    } else {
      //Temp: Dont block login in case of geolocation failure
      // To track the scenarios
      sendGeoLocationFailureEvent('geo_location_capture', {
        error: 'Geolocation is not supported by this browser',
      });
      handleLoginNextSteps(user, email);
    }
  };

  const handleOnClickLogin = async (values, formikActions) => {
    try {
      actions.showLoader();
      formikActions.setSubmitting(false);
      if (isCaptchaV3Enabled() || skipCaptcha) {
        let token = '';
        let captchaModeSignIn = 'v3';

        // if "skipCaptcha" is true we skip V3 captcha and set captchaMode as blank value
        // this is set in QA env for automated testing in various QA envs.
        if (skipCaptcha) {
          captchaModeSignIn = ''; // by default api sends "invisible" as captcha mode if its blank
        } else {
          try {
            // trigger v3 captcha
            token = await executeRecaptchaV3('signin');
          } catch (e) {
            // Probably "Uncaught promise null" issue in recaptcha execution
            // so do nothing and hide the loader
            // so that user can get a chance to retry.
          }

          if (!token) {
            // if no token, trigger v2 invisible
            invisibleCaptchaRef.current.reset();
            invisibleCaptchaRef.current.execute();
            handleTestSubmit();
            return;
          }
        }

        let loggedInUser;

        if (signInMethod === authMethods.PHONE_NUMBER) {
          // phone+otp signin
          await authenticateUser(
            {
              mobileNumber: values.mobileNumber,
              otp: values.otp,
              otpToken: state.user.otpToken,
              captcha: token,
              captchaMode: captchaModeSignIn,
            },
            (user) => {
              // once user has entered otp, we know the user details returning them here in callback to execute the geolocation capture
              loggedInUser = user;
            },
          );

          // Note: Since geolocation capture can only be done on user event hence we are calling it here and accessing the user details received in callback
          if (isJKOmniEnabled(loggedInUser?.features, orgName)) {
            await handleGeoLocationCapture({
              user: loggedInUser,
              email: state?.user?.email,
            });
          }
        } else if (signInMethod === authMethods.EMAIL && state.authMode === authModes.OTP) {
          // email+otp signin
          authenticateUser({
            email: values.email,
            otp: values.otp,
            otpToken: state.user.otpToken,
            captcha: token,
            captchaMode: captchaModeSignIn,
          });
        } else {
          // email+password signin
          authenticateUser({
            email: values.email,
            password: values.password,
            captcha: token,
            captchaMode: captchaModeSignIn,
          });
        }
      } else {
        // v2 invisible flow
        invisibleCaptchaRef.current.reset();
        invisibleCaptchaRef.current.execute();
      }
      handleTestSubmit();
    } catch (error) {
      captureSigninException(error);
    }
  };

  const handleGotoSignInGauth = (isFieldFocus) => {
    handleFieldFocus(isFieldFocus);
    actions.resetAuthMethodAndAuthMode();
    goToScreen({ screen: screenMap.signIn, navigate, locationQuery });
  };

  const formikInitialValues = {
    email: state.user.email,
    mobileNumber: state.user.mobileNumber,
    password: state.user.password,
  };

  formikInitialValues[OTP_FIELD_FORMIK_KEYNAME] = undefined;

  const handleForgotPassword = () => {
    signInEvents.trackNonSignInActionsInitiate({ action: 'click Forgot Password' });
    goToScreen({ screen: screenMap.forgotPassword, navigate, locationQuery });
  };

  const handleInvisibleCaptchaChange = async (formikProps, value) => {
    try {
      signInEvents.trackCaptchaSuccess({
        isCaptchaV3ValidationFailed,
        method: signInMethod,
        captchaMode: 'v2',
      });
      let loggedInUser;
      if (signInMethod === authMethods.PHONE_NUMBER) {
        // phone+otp signin
        await authenticateUser({
          mobileNumber: formikProps.values.mobileNumber,
          otp: formikProps.values.otp,
          otpToken: state.user.otpToken,
          captcha: value,
        },  (user) => {
          // once user has entered otp, we know the user details returning them here in callback to execute the geolocation capture
          loggedInUser = user;
        });

        // Note: Since geolocation capture can only be done on user event hence we are calling it here and accessing the user details received in callback
        if (isJKOmniEnabled(loggedInUser?.features, orgName)) {
          await handleGeoLocationCapture({
            user: loggedInUser,
            email: state?.user?.email,
          });
        }
      } else {
        // email+password signin
        authenticateUser({
          email: formikProps.values.email,
          password: formikProps.values.password,
          captcha: value,
        });
      }
    } catch (error) {
      captureSigninException(error);
    }
  };

  const hideLoader = () => {
    actions.hideLoader();
  };

  const handleCaptchaError = (formikProps) => {
    hideLoader();
    signInEvents.trackCaptchaFailure({ email: formikProps.values.email, method: signInMethod });
  };

  const handleChangeOnClick = () => {
    signInEvents.trackChangeSignInMethodInitiate({ mode: signInMethod });
    handleGotoSignInGauth(true);
  };

  const handleUseAnotherOptionClick = () => {
    signInEvents.trackSignInWithAnotherOptionInitiate({ method: signInMethod });
    handleGotoSignInGauth(false);
  };

  return (
    <>
      <Formik
        initialValues={formikInitialValues}
        onSubmit={(values, formikActions) => {
          handleOnClickLogin(values, formikActions);
        }}
        validate={(values) => validateSignInStep2Form(values, { authMode: state.authMode })}
        enableReinitialize
      >
        {(formikProps) => {
          return (
            <ScreenForm>
              <ScreenHeading>Login to Dashboard</ScreenHeading>
              <EmailNumberChangeView onChangeClick={handleChangeOnClick}>
                {emailOrNumber}
              </EmailNumberChangeView>
              <SpaceView padding={[signInMethod === authMethods.PHONE_NUMBER ? 0 : 2, 0, 0, 0]}>
                {state.authMode === authModes.OTP ? (
                  <OTPInput
                    context={SIGNIN}
                    otpError={otpInputError ?? ''}
                    setOTPError={setOTPInputError}
                  />
                ) : (
                  <PasswordInput handleForgotPassword={handleForgotPassword} />
                )}
              </SpaceView>
              {locationError ? (
                <Text size="xsmall" color="negative.900">
                  {geoLocationError}
                </Text>
              ) : null}
              <SpaceView padding={[3, 0, 2.5, 0]}>
                <Button
                  type="submit"
                  size="medium"
                  variant="primary"
                  block
                  disabled={!formikProps.isValid || formikProps.isSubmitting || locationError}
                >
                  Login
                </Button>
              </SpaceView>
              <SignInOptions />
              {isGoogleOauthEnabled ? (
                <>
                  <Separator color="shade.960" size="small" />
                  <CenteredView margin={[1.25, 0, 0, 0]} padding={[0, 0, 0, 0.5]}>
                    <Button
                      variant="tertiary"
                      size="medium"
                      icon={ChevronLeft}
                      onClick={handleUseAnotherOptionClick}
                    >
                      Use Another Login Option
                    </Button>
                  </CenteredView>
                </>
              ) : null}
              <ReCaptchaV2
                ref={invisibleCaptchaRef}
                size="invisible"
                sitekey={__ENV__.invisibleCaptcha.key}
                onChange={(value) => handleInvisibleCaptchaChange(formikProps, value)}
                onErrored={() => handleCaptchaError(formikProps)}
                onExpired={hideLoader}
              />
            </ScreenForm>
          );
        }}
      </Formik>
      {state?.showTncPopup ? (
        <TermsAndConditionModal closeModal={handleTncModalClose} handleAccept={handleAcceptTnC} />
      ) : null}
    </>
  );
};

SignIn.propTypes = {
  handleFieldFocus: PropTypes.func,
  handleTestSubmit: PropTypes.func,
  isGoogleOauthEnabled: PropTypes.bool,
  skipCaptcha: PropTypes.bool,
};

SignIn.defaultProps = {
  handleTestSubmit: () => {},
  skipCaptcha: false,
};

export default SignIn;

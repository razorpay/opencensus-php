import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import PropTypes from 'prop-types';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import hasKey from '@razorpay/universe-utils/hasKey';
import * as yup from 'yup';
import { Formik } from 'formik';
import { captureSignupException } from '../../shared/captureException';
import TextInput from '../../shared/TextInput';
import Button from '../../shared/Button';
import Screen from '../../shared/Screen/';
import signUpEvents from '../SignUp/signUpEvents';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import {
  goToDashboard,
  goToScreen,
  screenMap,
  SIGNUP,
  authModes,
  setSignupExpData,
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  authMethods,
} from '../screenHelpers';
import useLocationQuery from '../../shared/useLocationQuery';
import GoogleAuthCookieModal from '../../shared/GoogleAuth/GoogleAuthCookieModal';
import {
  resizeOneTapIframe,
  initOneTap,
  loadingGauthScript,
} from '../../shared/GoogleAuth/GoogleAuthHelpers';
import {
  ButtonView,
  OneTapView,
  OneTapWrapper,
  SeparatorView,
  CenteredText,
  SeparatorBlock,
} from '../../shared/GoogleAuth/GoogleAuthViews';
import {
  G_AUTH_TYPES,
  ONE_TAP_SELECTOR,
  SCRIPT_FAIL_ERROR,
  EMAIL_ALREADY_TAKEN,
} from '../../shared/GoogleAuth/GoogleAuthConstants';
import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../../shared/Experiments/Experiments';
import getExpStatus from '../../utils/getExperimentStatus';
import { EMAIL_VERIFY_REGEX, MOBILE_NUMBER_VERIFY_REGEX } from '../../utils/regex';
import {
  getSignMethod,
  EMAIL_OR_NUMBER_VALIDATION_ERROR_MESSAGE,
} from '../../utils/validationService';
import { handleEventForInputError } from '../../js/signUpAnalytics';

const SignUpGoogle = ({ handleNext, isMobileNumberSignupEnabled, ...props }) => {
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();
  const [isPageLoadEventFired, setPageLoadEvent] = useState(false);
  const [showModal, setShowModal] = useState(false);
  const [isOneTapEnabled, setIsOneTapEnabled] = useState(false);
  const [isInlineOneTap, setIsInlineOneTap] = useState(true);
  const [isGauthTypeDecided, setIsGauthTypeDecided] = useState(false);
  const oneTapWrapperRef = useRef(null);
  const orSeparatorRef = useRef(null);
  const emailOrNumberRef = useRef(null);
  const emailRef = useRef(null);
  const { authClientId, isOneTapOn, isOneTapScriptFailed, isFocusOnField } = props;
  let showOrSeparator = !isOneTapOn;
  let showGauthButton = !isOneTapOn;
  if (isOneTapOn && isGauthTypeDecided) {
    showOrSeparator = isInlineOneTap || !isOneTapEnabled;
    showGauthButton = !isOneTapEnabled;
  }

  if (!isPageLoadEventFired && window.rzpQ) {
    signUpEvents.trackPageLoad(state.user);
    setPageLoadEvent(true);
  }

  const loginHandler = async (email, tokenId) => {
    try {
      let response, loginResponse;
      try {
        loginResponse = await actions.loginWithGoogle(email, tokenId, SIGNUP);
        response = await actions.getUserDetails();
        actions.showLoader();
      } catch (error) {
        actions.hideLoader();
        // if login api fails show G_AUTH_TYPES.button as oneTap disappears after error
        setIsOneTapEnabled(false);
        snackbar.error(error?.message);
        return;
      }

      setLoggedInViaInStorage(loginResponse.user.loggedInVia, email);

      signUpEvents.bindSegmentUserIdentity(response.user, locationQuery);
      signUpEvents.trackLoginSuccess(state.user, {
        mid: response.user.mid,
        id: response.user.id,
        email: response.user.email,
      });

      const nextScreen = getExpStatus(response.user, REMOVE_PRESIGNUP_FUNCTIONALITY)
        ? screenMap.contactDetails
        : screenMap.businessType;

      if (response.user.isConfirmed && response.user.isPreSignUpComplete) {
        setSignupExpData();
        goToDashboard(state.signUpSource);
      } else {
        actions.setAuthMethodAndAuthMode({
          authMethod: authMethods.GAUTH,
          authMode: authModes.GAUTH,
        });
        actions.hideLoader();
        goToScreen({ screen: nextScreen, navigate, locationQuery });
      }
    } catch (loginHandlerError) {
      captureSignupException(loginHandlerError);
    }
  };

  const signupHandler = async (tokenId, email, gAuthType) => {
    try {
      signUpEvents.trackSignUpInitiate({ email }, authMethods.GAUTH, gAuthType);
      let response, signupResponse;
      try {
        signupResponse = await actions.registerUserWithGoogle(email, tokenId);
        response = await actions.getUserDetails();
        actions.showLoader();
      } catch (error) {
        actions.hideLoader();

        signUpEvents.trackSignUpError(
          {
            error: error?.message,
            email,
            statusCode: error?.statusCode,
            actualError: error?.actualError,
          },
          authMethods.GAUTH,
          gAuthType,
        );

        if (error.message.includes(EMAIL_ALREADY_TAKEN)) {
          loginHandler(email, tokenId);
        } else {
          // In case of onetap, if signup api fails show G_AUTH_TYPES.button as fallback
          setIsOneTapEnabled(false);
          snackbar.error(error?.message);
        }
        return;
      }

      setLoggedInViaInStorage(signupResponse.user.loggedInVia, email);

      signUpEvents.bindSegmentUserIdentity(response.user, locationQuery);
      signUpEvents.trackSignUpSuccess(
        response.user,
        authMethods.GAUTH,
        state.user.partnerIntent,
        gAuthType,
      );

      if (response.user.isConfirmed && response.user.isPreSignUpComplete) {
        setSignupExpData();
        goToDashboard(state.signUpSource);
      } else {
        actions.setAuthMethodAndAuthMode({
          authMethod: authMethods.GAUTH,
          authMode: authModes.GAUTH,
        });
        actions.hideLoader();
        goToOnboardingScreen({ user: response.user, navigate, locationQuery });
      }
    } catch (signupHandlerError) {
      captureSignupException(signupHandlerError);
    }
  };

  const handleOneTapNotify = (notification) => {
    try {
      const notifyType = notification.getMomentType();
      if (notifyType === 'display') {
        if (notification.isDisplayed()) {
          setIsOneTapEnabled(true);
          signUpEvents.trackGoogleAuthVariantDecide(state.user, G_AUTH_TYPES.oneTap);

          const oneTapIframe = oneTapWrapperRef.current.querySelector(`iframe`);
          if (oneTapIframe) {
            resizeOneTapIframe(oneTapIframe, oneTapWrapperRef);
          } else {
            setIsInlineOneTap(false);
          }
        } else {
          setIsOneTapEnabled(false);
          signUpEvents.trackGoogleAuthVariantDecide(
            state.user,
            G_AUTH_TYPES.button,
            notification.getNotDisplayedReason(),
          );
        }
      } else if (notifyType === 'skipped') {
        setIsOneTapEnabled(false);
        if (
          notification.getSkippedReason() === 'tap_outside' ||
          notification.getSkippedReason() === 'user_cancel'
        ) {
          signUpEvents.trackOneTapClose(state.user);
        } else {
          signUpEvents.trackGoogleAuthVariantDecide(
            state.user,
            G_AUTH_TYPES.button,
            notification.getNotDisplayedReason(),
          );
        }
      } else if (notifyType === 'dismissed') {
        signUpEvents.trackGoogleAuthOneTapDismissed(state.user, notification.getDismissedReason());
      }
      setIsGauthTypeDecided(true);
    } catch (error) {
      captureSignupException(error);
    }
  };

  const gAuthCallback = (response) => {
    try {
      const tokenId = response.credential;
      /**
       * Records the method of authentication by the user. More info - https://developers.google.com/identity/gsi/web/reference/js-reference#select_by
       * @type {string}
       */
      const method = response.select_by;

      if (tokenId) {
        if (orSeparatorRef?.current) {
          orSeparatorRef.current.style.display = 'none';
        }
        try {
          // tokenId is a JWT(https://developers.google.com/identity/one-tap/web/reference/js-reference)
          const email = JSON.parse(atob(tokenId.split('.')[1])).email;

          signUpEvents.trackGoogleAuthSuccess({ email, coupon: state.user.coupon.code }, method);
          signupHandler(tokenId, email, method);
        } catch (e) {
          signUpEvents.trackSignUpError(
            { error: 'Error in email parsing', email: '' },
            'google_oauth',
            method,
          );
          setIsOneTapEnabled(false);
        }
      } else {
        setIsOneTapEnabled(false);
        signUpEvents.trackSignUpError(
          { error: 'Auth token not found', email: '' },
          'google_oauth',
          method,
        );
      }
    } catch (error) {
      captureSignupException(error);
    }
  };

  const initializeGoogleAuth = () => {
    loadingGauthScript().then((response) => {
      if (response) {
        const googleAuthScript = response;

        googleAuthScript.accounts.id.initialize({
          client_id: authClientId,
          cancel_on_tap_outside: false,
          context: 'signup',
          callback: gAuthCallback,
          prompt_parent_id: ONE_TAP_SELECTOR,
        });

        // Google button render fallback
        googleAuthScript.accounts.id.renderButton(document.getElementById(G_AUTH_TYPES.button), {
          theme: 'filled_blue',
          size: 'large',
        });

        // init onetap auth
        if (isOneTapOn) {
          initOneTap(SIGNUP, authClientId, gAuthCallback, handleOneTapNotify);
        }
      }
    });
  };

  // focus on email input
  useEffect(() => {
    if (isFocusOnField) {
      if (isMobileNumberSignupEnabled) {
        emailOrNumberRef.current.focus();
      } else {
        emailRef.current.focus();
      }
    }
  }, [isFocusOnField, isMobileNumberSignupEnabled]);

  // must be called only once as it initializes gauth scripts
  useEffect(() => {
    initializeGoogleAuth();
  }, []);

  useEffect(() => {
    if (isOneTapOn && isGauthTypeDecided && !isOneTapEnabled) {
      oneTapWrapperRef.current.style.display = 'none';
      orSeparatorRef.current.style.display = 'block';
    }
  }, [isOneTapEnabled, isGauthTypeDecided, isOneTapOn]);

  useEffect(() => {
    if (isOneTapScriptFailed) {
      signUpEvents.trackGoogleAuthVariantDecide(state.user, G_AUTH_TYPES.button, SCRIPT_FAIL_ERROR);
      setIsOneTapEnabled(false);
      setIsGauthTypeDecided(true);
    }
  }, [isOneTapScriptFailed]);

  const handleSignUpClick = async (values) => {
    try {
      // only used in testing.
      handleNext();

      const signInMethod = getSignMethod(values.emailOrNumber);
      const isMobileSignInMethod = signInMethod === authMethods.PHONE_NUMBER;
      const isEmailSignInMethod = signInMethod === authMethods.EMAIL;

      // mobile otp signup & email otp signup flow
      if (isMobileSignInMethod || state.isEmailOtpSignupEnabled) {
        try {
          let contact, email;

          if (isMobileSignInMethod) {
            // mobile number signup flow
            contact = values.emailOrNumber; // There is one input field taking either email/mobile number
            email = undefined; // The email in state should be set as undefined
          } else if (isEmailSignInMethod) {
            // mobile number signup and email otp true
            email = values.emailOrNumber;
            contact = undefined; // The user can change the method type, so in state we should make the other method as undefined
          } else {
            email = values.email; // for email otp flow only (without mobile number signup)
          }

          signUpEvents.trackNativeAuthInitiate(state.user, { method: authMethods.PHONE_NUMBER });
          const response = await actions.sendSignUpOTP({ contact, email });
          actions.hideLoader();
          actions.updateUser({
            email,
            contact,
            otpToken: response.user.token,
          });
          actions.setAuthMethodAndAuthMode({
            authMode: authModes.OTP,
            authMethod: isEmailSignInMethod ? authMethods.EMAIL : authMethods.PHONE_NUMBER,
          });
        } catch (error) {
          snackbar.error(error?.message);
          captureSignupException(error);
        }
      } else {
        // email password signup
        const email = values.emailOrNumber || values.email;
        actions.updateUser({
          email,
          otpToken: '',
          contact: '',
        });
        actions.setAuthMethodAndAuthMode({
          authMode: authModes.PASSWORD,
          authMethod: isEmailSignInMethod ? authMethods.EMAIL : authMethods.PHONE_NUMBER,
        });

        signUpEvents.trackNativeAuthInitiate(state.user, { email });

        if (window.google) {
          window.google.accounts.id.cancel(); // cancel ongoing google onetap signup
        }
      }
    } catch (error) {
      captureSignupException(error);
    }
  };

  const handleModalClose = () => {
    setShowModal(false);
  };

  const getEmailOrNumberInitialVal = () => {
    let emailOrNumber = '';
    if (isMobileNumberSignupEnabled) {
      if (state.user.email) {
        emailOrNumber = state.user.email;
      } else {
        emailOrNumber = state.user.contact;
      }
    }
    return emailOrNumber;
  };

  // emailOrNumber field is only present in mobile number + otp signup flow
  const formikInitialValues = {
    email: state.user.email,
    emailOrNumber: getEmailOrNumberInitialVal(),
  };

  const validationSchema = () => {
    if (isMobileNumberSignupEnabled) {
      return yup.object().shape({
        emailOrNumber: yup
          .string()
          .test('emailOrNumber', EMAIL_OR_NUMBER_VALIDATION_ERROR_MESSAGE, (value) => {
            const isValidEmail = EMAIL_VERIFY_REGEX.test(value);
            const isValidMobileNum = MOBILE_NUMBER_VERIFY_REGEX.test(value);
            return isValidMobileNum || isValidEmail;
          })
          .required(EMAIL_OR_NUMBER_VALIDATION_ERROR_MESSAGE),
      });
    } else {
      return yup.object().shape({
        email: yup
          .string()
          .email('Please enter a valid email id.')
          .required('Please enter a valid email id.'),
      });
    }
  };

  return (
    <>
      <Formik
        initialValues={formikInitialValues}
        onSubmit={handleSignUpClick}
        enableReinitialize
        validationSchema={validationSchema}
      >
        {(formikProps) => {
          return (
            <Size height="100%">
              <form onSubmit={formikProps.handleSubmit}>
                <Screen>
                  <Screen.Content>
                    <Space padding={[4.75, 0, 4, 0]}>
                      <View>
                        <Heading weight="bold" size="xlarge">
                          {state.signUpHeading}
                        </Heading>
                        <Space margin={[1, 0, 0, 0]}>
                          <Text size="medium" color="shade.960">
                            Sign up to create an account with us
                          </Text>
                        </Space>
                      </View>
                    </Space>
                    <Space padding={[1, 0, 1.5, 0]}>
                      <View>
                        {isMobileNumberSignupEnabled ? (
                          <TextInput
                            name="emailOrNumber"
                            width="auto"
                            placeholder="Enter email address or mobile number"
                            autoCapitalize="none"
                            value={formikProps.values.email || formikProps.values.emailOrNumber}
                            label="Email or Mobile number"
                            ref={emailOrNumberRef}
                            errorText={
                              formikProps.errors.emailOrNumber &&
                              hasKey(formikProps.touched, 'emailOrNumber')
                                ? formikProps.errors.emailOrNumber
                                : undefined
                            }
                            variant="filled"
                            onChange={(value) => {
                              formikProps.setFieldValue('emailOrNumber', value);
                            }}
                            onBlur={(value) => {
                              formikProps.setFieldTouched('emailOrNumber', value);
                              handleEventForInputError(formikProps, 'emailOrNumber', signUpEvents);
                            }}
                          />
                        ) : (
                          <TextInput
                            name="email"
                            width="auto"
                            autoCapitalize="none"
                            value={formikProps.values.email}
                            ref={emailRef}
                            label="Email"
                            placeholder="example@xyz.com"
                            errorText={
                              formikProps.errors.email && hasKey(formikProps.touched, 'email')
                                ? formikProps.errors.email
                                : undefined
                            }
                            variant="filled"
                            type="email"
                            onChange={(value) => {
                              formikProps.setFieldValue('email', value);
                            }}
                            onBlur={(value) => {
                              formikProps.setFieldTouched('email', value);
                              signUpEvents.trackEmailInput();
                              handleEventForInputError(formikProps, 'email', signUpEvents);
                            }}
                          />
                        )}
                      </View>
                    </Space>
                    <Space padding={[2.5, 0]}>
                      <View>
                        <Button type="submit" size="medium" variant="primary" block>
                          Next
                        </Button>
                      </View>
                    </Space>
                    {showOrSeparator ? (
                      <Space margin={[1.75, 0]}>
                        <SeparatorView ref={orSeparatorRef}>
                          <CenteredText>
                            <Space padding={[0, 1.75]}>
                              <Text>or</Text>
                            </Space>
                          </CenteredText>
                          <SeparatorBlock />
                        </SeparatorView>
                      </Space>
                    ) : null}
                    {isOneTapOn ? (
                      <Space padding={[2.5, 0, 0, 0]}>
                        <OneTapWrapper ref={oneTapWrapperRef}>
                          <OneTapView id={ONE_TAP_SELECTOR} />
                        </OneTapWrapper>
                      </Space>
                    ) : null}
                    {/* Not removing button below from dom in case of onetap so that initializing can take place */}
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
      {showModal ? <GoogleAuthCookieModal closeModal={handleModalClose} context={SIGNUP} /> : null}
    </>
  );
};

SignUpGoogle.propTypes = {
  authClientId: PropTypes.string,
  isOneTapOn: PropTypes.bool,
  isOneTapScriptFailed: PropTypes.bool,
  isFocusOnField: PropTypes.bool,
  handleNext: PropTypes.func,
  isMobileNumberSignupEnabled: PropTypes.bool,
};

SignUpGoogle.defaultProps = {
  handleNext: () => {},
};

export default SignUpGoogle;

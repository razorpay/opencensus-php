import React, { useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import * as yup from 'yup';
import { Formik } from 'formik';
import PropTypes from 'prop-types';
import ReCaptchaV2 from 'react-google-recaptcha';
import { useGoogleReCaptcha as useReCaptchaV3 } from 'react-google-recaptcha-v3';
import ChevronLeft from '@razorpay/blade-old/src/icons/ChevronLeft';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import { commanderShieldConfig } from '../../config/config';
import { captureSignupException } from '../../shared/captureException';
import Link from '../../shared/Link';
import Button from '../../shared/Button';
import Screen from '../../shared/Screen/';
import useLocationQuery from '../../shared/useLocationQuery';
import {
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  RECAPTCHA_NULL_ERROR,
  authMethods,
  authModes,
  OTP_FIELD_FORMIK_KEYNAME,
  setSignupExpData,
  goToDashboard,
  goToScreen,
  screenMap,
} from '../screenHelpers';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useUserContext from '../../user/useUserContext';
import setCookie from '../../utils/setCookie';
import { isCaptchaV3Enabled } from '../../utils/captchaService';
import hideLoaderOnCaptchaBlur from '../../utils/hideLoaderOnCaptchaBlur';
import { CenteredView } from '../../shared/ScreenViews';
import Separator from '../../shared/Separator';
import { PASSWORD_REGEX } from '../../utils/regex';
import { PASSWORD_ERROR_TEXT } from '../../shared/constants';
import { authApiResponse } from '../../api/apiHelpers';
import signUpEvents from './signUpEvents';
import SignUpPassword from './SignUpPassword';
import SignUpOtp from './SignUpOtp';

const __ENV__ = commanderShieldConfig[SHIELD_STAGE];

const SignUp = ({
  handleFieldFocus,
  handleSubmit,
  showPasswordRules,
  skipCaptcha,
  autoReadOtpSignup,
}) => {
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();
  const { state, actions } = useUserContext();
  const { executeRecaptcha: executeRecaptchaV3 } = useReCaptchaV3();
  const snackbar = useSnackbar();

  // if captcha v3 returns Validation Failed, we set this to true
  const [isCaptchaV3ValidationFailed, setIsCaptchaV3ValidationFailed] = useState(false);
  const invisibleCaptchaRef = useRef(null);

  const isPasswordFlow = () => state.authMode === authModes.PASSWORD;
  const isMerchantInvite = !!state.user.merchantInvitationCode;
  const isInvite = !!state.user.invitationCode;
  const signUpMethod = isPasswordFlow() ? authMethods.EMAIL : authMethods.PHONE_NUMBER;

  // eslint-disable-next-line complexity
  const registerUser = async (values, formikProps = null) => {
    const apiStartTimeInMs = new Date().getTime();
    try {
      let signupResponse;
      if (isPasswordFlow()) {
        signupResponse = await actions.registerUser({
          email: values.email,
          password: values.password,
          captcha: values.captcha || 'Faked',
          captchaMode: values.captchaMode,
          partnerIntent: state.user.partnerIntent,
          isMerchantInvite: state.user.merchantInvitationCode,
          invitation: state.user.invitationCode,
        });
      } else {
        signupResponse = await actions.verifySignUpOTP({
          contact: state.user.contact,
          captcha: values.captcha || 'Faked',
          token: state.user.otpToken,
          otp: values.otp,
          captchaMode: values.captchaMode,
          partnerIntent: state.user.partnerIntent,
          email: values.email,
        });
        actions.sendDeviceDetails(true);
      }

      setLoggedInViaInStorage(signupResponse.user.loggedInVia, values.email);
      const response = await actions.getUserDetails();
      setCookie('midExists', !!response.user.mid);
      signUpEvents.bindSegmentUserIdentity(response.user, locationQuery);
      signUpEvents.trackSignUpSuccess(
        { contact: state.user.contact, ...response.user },
        signUpMethod,
        state.user.partnerIntent,
      );
      if (values.captchaMode === 'v3') {
        signUpEvents.trackCaptchaSuccess({ email: values.email, captchaMode: 'v3' }, signUpMethod);
      }

      // useful in case of invitation flow where user is by default confirmed
      if (response.user.isConfirmed && response.user.isPreSignUpComplete) {
        setSignupExpData();
        goToDashboard(state.signUpSource);
      } else {
        goToOnboardingScreen({
          user: response.user,
          navigate,
          locationQuery,
        });
      }
    } catch (error) {
      if (values.captchaMode === 'v3') {
        if (error.message === 'Low captcha score') {
          // When v3 returns with low score, we can't be sure whether the user is bot or human.
          // Thus, we trigger v2 to help with the final differentiation.
          invisibleCaptchaRef.current.reset();
          invisibleCaptchaRef.current.execute();
          return; // we don't want rest of the failure events to fire in this case so we return here
        } else if (error.message === 'Captcha Failed') {
          signUpEvents.trackCaptchaFailure({
            email: values.email,
            error: 'Captcha Failed',
            captchaMode: 'v3',
            method: signUpMethod,
          });
          setIsCaptchaV3ValidationFailed(true);
          // Trigger invisible captcha as a fallback
          invisibleCaptchaRef.current.reset();
          invisibleCaptchaRef.current.execute();
          return;
        } else {
          // When there is error, but it is not the fault of v3 (e.g errors like email already exists, validation failures, etc)
          signUpEvents.trackCaptchaSuccess(
            { email: values.email, captchaMode: 'v3' },
            signUpMethod,
          );
        }
      }
      if (error.code === authApiResponse.twoFactorSetupRequired) {
        goToScreen({ screen: screenMap.setupTwoFactorAuth, navigate, locationQuery });
      } else {
        snackbar.error(error?.message);
      }

      if (formikProps) {
        formikProps.setSubmitting(false);
      }
      if (invisibleCaptchaRef.current) {
        invisibleCaptchaRef.current.reset();
      }

      // @TODO: Remove after debugging
      /*
      This is added to debug the reason for `Something went wrong. Please try again` error
      This is a default error and we want to know how much time does it take for the error
      to be thrown to the user
      Right now sending this field to datalake events for all the errors.
      We will filter out in looker dashboard
       */
      const apiResponseTimeInSec = (new Date().getTime() - apiStartTimeInMs) / 1000;

      signUpEvents.trackSignUpError(
        {
          error: error?.message,
          email: values?.email,
          apiResponseTime: apiResponseTimeInSec,
          statusCode: error?.statusCode,
          actualError: error?.actualError,
        },
        signUpMethod,
      );
    }
  };

  const handleNext = async (values, formikActions) => {
    try {
      signUpEvents.trackSignUpInitiate(
        {
          email: values.email,
          partnerIntent: state.user.partnerIntent,
          coupon: state.user.coupon.code,
        },
        signUpMethod,
      );

      actions.showLoader();
      hideLoaderOnCaptchaBlur(actions.hideLoader);

      formikActions.setSubmitting(false);

      if (isCaptchaV3Enabled() || skipCaptcha) {
        let token = '';
        let captchaModeSignUp = 'v3';

        // if "skipCaptcha" is true we skip V3 captcha and set captchaMode as blank value
        // this is set in QA env for automated testing in various QA envs.
        if (skipCaptcha) {
          captchaModeSignUp = ''; // by default api sends "invisible" as captcha mode if its blank
        } else {
          try {
            token = await executeRecaptchaV3('signup');
          } catch (e) {
            // Probably "Uncaught promise null" issue in recaptcha execution
            // so do nothing and hide the loader
            // so that user can get a chance to retry.
            actions.hideLoader();
            snackbar.error(RECAPTCHA_NULL_ERROR);
            return;
          }
        }

        registerUser({
          ...values,
          captcha: token,
          captchaMode: captchaModeSignUp,
        }).catch((e) => {
          captureSignupException(e);
        });
      } else {
        // A/B Experiment - old v2 flow
        invisibleCaptchaRef.current.reset();
        invisibleCaptchaRef.current.execute();
      }
      handleSubmit();
    } catch (error) {
      captureSignupException(error);
    }
  };

  const handleInvisibleCaptchaChange = (formikProps, value) => {
    formikProps.values.captcha = value;
    signUpEvents.trackCaptchaSuccess(
      {
        email: formikProps.values.email,
        captchaMode: 'v2',
        calledOnV3Failure: isCaptchaV3ValidationFailed,
      },
      signUpMethod,
    );
    registerUser(formikProps.values, formikProps).catch((e) => {
      captureSignupException(e);
    });
  };

  const handlePrivacyClick = (formikProps) => {
    signUpEvents.trackSecondaryLinkClick({
      user: { email: formikProps.values.email },
      source: 'Privacy Policy',
    });
  };

  const handleTncClick = (formikProps) => {
    signUpEvents.trackSecondaryLinkClick({
      user: { email: formikProps.values.email },
      source: 'Terms of Use',
    });
  };

  const handleSignUpOptionsClick = (formikProps, isFieldFocus = false) => {
    if (isFieldFocus && signUpMethod === authMethods.PHONE_NUMBER) {
      signUpEvents.trackSignUpMethodChangeInitiate(authMethods.PHONE_NUMBER);
    } else {
      signUpEvents.trackSecondaryLinkClick({
        user: { email: formikProps.values.email },
        source: 'Signup Options',
      });
    }

    const email = formikProps.values.email;
    actions.updateUser({
      email,
    });

    handleFieldFocus(isFieldFocus);
    actions.resetAuthMethodAndAuthMode();
  };

  const hideLoader = () => {
    actions.hideLoader();
  };

  const validationSchema = () => {
    if (isPasswordFlow()) {
      return yup.object().shape({
        email: yup
          .string()
          .email('Please enter a valid email id.')
          .required('Please enter a valid email id.'),
        password: yup
          .string()
          .matches(PASSWORD_REGEX, PASSWORD_ERROR_TEXT)
          .required(PASSWORD_ERROR_TEXT),
      });
    } else {
      return yup.object().shape({
        otp: yup
          .string()
          .length(6, 'Please enter a valid 6-digit verification code.')
          .required('Please enter a valid 6-digit verification code.'),
      });
    }
  };

  const handleOTPSignUp = async (formikProps) => {
    try {
      const email = formikProps.values.email;
      const response = await actions.sendSignUpOTP({ email });
      actions.updateState({
        authMode: authModes.OTP,
        user: {
          ...state.user,
          otpToken: response.user.token,
        },
      });
      setLoggedInViaInStorage(response.user.loggedInVia, email);
    } catch (error) {
      snackbar.error(error?.message);
      captureSignupException(error);
    }
  };

  const handlePasswordSignUp = () => {
    actions.updateState({
      authMode: authModes.PASSWORD,
    });
  };

  const signUpOptionsView = (formikProps) => {
    if (state.isEmailOtpSignupEnabled && state.authMethod === authMethods.EMAIL) {
      if (state.authMode === authModes.PASSWORD) {
        return (
          <CenteredView padding={[0, 0, 0, 0.5]}>
            <Button variant="tertiary" size="small" onClick={() => handleOTPSignUp(formikProps)}>
              Want To Sign Up With OTP?
            </Button>
          </CenteredView>
        );
      }
      if (state.authMode === authModes.OTP) {
        return (
          <CenteredView padding={[0, 0, 0, 0.5]}>
            <Button
              variant="tertiary"
              size="small"
              onClick={() => handlePasswordSignUp(formikProps)}
            >
              Want To Sign Up With Password?
            </Button>
          </CenteredView>
        );
      }
    }

    return null;
  };

  const formikInitialValues = {
    email: state.user.email,
    password: '',
  };

  formikInitialValues[OTP_FIELD_FORMIK_KEYNAME] = '';

  return (
    <Formik
      initialValues={formikInitialValues}
      onSubmit={(values, formikActions) => {
        handleNext(values, formikActions);
      }}
      enableReinitialize
      validationSchema={validationSchema}
      validateOnMount
    >
      {(formikProps) => {
        return (
          <Size height="100%">
            <form onSubmit={formikProps.handleSubmit}>
              <Screen>
                <Screen.Content>
                  <Flex flexDirection="column" flexGrow={1}>
                    <View>
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

                      {isPasswordFlow() ? (
                        <SignUpPassword
                          showPasswordRules={showPasswordRules}
                          isFocusPassword={isMerchantInvite || isInvite}
                        /> // [AB] Set Password UX - test
                      ) : (
                        <SignUpOtp
                          handleChangeOnClick={() => handleSignUpOptionsClick(formikProps, true)}
                          email={formikProps.values.email || state.user.email}
                          autoReadOtpSignup={autoReadOtpSignup}
                        />
                      )}

                      <ReCaptchaV2
                        ref={invisibleCaptchaRef}
                        size="invisible"
                        sitekey={__ENV__.invisibleCaptcha.key}
                        onChange={(value) => handleInvisibleCaptchaChange(formikProps, value)}
                        onErrored={hideLoader}
                        onExpired={hideLoader}
                      />

                      <Space padding={[2.5, 0]}>
                        <View>
                          <Button
                            type="submit"
                            size="medium"
                            variant="primary"
                            block
                            disabled={formikProps.isSubmitting}
                          >
                            Create Account
                          </Button>
                        </View>
                      </Space>

                      {signUpOptionsView(formikProps)}

                      {state.authMethod === authMethods.EMAIL && state.isEmailOtpSignupEnabled ? (
                        <Space margin={[1.25, 0, 1, 0]}>
                          <View>
                            <Separator
                              color="shade.960"
                              size="small"
                              textViewStyles={{ top: '-11px' }}
                            />
                          </View>
                        </Space>
                      ) : null}

                      {!isMerchantInvite && !isInvite ? (
                        <Flex justifyContent="center">
                          <View>
                            <Space padding={[0, 0, 0, 0.5]}>
                              <Button
                                variant="tertiary"
                                size="small"
                                icon={ChevronLeft}
                                onClick={() => handleSignUpOptionsClick(formikProps)}
                              >
                                Use Another Sign Up Option
                              </Button>
                            </Space>
                          </View>
                        </Flex>
                      ) : null}
                    </View>
                  </Flex>
                  <Space padding={[1.25, 0, 0]}>
                    <View>
                      <Text size="small" color="shade.960">
                        By signing up you agree to our{' '}
                        <Link
                          size="small"
                          href="https://razorpay.com/privacy/"
                          target="_blank"
                          onClick={() => handlePrivacyClick(formikProps)}
                        >
                          privacy policy
                        </Link>{' '}
                        and{' '}
                        <Link
                          size="small"
                          href="https://razorpay.com/terms/"
                          target="_blank"
                          onClick={() => handleTncClick(formikProps)}
                        >
                          terms of use.
                        </Link>
                      </Text>
                    </View>
                  </Space>
                </Screen.Content>
              </Screen>
            </form>
          </Size>
        );
      }}
    </Formik>
  );
};

SignUp.propTypes = {
  handleFieldFocus: PropTypes.func,
  handleSubmit: PropTypes.func,
  showPasswordRules: PropTypes.bool,
  skipCaptcha: PropTypes.bool,
  autoReadOtpSignup: PropTypes.bool,
};

SignUp.defaultProps = {
  handleSubmit: () => {},
  showPasswordRules: false,
  skipCaptcha: false,
  autoReadOtpSignup: false,
};

export default SignUp;

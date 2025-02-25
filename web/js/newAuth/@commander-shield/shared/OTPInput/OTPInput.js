import React, { useRef } from 'react';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { useFormikContext } from 'formik';
import hasKey from '@razorpay/universe-utils/hasKey';
import PropTypes from 'prop-types';
import LinkButton from '../../shared/LinkButton';
import TextInput from '../../shared/TextInput';
import useSnackbar from '../Snackbar/useSnackbar';
import useUserContext from '../../user/useUserContext';
import { SIGNIN, SIGNUP, authMethods, OTP_FIELD_FORMIK_KEYNAME } from '../../screens/screenHelpers';
import signUpEvents from '../../screens/SignUp/signUpEvents';
import signInEvents from '../../screens/SignIn/signInEvents';
import { handleEventForInputError } from '../../js/signUpAnalytics';
import captureException, { captureSignupException } from '../captureException';
import useOTPCountdownTimer from './useOTPCountdownTimer';
import otpInputEvents from './OTPinputEvents';

const RESEND_OTP_ERROR = 'Could not send OTP. Please refresh the page and try again';
const RESEND_OTP_SUCCESS = 'New OTP has been sent successfully.';
const INPUT_FIELD_NAME = 'otp';

const resendLoginOTP = async (state, actions) => {
  let resendOtpRes;
  if (state.authMethod === authMethods.PHONE_NUMBER) {
    if (state.user.isMobileNumberVerified) {
      resendOtpRes = await actions.resendLoginOTP({
        mobileNumber: state.user.mobileNumber,
        otpToken: state.user.otpToken,
      });
    } else {
      resendOtpRes = await actions.userVerificationResendOTP({
        mobileNumber: state.user.mobileNumber,
        otpToken: state.user.otpToken,
        password: state.user.password,
      });
    }
  } else {
    resendOtpRes = await actions.resendLoginOTP({
      email: state.user.email,
      otpToken: state.user.otpToken,
    });
  }

  return resendOtpRes;
};

const OTPInput = ({ context, email, otpError, setOTPError, autoReadOtpSignup }) => {
  const formikProps = useFormikContext();
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();
  const [isResendOTPSuccess, setIsResendOTPSuccess] = React.useState(false);
  const [isAutoReadOtpInitialized, setIsAutoReadOtpInitialized] = React.useState(false);
  const { timerText, isTimerRunning, resetTimer } = useOTPCountdownTimer();
  const otpFieldRef = useRef(null);
  let readOtpAbortcontroller;

  if (typeof AbortController === 'function') {
    readOtpAbortcontroller = new AbortController();
  }

  React.useEffect(() => {
    if (formikProps.errors.otp) {
      setIsResendOTPSuccess(false);
    }
  }, [formikProps.errors.otp]);

  // Read OTP from SMS in chrome
  React.useEffect(() => {
    try {
      if (autoReadOtpSignup && context === SIGNUP) {
        if (!isAutoReadOtpInitialized) {
          setIsAutoReadOtpInitialized(true);
          if ('OTPCredential' in window) {
            otpInputEvents.trackAutoOtpReadInitiate(context);
            navigator.credentials
              .get({
                otp: { transport: ['sms'] },
                signal: readOtpAbortcontroller?.signal,
              })
              .then((otp) => {
                otpInputEvents.trackAutoOtpReadSuccess(context);
                formikProps.setFieldValue(OTP_FIELD_FORMIK_KEYNAME, otp.code);
                formikProps.submitForm();
              })
              .catch((err) => {
                otpInputEvents.trackAutoOtpReadFailed(context, err);
                captureSignupException(err);
              });
          }
        } else if (formikProps.isSubmitting) {
          // stop reading otp once form submission is attempted
          if (readOtpAbortcontroller) {
            readOtpAbortcontroller.abort();
          }
        }
      }
    } catch (errInEffect) {
      captureException(errInEffect);
    }
  }, [autoReadOtpSignup, context, formikProps, isAutoReadOtpInitialized, readOtpAbortcontroller]);

  const resendOTP = async () => {
    resetTimer();
    try {
      let resendOtpRes;
      if (context === SIGNIN) {
        signInEvents.trackResendOtpInitiate();
        resendOtpRes = await resendLoginOTP(state, actions);
        signInEvents.trackResendOtpSuccess();

        // store new token
        actions.updateUser({
          otpToken: resendOtpRes.data.token,
        });
      } else {
        signUpEvents.trackResendOtpInitiate();
        resendOtpRes = await actions.resendSignUpOTP({
          contact: state.user.contact,
          token: state.user.otpToken,
          email,
        });

        signUpEvents.trackResendOtpSuccess();

        // store new token
        actions.updateUser({
          otpToken: resendOtpRes.user.token,
        });
      }
      formikProps.setErrors({});
      setOTPError('');
      setIsResendOTPSuccess(true);
    } catch (err) {
      if (context === SIGNIN) {
        signInEvents.trackResendOtpFailure();
      } else {
        signUpEvents.trackResendOtpFailure();
      }

      snackbar.error(RESEND_OTP_ERROR);
      setIsResendOTPSuccess(false);
    }
  };

  const getErrorText = () => {
    if (formikProps.errors.otp && hasKey(formikProps.touched, INPUT_FIELD_NAME)) {
      setOTPError('');
      return formikProps.errors.otp;
    }

    if (otpError) {
      return otpError;
    }

    setOTPError('');
    return undefined;
  };

  return (
    <>
      <Space margin={[1, 0, 2, 0]}>
        <Text size="xsmall" color="shade.980">
          {`A 6-digit OTP has been sent to your ${
            email ? 'email' : 'phone number'
          }. OTP will expire in 5 mins.`}
        </Text>
      </Space>
      <TextInput
        ref={otpFieldRef}
        name={INPUT_FIELD_NAME}
        width="auto"
        autoComplete="one-time-code"
        value={formikProps.values.otp}
        label="Enter OTP"
        successText={isResendOTPSuccess ? RESEND_OTP_SUCCESS : null}
        errorText={getErrorText()}
        placeholder=""
        onChange={(value) => formikProps.setFieldValue(INPUT_FIELD_NAME, value)}
        variant="filled"
        type="number"
        onBlur={(value) => {
          formikProps.setFieldTouched(INPUT_FIELD_NAME, value);
          handleEventForInputError(formikProps, INPUT_FIELD_NAME, signUpEvents);
        }}
      />
      <Space margin={[0.5, 0, 0, 0]}>
        {isTimerRunning ? (
          <Text size="xsmall" color="shade.950">
            Resend OTP in {timerText}
          </Text>
        ) : (
          <Text size="xsmall" color="shade.950">
            {"Didn't recieve an OTP? "}
            <LinkButton key="link-button-123" size="xsmall" type="button" onClick={resendOTP}>
              Resend OTP
            </LinkButton>
          </Text>
        )}
      </Space>
    </>
  );
};

OTPInput.propTypes = {
  context: PropTypes.string,
  otpError: PropTypes.string,
  setOTPError: PropTypes.func,
  email: PropTypes.string,
  autoReadOtpSignup: PropTypes.bool,
};

OTPInput.defaultProps = {
  otpError: '',
  setOTPError: () => {},
  autoReadOtpSignup: false,
};

export default OTPInput;

import React, { useEffect, useRef } from 'react';
import * as yup from 'yup';
import { Formik } from 'formik';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import hasKey from '@razorpay/universe-utils/hasKey';
import captureException, { sentryFlows } from '../../shared/captureException';
import TextInput from '../../shared/TextInput';
import Button from '../../shared/Button';
import Screen from '../../shared/Screen/';
import Link from '../../shared/Link';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import useProgressBar from '../../shared/ProgressBar/useProgressBar';
import { goToDashboard, setSignupExpData } from '../screenHelpers';
import getExpStatus from '../../utils/getExperimentStatus';
import { handleEventForInputError } from '../../js/signUpAnalytics';
import { setIsRemovePreSignUpExperimentEnabled } from '../../js/analytics';
import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../../shared/Experiments/Experiments';
import verifyEmailEvents from './verifyEmailEvents';

const VerifyEmail = () => {
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();
  const { setPercent } = useProgressBar();
  const otpFieldRef = useRef(null);

  useEffect(() => {
    setPercent(80);
    if (!state.user.token) {
      (async () => {
        try {
          await actions.resendOtp();
        } catch (error) {
          captureException(error, {
            flow: sentryFlows.VERIFY_EMAIL,
          });
          actions.showErrorScreen();
        }
      })();
    }
  }, [setPercent]);

  useEffect(() => {
    if (getExpStatus(state.user, REMOVE_PRESIGNUP_FUNCTIONALITY)) {
      setIsRemovePreSignUpExperimentEnabled(true); // For analytics
    }
  }, [state.user.experiments]);

  const verifyEmail = async (value) => {
    otpFieldRef.current.blur();
    verifyEmailEvents.trackVerifyEmailInitiate(state.user);
    try {
      verifyEmailEvents.trackVerifyEmailRequest();
      const response = await actions.verifyEmail(value);
      verifyEmailEvents.trackVerifyEmailSuccess(state.user);

      if (response.user.isConfirmed) {
        await actions.sendDeviceDetails();
        // timeout to ensure tracking event fires successfully before redirecting
        setTimeout(() => {
          setSignupExpData();
          goToDashboard(state.signUpSource);
        }, 0);
      }
    } catch (error) {
      captureException(error, {
        flow: sentryFlows.VERIFY_EMAIL,
      });
      verifyEmailEvents.trackVerifyEmailError({ user: state.user, error: error?.message });
      snackbar.error(error?.message);
    }
  };

  const handleResend = async () => {
    verifyEmailEvents.trackResendOtpInitiate(state.user);

    try {
      await actions.resendOtp();
      snackbar.success('Code sent successfully');
    } catch (error) {
      captureException(error, {
        flow: sentryFlows.VERIFY_EMAIL,
      });
      verifyEmailEvents.trackResendOtpError(error?.message);
      snackbar.error(error?.message);
    }
  };

  const handleSubmit = (values) => {
    verifyEmail(values.verification);
  };

  const handleOnChange = async (formikProps, value) => {
    formikProps.setFieldValue('verification', value);
    await formikProps.validateForm();
    if (value.toString().length === 6) {
      verifyEmail(value);
    }
  };

  return (
    <Formik
      initialValues={{ verification: '' }}
      onSubmit={handleSubmit}
      validationSchema={yup.object().shape({
        verification: yup
          .string()
          .length(6, 'Please enter a valid 6-digit verification code.')
          .required('Please enter a valid 6-digit verification code.'),
      })}
      validateOnMount
    >
      {(formikProps) => {
        if (formikProps.errors.verification) {
          setPercent(80);
        } else {
          setPercent(100);
        }

        return (
          <Size height="100%">
            <form onSubmit={formikProps.handleSubmit}>
              <Screen>
                <Screen.Content>
                  <Space padding={[4.75, 0, 2, 0]}>
                    <View>
                      <Heading weight="bold" size="xlarge">
                        Verify your email
                      </Heading>
                    </View>
                  </Space>
                  <Text color="shade.960" size="small">
                    A verification code has been sent to
                  </Text>
                  <Space padding={[0.5, 0, 0]}>
                    <Flex alignItems="center" flexWrap="wrap">
                      <View>
                        <Text color="shade.970" size="medium">
                          {state.user.email}
                        </Text>
                      </View>
                    </Flex>
                  </Space>
                  <Space padding={[4, 0, 0]}>
                    <View>
                      <TextInput
                        ref={otpFieldRef}
                        name="verification"
                        value={formikProps.values.verification}
                        width="auto"
                        label="Verification code"
                        disabled={false}
                        placeholder="6 digit verification code"
                        maxLength={6}
                        errorText={
                          formikProps.errors.verification &&
                          hasKey(formikProps.touched, 'verification')
                            ? formikProps.errors.verification
                            : undefined
                        }
                        variant="filled"
                        type="number"
                        onChange={(value) => handleOnChange(formikProps, value)}
                        onBlur={(value) => {
                          formikProps.setFieldTouched('verification', value);
                          handleEventForInputError(formikProps, 'verification', verifyEmailEvents);
                        }}
                      />
                    </View>
                  </Space>
                  <Space padding={[1, 0]}>
                    <Flex>
                      <View>
                        <Text size="xsmall" color="shade.960">
                          {"Didn't receive the code?"}
                        </Text>
                        <Space padding={[0, 0.5]}>
                          <Link onClick={handleResend} size="xsmall">
                            Resend
                          </Link>
                        </Space>
                      </View>
                    </Flex>
                  </Space>
                </Screen.Content>
                <Screen.Footer>
                  <Button type="submit" block disabled={!formikProps.isValid}>
                    Verify Email
                  </Button>
                </Screen.Footer>
              </Screen>
            </form>
          </Size>
        );
      }}
    </Formik>
  );
};

export default VerifyEmail;

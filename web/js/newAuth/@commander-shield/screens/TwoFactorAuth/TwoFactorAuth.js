import React from 'react';
import { useNavigate } from 'react-router-dom';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import hasKey from '@razorpay/universe-utils/hasKey';
import * as yup from 'yup';
import { Formik } from 'formik';
import PropTypes from 'prop-types';
import Button from '../../shared/Button';
import useLocationQuery from '../../shared/useLocationQuery';
import Screen from '../../shared/Screen';
import {
  redirectTo,
  goToDashboard,
  goToScreen,
  screenMap,
  goToOnboardingScreen,
  setLoggedInViaInStorage,
  authMethods,
  setMidCookie,
} from '../screenHelpers';
import TextInput from '../../shared/TextInput';
import Link from '../../shared/Link';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import { authApiResponse } from '../../api/apiHelpers';
import captureException, { sentryFlows } from '../../shared/captureException';
import signInEvents from '../SignIn/signInEvents';
import accountBlockEvents from '../AccountBlock/accountBlockEvents';
import TermsAndConditionModal from '../../shared/TermsAndConditionModal';
import twoFactorAuthEvents from './twoFactorAuthEvents';

const TwoFactorAuth = ({ onSubmit }) => {
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();
  const { state, actions } = useUserContext();
  const snackbar = useSnackbar();

  const handleErrors = (error) => {
    const { message, internalData } = error;
    const { user } = state;
    const { email } = user;
    if (message === authApiResponse.accountBlocked) {
      accountBlockEvents.trackInitiated({
        email,
        method: authMethods.EMAIL,
        flow: `${authMethods.EMAIL}_2fa`,
      });
      actions.updateUser({
        isOwner: Boolean(internalData?.is_owner),
      });
      goToScreen({ screen: screenMap.accountBlock, navigate, locationQuery });
    } else {
      captureException(error, {
        flow: sentryFlows.TWO_FACTOR_OTP_AUTH,
        email,
      });
      snackbar.error(message);
    }
    twoFactorAuthEvents.trackFailure({ email, error: message });
  };

  const proceedWithSignIn = async () => {
    try {
      const { user, authMethod, signInGoogleAuthType } = state;
      const { email, loggedInVia } = user;
      setLoggedInViaInStorage(loggedInVia, email);
      const response = await actions.getUserDetails();
      setMidCookie(response?.user?.mid, response?.user?.id);
      twoFactorAuthEvents.trackSuccess({ email });
      signInEvents.trackSignInSuccess({
        email,
        method: authMethod,
        googleAuthVariant: signInGoogleAuthType,
        mid: response.user.mid,
        userId: response.user.id,
      });
      if (response.user.isConfirmed) {
        actions.showLoader();
        if (state.user.redirectUrl) {
          redirectTo(state.user.redirectUrl);
        } else {
          goToDashboard();
        }
      } else {
        goToOnboardingScreen({
          user: response.user,
          navigate,
          locationQuery,
        });
      }
    } catch (error) {
      handleErrors(error);
    }
  };

  const handleTncModalClose = () => {
    actions.updateUser({ show_tnc_popup: false });
  };

  const handleAcceptTnC = async () => {
    try {
      actions.updateUser({ show_tnc_popup: false });
      await actions.updateTermsAndConditions(true);
      proceedWithSignIn();
    } catch (error) {
      snackbar.error(error?.message);
    }
  };

  const verify2faOtp = async (otp) => {
    try {
      twoFactorAuthEvents.trackInitiate({ email: state.user.email });

      const twoFAResponse = await actions.verify2faOtp(otp);
      const { user } = twoFAResponse;
      // is show_tnc_popup is false then continue with signin else show the Terms And Conditions(TnC) popup & proceed once user accept the TnC
      if (!user?.show_tnc_popup) {
        proceedWithSignIn();
      }
    } catch (error) {
      handleErrors(error);
    }
  };

  const handleSubmit = (values) => {
    verify2faOtp(values.otp);
    if (onSubmit) {
      onSubmit(values);
    }
  };

  const handleResend = async () => {
    try {
      twoFactorAuthEvents.trackResendOtpInitiate({ email: state.user.email });
      await actions.resend2faOtp();

      twoFactorAuthEvents.trackResendOtpSuccess({ email: state.user.email });
      snackbar.success('Code sent successfully');
    } catch (error) {
      twoFactorAuthEvents.trackResendOtpFailure({
        email: state.user.email,
        error: error.message,
      });
      snackbar.error(error.message);

      captureException(error, {
        flow: sentryFlows.TWO_FACTOR_OTP_AUTH,
        email: state.user.email,
      });
    }
    if (onSubmit) {
      onSubmit();
    }
  };

  const handleOnChange = async (formikProps, value) => {
    formikProps.setFieldValue('otp', value);
    await formikProps.validateForm();
    if (value.toString().length === 6) {
      verify2faOtp(value);
    }
    if (onSubmit) {
      onSubmit(value);
    }
  };

  return (
    <>
      <Formik
        initialValues={{ otp: '' }}
        onSubmit={handleSubmit}
        validationSchema={yup.object().shape({
          otp: yup
            .string()
            .length(6, 'Please enter a valid 6-digit verification code.')
            .required('Please enter a valid 6-digit verification code.'),
        })}
        validateOnMount
      >
        {(formikProps) => {
          return (
            <Size height="100%">
              <form onSubmit={formikProps.handleSubmit}>
                <Screen>
                  <Screen.Content>
                    <Space padding={[4.75, 0, 3, 0]}>
                      <View>
                        <Heading size="xlarge">2 Step Verification</Heading>
                      </View>
                    </Space>
                    <Flex>
                      <View>
                        <Text color="shade.980" size="small">
                          A 6-digit OTP has been sent to your phone number. OTP will expire in 5
                          mins.
                        </Text>
                      </View>
                    </Flex>
                    <Space padding={[4, 0, 0]}>
                      <View>
                        <TextInput
                          name="otp"
                          value={formikProps.values.otp}
                          width="auto"
                          label="OTP"
                          disabled={false}
                          placeholder="6 digit verification code"
                          maxLength={6}
                          errorText={
                            formikProps.errors.otp && hasKey(formikProps.touched, 'otp')
                              ? formikProps.errors.otp
                              : undefined
                          }
                          variant="filled"
                          type="number"
                          onChange={(value) => handleOnChange(formikProps, value)}
                          onBlur={(value) => formikProps.setFieldTouched('otp', value)}
                        />
                      </View>
                    </Space>
                    <Space padding={[1, 0]}>
                      <Flex>
                        <View>
                          <Text size="small" color="shade.960">
                            Didn't receive an SMS?
                          </Text>
                          <Space padding={[0, 0.5]}>
                            <Link onClick={handleResend} size="xsmall">
                              Resend OTP
                            </Link>
                          </Space>
                        </View>
                      </Flex>
                    </Space>
                    <Space padding={[3, 0]}>
                      <Flex>
                        <View>
                          <Flex flexBasis="100%">
                            <Button type="submit" block>
                              Confirm
                            </Button>
                          </Flex>
                        </View>
                      </Flex>
                    </Space>
                  </Screen.Content>
                </Screen>
              </form>
            </Size>
          );
        }}
      </Formik>
      {state.user.show_tnc_popup ? (
        <TermsAndConditionModal closeModal={handleTncModalClose} handleAccept={handleAcceptTnC} />
      ) : null}
    </>
  );
};
TwoFactorAuth.propTypes = {
  onSubmit: PropTypes.func,
};

export default TwoFactorAuth;

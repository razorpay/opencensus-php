import React from 'react';
import { useNavigate } from 'react-router-dom';
import Size from '@razorpay/blade-old/src/atoms/Size';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import hasKey from '@razorpay/universe-utils/hasKey';
import * as yup from 'yup';
import { Formik } from 'formik';
import PropTypes from 'prop-types';
import TextInput from '../../shared/TextInput';
import Button from '../../shared/Button';
import Link from '../../shared/Link';
import Screen from '../../shared/Screen';
import useUserContext from '../../user/useUserContext';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import { goToScreen, screenMap } from '../screenHelpers';
import useLocationQuery from '../../shared/useLocationQuery';
import captureException, { sentryFlows } from '../../shared/captureException';
import forgotPasswordEvents from './forgotPasswordEvents';

const RESET_PASSWORD_TEXT = `We have sent a reset password link to your email. Didn’t receive the email? Check email address again or look in your spam folder.`;

const ForgotPassword = ({ onSubmit }) => {
  const snackbar = useSnackbar();
  const navigate = useNavigate();
  const locationQuery = useLocationQuery();
  const { state, actions } = useUserContext();
  const formikInitialValues = { email: state.user.email };

  const handleBackToLogin = () => {
    goToScreen({ screen: screenMap.signIn, navigate, locationQuery });
  };

  const handleOnSubmit = async (values) => {
    try {
      forgotPasswordEvents.trackSubmitInitiate();
      await actions.resetPassword(values.email);

      forgotPasswordEvents.trackSubmitSuccess();
      snackbar.success(RESET_PASSWORD_TEXT);
    } catch (error) {
      forgotPasswordEvents.trackSubmitError({ error: error.message });
      captureException(error, {
        flow: sentryFlows.FORGOT_PASSWORD,
        email: state.user.email,
      });
      snackbar.error(error?.message);
    }
    actions.updateUser({
      email: values.email,
    });
    onSubmit(); // used in testing
  };

  return (
    <Formik
      initialValues={formikInitialValues}
      onSubmit={handleOnSubmit}
      enableReinitialize
      validationSchema={yup.object().shape({
        email: yup
          .string()
          .email('Please enter a valid email id.')
          .required('Please enter a valid email id.'),
      })}
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
                        Forgot Password
                      </Heading>
                    </View>
                  </Space>
                  <Space padding={[1, 0, 1, 0]}>
                    <View>
                      <TextInput
                        name="email"
                        width="auto"
                        autoCapitalize="none"
                        value={formikProps.values.email}
                        label="Your email"
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
                        }}
                      />
                    </View>
                  </Space>
                  <Space padding={[2, 0]}>
                    <View>
                      <Button
                        type="submit"
                        size="medium"
                        variant="primary"
                        block
                        disabled={!formikProps.isValid || formikProps.isSubmitting}
                      >
                        Send Reset Link
                      </Button>
                    </View>
                  </Space>
                  <Link onClick={handleBackToLogin}>Back to Login</Link>
                </Screen.Content>
              </Screen>
            </form>
          </Size>
        );
      }}
    </Formik>
  );
};

ForgotPassword.propTypes = {
  onSubmit: PropTypes.func,
};

ForgotPassword.defa = {
  onSubmit: () => {},
};

export default ForgotPassword;

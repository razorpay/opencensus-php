import React, { useEffect } from 'react';
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
import Screen from '../../shared/Screen/';
import useUserContext from '../../user/useUserContext';
import TextInput from '../../shared/TextInput';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import { goToScreen, screenMap } from '../screenHelpers';
import useLocationQuery from '../../shared/useLocationQuery';
import captureException, { sentryFlows } from '../../shared/captureException';
import { MOBILE_NUMBER_VERIFY_REGEX } from '../../utils/regex';
import setupTwoFactorAuthEvents from './setupTwoFactorAuthEvents';

const SetupTwoFactorAuth = ({ onSubmit }) => {
  const { state, actions } = useUserContext();
  const navigate = useNavigate();
  const snackbar = useSnackbar();
  const locationQuery = useLocationQuery();

  useEffect(() => {
    setupTwoFactorAuthEvents.trackPageLoad({ email: state.user.email });
  }, [state.user.email]);

  const setup2faMobileNumber = async (values) => {
    const email = state.user.email;
    try {
      setupTwoFactorAuthEvents.trackSetupTwoFactorInitiate({ email });
      await actions.setup2faMobileNumber(values.mobileNumber);

      setupTwoFactorAuthEvents.trackSetupTwoFactorSuccess({ email });
      goToScreen({ screen: screenMap.twoFactorAuth, navigate, locationQuery });
    } catch (error) {
      setupTwoFactorAuthEvents.trackSetupTwoFactorFailure({ email, error: error.message });
      captureException(error, {
        flow: sentryFlows.SETUP_2FA,
        email: state.user.email,
      });
      snackbar.error(error.message);
    }
  };

  const handleSubmit = (values) => {
    setup2faMobileNumber(values);
    if (onSubmit) {
      onSubmit(values);
    }
  };

  return (
    <Formik
      initialValues={{ mobileNumber: '' }}
      onSubmit={handleSubmit}
      validationSchema={yup.object().shape({
        mobileNumber: yup
          .string()
          .matches(MOBILE_NUMBER_VERIFY_REGEX, {
            message: 'Please provide a valid mobile number.',
            excludeEmptyString: true,
          })
          .required('Please enter your mobile number to continue.'),
      })}
      validateOnMount
    >
      {(formikProps) => {
        return (
          <Size height="100%">
            <form onSubmit={formikProps.handleSubmit}>
              <Screen>
                <Screen.Content>
                  <Space padding={[4.75, 0, 2, 0]}>
                    <View>
                      <Heading weight="bold" size="xlarge">
                        Setup 2 Step Verification
                      </Heading>
                    </View>
                  </Space>
                  <Text color="shade.980" size="small">
                    Let’s setup a mobile number where you will receive your verification code
                    everytime you log in to your account.
                  </Text>
                  <Space padding={[4, 0, 0]}>
                    <View>
                      <TextInput
                        type="text"
                        name="mobileNumber"
                        value={formikProps.values.mobileNumber}
                        width="auto"
                        label="Mobile number"
                        placeholder="Mobile number"
                        errorText={
                          formikProps.errors.mobileNumber &&
                          hasKey(formikProps.touched, 'mobileNumber')
                            ? formikProps.errors.mobileNumber
                            : undefined
                        }
                        variant="filled"
                        onChange={(value) => formikProps.setFieldValue('mobileNumber', value)}
                        onBlur={(value) => formikProps.setFieldTouched('mobileNumber', value)}
                      />
                    </View>
                  </Space>
                  <Space padding={[3, 0]}>
                    <Flex>
                      <View>
                        <Flex flexBasis="100%">
                          <Button type="submit" block>
                            Get Verification Code
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
  );
};

SetupTwoFactorAuth.propTypes = {
  onSubmit: PropTypes.func,
};

export default SetupTwoFactorAuth;

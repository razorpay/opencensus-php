import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { Formik } from 'formik';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import hasKey from '@razorpay/universe-utils/hasKey';
import styled from 'styled-components';
import { goToDashboardSignIn } from '../screenHelpers';
import useSnackbar from '../../shared/Snackbar/useSnackbar';
import { userActions } from '../../user/userDuck';
import TextInput from '../../shared/TextInput';
import { PASSWORD_REGEX, EMAIL_VERIFY_REGEX } from '../../utils/regex';
import { PASSWORD_ERROR_TEXT } from '../../shared/constants';
import LinkButton from '../../shared/LinkButton';
import Button from '../../shared/Button';
import captureException, { sentryFlows } from '../../shared/captureException';
import { useLocation } from 'react-router-dom';

const ERROR_TEXT = 'Something went wrong!';
const CONFIRM_PASSWORD_ERROR = 'Passwords do not match!';

const FormView = styled(View)`
  background-color: #ffffff;
  border: 1px solid #e7ecee;
  border-radius: 4px;
`;

const EmailUpdate = ({ logo = '' }) => {
  const location = useLocation();
  const snackbar = useSnackbar();
  const [redirecting, setRedirecting] = useState(false);
  const [showSigninButton, setShowSigninButton] = useState(false);
  const url = new URLSearchParams(location.search);
  let token = url.get('token');
  let email = url.get('email');
  let mid = url.get('mid');
  token = token && decodeURIComponent(url.get('token'));
  email = email && decodeURIComponent(url.get('email'));
  mid = mid && decodeURIComponent(url.get('mid'));

  useEffect(() => {
    if ((!token || !email || !EMAIL_VERIFY_REGEX.test(email)) && !mid && !redirecting) {
      setRedirecting(true);
      goToDashboardSignIn();
    }
  }, [token, email, mid, redirecting]);

  const validateForm = (values) => {
    const errors = {};
    if (!values.password || !PASSWORD_REGEX.test(values.password)) {
      errors.password = PASSWORD_ERROR_TEXT;
    }
    if (values.password && values.password !== values.confirmPassword) {
      errors.confirmPassword = CONFIRM_PASSWORD_ERROR;
    }

    return errors;
  };

  const handleNext = async (values) => {
    try {
      await userActions().emailUpdate({
        merchant_id: mid,
        password: values.password,
        passwordConfirmation: values.confirmPassword,
        token,
      });
      setShowSigninButton(true);
    } catch (error) {
      captureException(error, {
        flow: sentryFlows.RESET_PASSWORD,
      });
      snackbar.error(error?.message || ERROR_TEXT);
    }
  };

  const FIELD_NAMES = {
    EMAIL: 'email',
    PASSWORD: 'password',
    CONFIRM_PASSWORD: 'confirmPassword',
  };

  const formikInitialValues = {
    [FIELD_NAMES.EMAIL]: email,
    [FIELD_NAMES.PASSWORD]: '',
    [FIELD_NAMES.CONFIRM_PASSWORD]: '',
  };

  return (
    <Formik initialValues={formikInitialValues} onSubmit={handleNext} validate={validateForm}>
      {(formikProps) => {
        return (
          <Flex flexDirection="column" justifyContent="center" alignItems="center">
            <View>
              <Size maxWidth="296px">
                <Flex flexDirection="column" justifyContent="center">
                  <View>
                    <Space margin={[4, 0, 2, 0]}>
                      <img src={logo} />
                    </Space>
                    <Space margin={[0, 0, 2, 0]}>
                      <Text size="large" align="center" weight="bold" color="shade.980">
                        Set Password
                      </Text>
                    </Space>
                    <View>
                      <form onSubmit={formikProps.handleSubmit}>
                        <Space padding={[1, 1, 1, 1]}>
                          <FormView>
                            <TextInput
                              name={FIELD_NAMES.EMAIL}
                              width="auto"
                              value={formikProps.values.email || ''}
                              label=""
                              placeholder="Email"
                              variant="filled"
                              type="email"
                              disabled
                            />
                            <Space margin={[1, 0, 1, 0]}>
                              <View>
                                <TextInput
                                  name={FIELD_NAMES.PASSWORD}
                                  width="auto"
                                  value={formikProps.values.password}
                                  label=""
                                  errorText={
                                    hasKey(formikProps.touched, FIELD_NAMES.PASSWORD)
                                      ? formikProps.errors.password
                                      : ''
                                  }
                                  placeholder="Password" // [AB] Set Password UX - control, test
                                  variant="filled"
                                  type="password"
                                  onChange={(value) =>
                                    formikProps.setFieldValue(FIELD_NAMES.PASSWORD, value)
                                  }
                                  onBlur={(value) => {
                                    formikProps.setFieldTouched(FIELD_NAMES.PASSWORD, value);
                                  }}
                                />
                              </View>
                            </Space>
                            <TextInput
                              name={FIELD_NAMES.CONFIRM_PASSWORD}
                              width="auto"
                              value={formikProps.values.confirmPassword}
                              label=""
                              errorText={
                                hasKey(formikProps.touched, FIELD_NAMES.CONFIRM_PASSWORD)
                                  ? formikProps.errors.confirmPassword
                                  : ''
                              }
                              placeholder="Password confirmation" // [AB] Set Password UX - control, test
                              variant="filled"
                              type="password"
                              onChange={(value) =>
                                formikProps.setFieldValue(FIELD_NAMES.CONFIRM_PASSWORD, value)
                              }
                              onBlur={(value) => {
                                formikProps.setFieldTouched(FIELD_NAMES.CONFIRM_PASSWORD, value);
                              }}
                            />
                          </FormView>
                        </Space>
                        <Space margin={[3, 0, 0, 0]}>
                          <Button
                            type="submit"
                            size="large"
                            variant="primary"
                            block
                            disabled={formikProps.isSubmitting}
                          >
                            Set Password
                          </Button>
                        </Space>
                      </form>
                    </View>
                    {showSigninButton ? (
                      <Flex justifyContent="center" flexDirection="row" alignItems="center">
                        <Space margin={[2, 0, 0, 0]} padding={[1, 0, 0, 0]}>
                          <View>
                            <Text>Password Reset successful.</Text>
                            <Space margin={[0, 0, 0, 1]}>
                              <LinkButton
                                onClick={() => {
                                  goToDashboardSignIn();
                                }}
                                size="medium"
                                type="button"
                              >
                                Sign in
                              </LinkButton>
                            </Space>
                          </View>
                        </Space>
                      </Flex>
                    ) : null}
                    <Space margin={[4, 0, 0, 0]}>
                      <Text size="small" align="center" color="shade.940">
                        &#169; 2022 Copyright Razorpay
                      </Text>
                    </Space>
                  </View>
                </Flex>
              </Size>
            </View>
          </Flex>
        );
      }}
    </Formik>
  );
};

EmailUpdate.propTypes = {
  logo: PropTypes.string,
  location: PropTypes.object,
};

export default EmailUpdate;

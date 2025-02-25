import React, { useEffect, useState, useRef } from 'react';
import PropTypes from 'prop-types';
import { useFormikContext } from 'formik';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import hasKey from '@razorpay/universe-utils/hasKey';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '../../shared/TextInput';
import useUserContext from '../../user/useUserContext';
import { handleEventForInputError } from '../../js/signUpAnalytics';
import InputRules from '../../shared/InputRules/InputRules';
import signUpEvents from './signUpEvents';

const SignUpPassword = ({ showPasswordRules, isFocusPassword }) => {
  const [showHelpText, setShowHelpText] = useState(true); // [AB] Set Password UX - control
  const [showRules, setShowRules] = useState(false); // [AB] Set Password UX - test
  const formikProps = useFormikContext();
  const { state } = useUserContext();
  const passwordRef = useRef(null);
  const disableEmail =
    !isEmpty(state.user.email) &&
    (!isEmpty(state.user.invitationCode) || !isEmpty(state.user.merchantInvitationCode));

  const handlePasswordChange = (value) => {
    formikProps.setFieldValue('password', value);

    if (showPasswordRules) {
      // [AB] Set Password UX - test
      setShowRules(true);
    } else {
      /** [AB] Set Password UX - control */
      const passwordRegex = /^(?=.*\d)(?=.*[a-zA-Z]).{8,}$/;
      if (passwordRegex.test(value)) {
        setShowHelpText(false);
      } else {
        setShowHelpText(true);
      }
    }
  };

  const getPasswordErrorText = () => {
    if (formikProps.errors.password && hasKey(formikProps.touched, 'password')) {
      if (showPasswordRules) return ' ';
      // Return a space to activate red line decoration on input
      else return formikProps.errors.password;
    }

    return undefined;
  };

  useEffect(() => {
    if (isFocusPassword) {
      passwordRef.current.focus();
    }
  }, []);

  return (
    <>
      <Space padding={[1, 0, 1.5, 0]}>
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
            disabled={disableEmail}
            variant="filled"
            type="email"
            onChange={(value) => formikProps.setFieldValue('email', value)}
            onBlur={(value) => {
              formikProps.setFieldTouched('email', value);
              signUpEvents.trackEmailInput();
              handleEventForInputError(formikProps, 'email', signUpEvents);
            }}
          />
        </View>
      </Space>
      <Space padding={[1.5, 0]}>
        <View>
          <TextInput
            name="password"
            width="auto"
            value={formikProps.values.password}
            label="Create a password"
            ref={passwordRef}
            errorText={
              /** [AB] Set Password UX - control, test */
              getPasswordErrorText()
            }
            helpText={
              /** [AB] Set Password UX - control */
              !showPasswordRules && !isEmpty(formikProps.values.password) && showHelpText
                ? 'Password should have a minimum of 8 characters with at least 1 letter & 1 number.'
                : null
            }
            placeholder={showPasswordRules ? 'Password' : 'Min 8 char, 1 letter & 1 number'} // [AB] Set Password UX - control, test
            variant="filled"
            type="password"
            onChange={(value) => handlePasswordChange(value)}
            onBlur={(value) => {
              formikProps.setFieldTouched('password', value);
              signUpEvents.trackPasswordInput();
              handleEventForInputError(formikProps, 'password', signUpEvents);
            }}
          />
          {showPasswordRules ? (
            <InputRules
              value={formikProps.values.password}
              touched={showRules || formikProps.touched.password}
              rules={[
                {
                  id: 'min-char',
                  description: 'Minimum 8 characters',
                  pattern: /^.{8,}$/,
                },
                {
                  id: 'min-letter',
                  description: 'Must include 1 letter',
                  pattern: /^(?=.*?[a-zA-Z])/,
                },
                {
                  id: 'min-number',
                  description: 'Must include 1 number',
                  pattern: /^(?=.*?[0-9])/,
                },
              ]}
            />
          ) : null}
        </View>
      </Space>
    </>
  );
};

SignUpPassword.propTypes = {
  showPasswordRules: PropTypes.bool,
  isFocusPassword: PropTypes.bool,
};

SignUpPassword.defaultProps = {
  showPasswordRules: false,
  isFocusPassword: false,
};

export default SignUpPassword;

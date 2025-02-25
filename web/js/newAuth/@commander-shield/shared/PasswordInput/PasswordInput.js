import React from 'react';
import { useFormikContext } from 'formik';
import PropTypes from 'prop-types';
import hasKey from '@razorpay/universe-utils/hasKey';
import TextInput from '../../shared/TextInput';

const PasswordInput = ({ handleForgotPassword }) => {
  const formikProps = useFormikContext();
  return (
    <TextInput
      name="password"
      width="auto"
      value={formikProps.values.password}
      label="Password"
      labelLink="(Forgot?)"
      labelLinkHandler={handleForgotPassword}
      errorText={
        formikProps.errors.password && hasKey(formikProps.touched, 'password')
          ? formikProps.errors.password
          : undefined
      }
      placeholder="Password"
      onChange={(value) => {
        formikProps.setFieldValue('password', value);
      }}
      variant="filled"
      type="password"
      onBlur={(value) => {
        formikProps.setFieldTouched('password', value);
      }}
    />
  );
};

PasswordInput.propTypes = {
  handleForgotPassword: PropTypes.func,
};

export default PasswordInput;

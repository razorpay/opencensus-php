import React, { useEffect, useState } from 'react';
import { TextInput, Text, Box, Divider, Link } from '@razorpay/blade/components';
import { Formik } from 'formik';
import isEmpty from 'lodash/isEmpty';

import { UseFormikReturnType } from 'common/typings';
import { setItem } from 'common/utils/localStorage';
import { mobileNumberSchema, SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';

import StepFooter from './StepFooter';
import {
  StyledForm,
  StyledInputWrapper,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

interface WelcomeBackProps {
  mobileNumber: string;
  setMobileNumber: (value: string) => void;
  setStep: React.Dispatch<React.SetStateAction<number>>;
}

const WelcomeBack = ({ setMobileNumber, setStep, mobileNumber }: WelcomeBackProps): JSX.Element => {
  const [isRedirecting, setIsRedirecting] = useState(false);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Signup',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.MOBILE_NUMBER],
    });
  }, []);

  const onCTAClick = () => {
    setIsRedirecting(true);
    // setting this value in localStorage because we will use this when the login is successful
    // to see if we want to redirect the user directly to partner dashboard
    // or open up the explore partner program dashboard
    // this will be removed after the consumption
    setItem('partner_intent', true);

    // redirect them to dashboard login
    const url = `/?mobileNumber=${mobileNumber}`;
    window.location.assign(url);
  };

  const onMobileChange = (formikProps, name, value) => {
    value = value.trim();
    if (!formikProps.touched[name])
      trackWithSegment({
        objectName: 'Signup',
        actionName: 'Initiated',
        location: 'Mobile Number',
      });
    formikProps.setFieldTouched(name);
    formikProps.setFieldValue(name, value);
    setMobileNumber(value);
  };

  const handleSignupClick = () => {
    setStep((step) => step - 1);
  };
  const noop = () => {};

  return (
    <Formik initialValues={{}} validationSchema={mobileNumberSchema} onSubmit={noop}>
      {(formikProps: UseFormikReturnType) => (
        <StyledForm onSubmit={(e) => e.preventDefault()} onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Welcome Back!</StyledTitle>
            <StyledSubtitle>Login with your existing account to continue</StyledSubtitle>

            <StyledInputWrapper>
              <TextInput
                autoFocus
                label="Phone Number"
                name="mobileNumber"
                placeholder="Enter mobile number"
                helpText="Your mobile number"
                errorText={String(formikProps.errors.mobileNumber)}
                value={mobileNumber}
                maxCharacters={10}
                type="telephone"
                onChange={({ name, value }) => {
                  onMobileChange(formikProps, name, value);
                }}
                validationState={formikProps.errors.mobileNumber ? 'error' : 'none'}
                testID="mobile-number-input"
              />
            </StyledInputWrapper>
            <Box display="flex" alignItems="center" marginTop="spacing.11">
              <Divider />
              <Box padding="spacing.5">
                <Text weight="bold" color="surface.text.subdued.lowContrast">
                  or
                </Text>
              </Box>
              <Divider />
            </Box>
            <Box marginTop="spacing.5" textAlign="center">
              <Text size="small" weight="regular" color="surface.text.muted.lowContrast">
                Want to create a separate Partner account?{' '}
                <Link size="small" onClick={handleSignupClick}>
                  Sign Up
                </Link>
              </Text>
            </Box>
          </StyledStepWrapper>
          <StepFooter
            isLoading={isRedirecting}
            ctaText="Login Now"
            onClick={() => onCTAClick()}
            disabled={!isEmpty(formikProps.errors) || !mobileNumber}
          />
        </StyledForm>
      )}
    </Formik>
  );
};

export default WelcomeBack;

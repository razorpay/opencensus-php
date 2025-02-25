import React, { useEffect, useState } from 'react';
import { TextInput } from '@razorpay/blade/components';
import { Formik } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { contactInfoSchema, SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import StepFooter from './StepFooter';
import {
  StyledForm,
  StyledInputWrapper,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

const ContactInfo = ({ setStep, setContactName }) => {
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Contact Details',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.CONTACT_INFO],
    });
  }, []);

  const onContactChange = (formikProps, name, value) => {
    value = value.trim();
    if (!formikProps.touched[name])
      trackWithSegment({
        objectName: 'Contact Details',
        actionName: 'Initiated',
        location: SCREEN_NAME[STEPS.CONTACT_INFO],
      });
    formikProps.setFieldTouched(name);
    formikProps.setFieldValue(name, value);
    setContactName(value);
  };

  const onCTAClick = () => {
    trackWithSegment({
      objectName: 'Contact Details',
      actionName: 'Next Clicked',
      location: SCREEN_NAME[STEPS.CONTACT_INFO],
    });
    setStep((step) => step + 1);
    setIsLoading(false);
  };
  const noop = () => {};
  return (
    <Formik initialValues={{}} validationSchema={contactInfoSchema} onSubmit={noop}>
      {(formikProps) => (
        <StyledForm onSubmit={(e) => e.preventDefault()} onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Enter contact details</StyledTitle>
            <StyledSubtitle />

            <StyledInputWrapper>
              <TextInput
                width="auto"
                label="Your Name"
                name="contactName"
                autoFocus
                placeholder="Enter your name"
                value={formikProps.values.contactName}
                onChange={({ name, value }) => {
                  onContactChange(formikProps, name, value);
                }}
                validationState={formikProps.errors.contactName ? 'error' : false}
                errorText={formikProps.errors.contactName}
              />
            </StyledInputWrapper>
          </StyledStepWrapper>
          <StepFooter
            ctaText="Next"
            onClick={onCTAClick}
            isLoading={isLoading}
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.touched)}
          />
        </StyledForm>
      )}
    </Formik>
  );
};

export default ContactInfo;

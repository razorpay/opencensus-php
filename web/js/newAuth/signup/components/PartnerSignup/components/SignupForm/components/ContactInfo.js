import React, { useState, useEffect } from 'react';
import { TextInput } from '@razorpay/blade/components';
import { SCREEN_NAME, STEPS, contactInfoSchema } from 'newAuth/signup/Constants';
import { Formik } from 'formik';
import StepFooter from './StepFooter';
import { trackWithSegment } from 'newAuth/trackEvents';
import isEmpty from '@universe/utils/isEmpty';
import { StyledStepWrapper, StyledTitle, StyledInputWrapper } from './styled';

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
        <form onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Enter contact details</StyledTitle>

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
        </form>
      )}
    </Formik>
  );
};

export default ContactInfo;

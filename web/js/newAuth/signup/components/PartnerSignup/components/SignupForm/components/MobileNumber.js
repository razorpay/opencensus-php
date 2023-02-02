import React, { useState } from 'react';
import { Formik } from 'formik';
import StepFooter from './StepFooter';
import ErrorModal from './ErrorScreens/ErrorModal';
import { TextInput } from '@razorpay/blade/components';
import { registerMobileOTP } from 'newAuth/signup/components/PartnerSignup/components/api';
import { redirectToLogIn } from 'newAuth/utils';
import { SCREEN_NAME, STEPS, mobileNumberSchema } from 'newAuth/signup/Constants';
import whatsappLogo from 'assets/app-store/partner-logos/whatsapp.png';
import { trackWithSegment } from 'newAuth/trackEvents';
import isEmpty from '@universe/utils/isEmpty';
import { BottomSheet } from 'react-spring-bottom-sheet';
import {
  StyledStepWrapper,
  StyledTitle,
  StyledSubtitle,
  StyledInputWrapper,
  StyledOptInCheckbox,
  StyledIconWrap,
  StyledCheckboxWrapper,
} from './styled';
import { isMobileAndTablet } from 'common/utils/rzp-utils';

const MobileNumber = ({
  setMobileNumber,
  setStep,
  isSendWhatsapp,
  setIsSendWhatsapp,
  setOtpVerifyToken,
  openModal,
  closeModal,
  showNotification,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);

  const onCTAClick = (formikProps) => {
    setIsLoading(true);
    trackWithSegment({
      objectName: 'Get Started',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.MOBILE_NUMBER],
    });
    return registerMobileOTP(formikProps.values.mobileNumber)
      .then(({ data }) => {
        setIsLoading(false);
        setOtpVerifyToken(data?.token);
        setStep((step) => step + 1);
      })
      .catch((err) => {
        setIsLoading(false);
        const error_code = err.errors?.[0].internal_error_code;
        if (error_code === 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS') {
          if (isMobileAndTablet()) {
            setIsOpen(true);
          } else
            openModal({
              size: 'signup-info',
              component: (
                <ErrorModal
                  title="Mobile Number is already Registered"
                  description="This mobile number is already registered. you can either Log in to continue to your account or Try signing-up with another mobile number"
                  closeModal={closeModal}
                  primaryLabel="Log In"
                  primaryButtonClick={redirectToLogIn}
                  secondaryLabel="Try another way"
                  secondaryButtonClick={closeModal}
                />
              ),
            });
        } else
          showNotification({
            type: 'error',
            message: err.errors?.[0] || 'Please try again',
          });
      });
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

  const onWhatsappCheck = () => {
    trackWithSegment({
      objectName: 'WA Update Option',
      actionName: 'Clicked',
      location: 'Mobile Number',
      properties: {
        whatsappSelectDeselectFlag: isSendWhatsapp,
      },
    });
    setIsSendWhatsapp(!isSendWhatsapp);
  };
  const noop = () => {};

  return (
    <Formik initialValues={{}} validationSchema={mobileNumberSchema} onSubmit={noop}>
      {(formikProps) => (
        <form onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Sign up As Partners!</StyledTitle>
            <StyledSubtitle>
              Enter a valid mobile number on which you can receive OTP
            </StyledSubtitle>

            <StyledInputWrapper>
              <TextInput
                autoFocus
                label="Mobile Number"
                name="mobileNumber"
                placeholder="Enter mobile number"
                helpText="Your mobile number"
                errorText={formikProps.errors.mobileNumber}
                value={formikProps.values.mobileNumber}
                maxCharacters={10}
                type="telephone"
                width="auto"
                onChange={({ name, value }) => {
                  onMobileChange(formikProps, name, value);
                }}
                validationState={formikProps.errors.mobileNumber ? 'error' : false}
              />
            </StyledInputWrapper>

            <StyledCheckboxWrapper>
              <StyledOptInCheckbox
                labelPosition="top"
                size="medium"
                onChange={onWhatsappCheck}
                isChecked={isSendWhatsapp}
              >
                Send updates on WhatsApp <StyledIconWrap src={whatsappLogo} />
              </StyledOptInCheckbox>
            </StyledCheckboxWrapper>
          </StyledStepWrapper>
          <StepFooter
            isLoading={isLoading}
            ctaText="Get Started"
            onClick={() => onCTAClick(formikProps)}
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.values.mobileNumber)}
          />
          <BottomSheet open={isOpen}>
            <ErrorModal
              title="Mobile Number is already Registered"
              description="This mobile number is already registered. you can either Log in to continue to your account or Try signing-up with another mobile number"
              closeModal={closeModal}
              primaryLabel="Log In"
              primaryButtonClick={redirectToLogIn}
              secondaryLabel="Try another way"
              secondaryButtonClick={closeModal}
            />
          </BottomSheet>
        </form>
      )}
    </Formik>
  );
};

export default MobileNumber;

import { TextInput } from '@razorpay/blade/components';
import whatsappLogo from 'assets/app-store/partner-logos/whatsapp.png';
import { Modal, ModalBody } from 'common/components/Modal';
import { Formik } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { registerMobileOTP } from 'newAuth/signup/components/PartnerSignup/components/api';
import { mobileNumberSchema, SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import { redirectToLogIn } from 'newAuth/utils';
import React, { useEffect, useState } from 'react';
import ErrorModal from './ErrorScreens/ErrorModal';
import StepFooter from './StepFooter';

import { isMobileAndTablet } from 'common/utils/rzp-utils';
import {
  StyledCheckboxWrapper,
  StyledForm,
  StyledIconWrap,
  StyledInputWrapper,
  StyledOptInCheckbox,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

const MobileNumber = ({
  setMobileNumber,
  setStep,
  isSendWhatsapp,
  setIsSendWhatsapp,
  setOtpVerifyToken,
  closeModal,
  showNotification,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [showError, setShowError] = useState(false);
  const [errorCode, setErrorCode] = useState(null);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Signup',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.MOBILE_NUMBER],
    });
  }, []);

  const onCTAClick = (formikProps) => {
    setIsLoading(true);
    trackWithSegment({
      objectName: 'Get Started',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.MOBILE_NUMBER],
      properties: {
        whatsappDeselectFlag: isSendWhatsapp,
      },
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
        trackWithSegment({
          objectName: 'Form Field Validation',
          actionName: 'Error',
          location: SCREEN_NAME[STEPS.MOBILE_NUMBER],
          properties: {
            errorMessage: error_code,
            fieldLabel: 'Phone Number',
            funnelStage: 'L1',
          },
        });
        if (error_code === 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS') {
          setErrorCode('mobile_already_exists');
          setShowError(true);
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
        whatsappDeselectFlag: isSendWhatsapp,
      },
    });
    setIsSendWhatsapp(!isSendWhatsapp);
  };
  const noop = () => {};

  const onModalClose = () => {
    setShowError(false);
    closeModal();
  };
  return (
    <Formik initialValues={{}} validationSchema={mobileNumberSchema} onSubmit={noop}>
      {(formikProps) => (
        <StyledForm onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Sign up as a Partner!</StyledTitle>
            <StyledSubtitle>
              Enter a valid mobile number on which you can receive OTP
            </StyledSubtitle>

            <StyledInputWrapper>
              <TextInput
                autoFocus
                label="Phone Number"
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
          <Modal
            bottomsheet={isMobileAndTablet()}
            isOpen={showError}
            bottomSheetHeight="250px"
            onClose={onModalClose}
          >
            <ModalBody>
              {errorCode === 'mobile_already_exists' && (
                <ErrorModal
                  title="Mobile Number is already Registered"
                  description="This mobile number is already registered. you can either Log in to continue to your account or Try signing-up with another mobile number"
                  primaryLabel="Log In"
                  primaryButtonClick={redirectToLogIn}
                  secondaryLabel="Try another way"
                  secondaryButtonClick={onModalClose}
                />
              )}
            </ModalBody>
          </Modal>
        </StyledForm>
      )}
    </Formik>
  );
};

export default MobileNumber;

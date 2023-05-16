import React, { useEffect, useState } from 'react';
import { Button, TextInput } from '@razorpay/blade/components';
import { Formik } from 'formik';
import { isEmpty } from 'lodash';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { Modal, ModalBody } from 'common/components/Modal';
import { redirectToLogIn } from 'newAuth/utils';
import { sendEmailOTP } from './api';
import { StyledCongratsFormWrapper, StyledCongratsInputWrapper } from './styled';
import { trackWithSegment } from 'newAuth/trackEvents';
import {
  SCREEN_NAME,
  STEPS,
  congratsFormSchema,
  EMAIL_ALREADY_TAKEN_ERROR_DESC,
} from 'newAuth/signup/Constants';
import ErrorModal from 'newAuth/signup/components/PartnerSignup/components/SignupForm/components/ErrorScreens/ErrorModal';
import imageStarStroke from 'assets/partner-dashboard/star-stroke.png';

const CongratsForm = ({
  contactEmail: initialEmail,
  setContactEmail,
  setEmailToken,
  setStep,
  closeModal,
  showNotification,
  onboardAllAsResellerFlag,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [emailError, setEmailError] = useState(null);
  const [showError, setShowError] = useState(false);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Partner Welcome Screen',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.CONGRATS],
      properties: {
        onboardAllAsResellerFlag,
      },
    });
  }, []);

  const onModalClose = () => {
    setShowError(false);
    closeModal();
  };

  const onCTAClick = (label, submittedEmail = null) => {
    if (label === 'submit') {
      setIsLoading(true);
      trackWithSegment({
        objectName: 'Email Submit',
        actionName: 'Clicked',
        location: SCREEN_NAME[STEPS.CONGRATS],
        properties: {
          onboardAllAsResellerFlag,
        },
      });
      const payload = { email: submittedEmail };

      sendEmailOTP(payload)
        .then((res) => {
          setIsLoading(false);
          if (res?.data && res?.data?.token) {
            setStep(STEPS.EMAIL_VERIFICATION);
            setEmailToken(res.data.token);
          }
        })
        .catch((err) => {
          setIsLoading(false);
          const error_description = err.errors?.[0];
          trackWithSegment({
            objectName: 'Form Field Validation',
            actionName: 'Error',
            location: SCREEN_NAME[STEPS.CONGRATS],
            properties: {
              errorMessage: error_description,
              fieldLabel: 'Contact Email',
              funnelStage: 'L1',
            },
          });
          if (error_description === EMAIL_ALREADY_TAKEN_ERROR_DESC) {
            setEmailError('email_already_taken');
            setShowError(true);
          } else
            showNotification({
              type: 'error',
              message: error_description || 'Please try again',
            });
        });
    } else if (label === 'addLater') {
      trackWithSegment({
        objectName: 'Add Later Option',
        actionName: 'Clicked',
        location: SCREEN_NAME[STEPS.CONGRATS],
        properties: {
          onboardAllAsResellerFlag,
        },
      });
      window.location = '/app/partners';
    } else if (label == 'goToDashboard') {
      trackWithSegment({
        objectName: 'Go To Dashboard',
        actionName: 'Clicked',
        location: SCREEN_NAME[STEPS.CONGRATS],
        properties: {
          onboardAllAsResellerFlag,
        },
      });
      window.location = '/app/partners';
    }
  };

  const noop = () => {};
  return (
    <Formik
      initialValues={{
        contactEmail: initialEmail,
      }}
      validationSchema={congratsFormSchema}
      onSubmit={noop}
    >
      {(formikProps) => (
        <form onChange={formikProps.handleChange}>
          <StyledCongratsFormWrapper>
            <div className="congrats-form congrats-form-1">
              <img src={imageStarStroke} alt="success" className="star-logo" />
              <ul>
                <li>Your can now start refering users from the Razorpay account.</li>
                <li>Refer popular domestic payment methods</li>
              </ul>
              <div className="mt-20">
                <Button size="medium" isFullWidth block onClick={() => onCTAClick('goToDashboard')}>
                  Go to Dashboard
                </Button>
              </div>
            </div>
            <div className="congrats-form congrats-form-2">
              <div className="form2-heading">Enter email to get all notifications</div>
              <div className="form2-sub-heading">
                Please share your Email with us, so that we can send you all important
                communication.
              </div>
              <StyledCongratsInputWrapper>
                <TextInput
                  name="contactEmail"
                  width="auto"
                  label="Your email (optional)"
                  autoFocus
                  placeholder="Email ID"
                  type="email"
                  value={formikProps.values.contactEmail}
                  onChange={({ name, value }) => {
                    setContactEmail(value);
                    formikProps.setFieldTouched(name);
                    formikProps.setFieldValue(name, value);
                    trackWithSegment({
                      objectName: 'Email Details',
                      actionName: 'Initiated',
                      location: SCREEN_NAME[STEPS.CONGRATS],
                      properties: {
                        onboardAllAsResellerFlag,
                      },
                    });
                  }}
                  validationState={formikProps.errors.contactEmail ? 'error' : false}
                  errorText={formikProps.errors.contactEmail}
                />
              </StyledCongratsInputWrapper>
              <div className="email-btn-wrap">
                <div
                  className="sec-btn-wrap"
                  onClick={() => {
                    onCTAClick('addLater');
                  }}
                >
                  Add Later
                </div>
                <div className="primary-btn-wrap">
                  <Button
                    size="medium"
                    isFullWidth
                    isLoading={isLoading}
                    isDisabled={
                      !isEmpty(formikProps.errors) || isEmpty(formikProps.values.contactEmail)
                    }
                    onClick={() => {
                      onCTAClick('submit', formikProps.values.contactEmail);
                    }}
                  >
                    Submit
                  </Button>
                </div>
              </div>
            </div>
            <div className="congrats-note">
              To receive payments to your bank account & extend your payment limit, complete KYC
            </div>

            <Modal
              bottomsheet={isMobileAndTablet()}
              bottomSheetHeight="250px"
              isOpen={showError}
              onClose={onModalClose}
            >
              <ModalBody>
                {emailError === 'email_already_taken' && (
                  <ErrorModal
                    title="Email is already registered"
                    description="This Email ID is already registered. you can either Log in to continue to your account or Try verifying another email ID"
                    primaryLabel="Log In"
                    primaryButtonClick={redirectToLogIn}
                    secondaryLabel="Try another ID"
                    secondaryButtonClick={onModalClose}
                  />
                )}
              </ModalBody>
            </Modal>
          </StyledCongratsFormWrapper>
        </form>
      )}
    </Formik>
  );
};
export default CongratsForm;

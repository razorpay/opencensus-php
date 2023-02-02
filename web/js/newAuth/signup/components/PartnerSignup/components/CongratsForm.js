import React from 'react';
import { Button, TextInput } from '@razorpay/blade/components';
import { STEPS, congratsFormSchema } from 'newAuth/signup/Constants';
import { Formik } from 'formik';
import { sendEmailOTP } from './api';
import imageStarStroke from 'assets/partner-dashboard/star-stroke.png';
import { StyledCongratsFormWrapper, StyledCongratsInputWrapper } from './styled';

const CongratsForm = ({
  contactEmail: initialEmail,
  setContactEmail,
  setStep,
  showNotification,
}) => {
  const onCTAClick = (label, submittedEmail = null) => {
    if (label === 'submit') {
      const payload = { email: submittedEmail };

      sendEmailOTP(payload)
        .then((res) => {
          if (res?.data && res?.data?.token) {
            setStep(STEPS.EMAIL_VERIFICATION);
          }
        })
        .catch((err) => {
          if (err && Array.isArray(err.errors)) {
            showNotification({
              type: 'error',
              message: err,
            });
          } else {
            showNotification({
              type: 'error',
              message: 'Please try again',
            });
          }
        });
    } else if (label === 'addLater' || label == 'goToDashboard') {
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
                <li>Use popular domestic payment methods</li>
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
                Please share your Email with us, so that we can send your all important
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
                  }}
                  validationState={formikProps.errors.otp ? 'error' : false}
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
          </StyledCongratsFormWrapper>
        </form>
      )}
    </Formik>
  );
};
export default CongratsForm;

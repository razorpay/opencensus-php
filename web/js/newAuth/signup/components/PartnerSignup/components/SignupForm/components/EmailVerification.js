import React, { useState } from 'react';
import { Formik } from 'formik';
import { TextInput, Text } from '@razorpay/blade/components';

import isEmpty from '@universe/utils/isEmpty';
import useOTPCountdownTimer from 'newAuth/utils/useOtpCountdownTimer';
import { verifyEmailOTP } from 'newAuth/signup/components/PartnerSignup/components/api';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  SCREEN_NAME,
  STEPS,
  EMAIL_RESEND_OTP_COUNTDOWN,
  EMAIL_MAX_OTP_TRIES,
  EMAIL_INCORRECT_OTP_ERROR_DESC,
  emailVerificationSchema,
} from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';

import StepFooter from './StepFooter';
import ErrorScreen from './ErrorScreen';
import { StyledStepWrapper, StyledTitle, StyledSubtitle, StyledInputWrapper } from './styled';
import imageUnableToSendOTP from 'assets/partner-dashboard/error-unable-to-send-otp.svg';
import imageTooManyAttempts from 'assets/partner-dashboard/error-too-many-attempts.svg';

const EmailVerification = ({ contactEmail, setStep, setShowHeader }) => {
  const [isLoading, setIsLoading] = useState(false);
  const [emailToken, setEmailToken] = useState(null);

  const { timerText, isTimerRunning, resetTimer } = useOTPCountdownTimer(
    EMAIL_RESEND_OTP_COUNTDOWN,
  );
  const [otpTriesLeft, setOtpTriesLeft] = useState(EMAIL_MAX_OTP_TRIES);
  const [otpError, setOTPError] = useState(null);
  const onCTAClick = (emailOtp) => {
    trackWithSegment({
      objectName: 'Get Started',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.EMAIL_VERIFICATION],
    });
    const payload = emailToken ? { otp: emailOtp, token: emailToken } : { otp: emailOtp };

    return verifyEmailOTP(payload)
      .then((res) => {
        if (res?.data?.user?.email_verified) {
          setIsLoading(false);
          window.location = '/app/partners';
        }
      })
      .catch((err) => {
        setIsLoading(false);
        const error_description = err.errors?.[0];
        if (err.status_code === 400 || error_description === EMAIL_INCORRECT_OTP_ERROR_DESC)
          setOTPError('wrong_otp');
        else {
          setOTPError('server_error');
          setShowHeader(false);
        }
      });
  };

  const resendOTP = () => {
    const payload = emailToken ? { email: contactEmail, emailToken } : { email: contactEmail };
    resetTimer();
    setOtpTriesLeft(otpTriesLeft - 1);
    setIsLoading(true);
    return merchantFetch({
      url: 'merchant/activation/otp/send',
      method: 'POST',
      data: payload,
      mode: 'live',
    })
      .then((res) => {
        if (res?.data?.token) {
          setIsLoading(false);
          setOTPError(null);
          setEmailToken(res.data.token);
          window.location = '/app/partners';
        }
      })
      .catch(() => {
        setIsLoading(false);
        setOTPError('server_error');
        setShowHeader(false);
      });
  };

  const onErrorCTAClick = () => {
    setStep((step) => step - 1);
    setShowHeader(true);
  };

  if (otpError === 'server_error') {
    return (
      <ErrorScreen
        imageURL={imageUnableToSendOTP}
        title="Enter contact details"
        description="We are facing delays in triggering OTP, kindly wait or try again later"
        buttonLabel="Try again"
        onButtonClick={onErrorCTAClick}
      />
    );
  }

  if (otpTriesLeft === 0) {
    return (
      <ErrorScreen
        imageURL={imageTooManyAttempts}
        title="Too many attempts"
        description="Entered OTP is incorrect. Kindly try another number or try again after sometime"
        buttonLabel="Try again"
        onButtonClick={onErrorCTAClick}
      />
    );
  }

  const noop = () => {};
  return (
    <Formik initialValues={{}} validationSchema={emailVerificationSchema} onSubmit={noop}>
      {(formikProps) => (
        <form onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Verify your Email</StyledTitle>
            <StyledSubtitle>An email with an OTP has been sent to {contactEmail}</StyledSubtitle>
            <StyledInputWrapper>
              <TextInput
                width="auto"
                label="Enter OTP"
                name="emailOtp"
                autoFocus
                placeholder="••••••"
                type="telephone"
                maxCharacters={6}
                value={formikProps.values.emailOtp}
                errorText={
                  otpError === 'wrong_otp'
                    ? 'Entered OTP is incorrect. Kindly resubmit or regenerate the OTP'
                    : formikProps.errors.otp
                }
                validationState={formikProps.errors.emailOtp ? 'error' : false}
              />
            </StyledInputWrapper>
            {isTimerRunning && !isLoading ? (
              <Text size="small" color="shade.950">
                <div className="resend-otp text-success">Verification Email Successfully Sent</div>
                <div className="resend-otp help-text">Resend OTP after {timerText}</div>
              </Text>
            ) : (
              <Text size="small" color="shade.950">
                {otpTriesLeft > 0 && (
                  <div className="resend-otp" onClick={resendOTP}>
                    <div className={`resend-otp-btn ${isLoading ? 'resend-otp-disabled' : ''}`}>
                      Resend Verification Email
                    </div>
                    {otpTriesLeft < EMAIL_MAX_OTP_TRIES && (
                      <div className="resend-otp help-text">{otpTriesLeft} more attempts left</div>
                    )}
                  </div>
                )}
              </Text>
            )}
          </StyledStepWrapper>
          <StepFooter
            ctaText="Verify"
            isLoading={isLoading}
            onClick={() => onCTAClick(formikProps.values.emailOtp)}
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.touched)}
          />
        </form>
      )}
    </Formik>
  );
};

export default EmailVerification;

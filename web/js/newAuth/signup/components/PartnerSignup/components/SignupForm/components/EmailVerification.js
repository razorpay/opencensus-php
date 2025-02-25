import { Text, TextInput } from '@razorpay/blade/components';
import { Formik } from 'formik';
import React, { useState } from 'react';

import isEmpty from 'lodash/isEmpty';
import { newAuthFetch } from '@dashboards/newAuth/utils/newAuthFetch';
import { verifyEmailOTP } from 'newAuth/signup/components/PartnerSignup/components/api';
import {
  emailVerificationSchema,
  EMAIL_INCORRECT_OTP_ERROR_DESC,
  EMAIL_MAX_OTP_TRIES,
  EMAIL_RESEND_OTP_COUNTDOWN,
  SCREEN_NAME,
  STEPS,
} from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import useOTPCountdownTimer from 'newAuth/utils/useOtpCountdownTimer';

import imageTooManyAttempts from 'assets/partner-dashboard/error-too-many-attempts.svg';
import imageUnableToSendOTP from 'assets/partner-dashboard/error-unable-to-send-otp.svg';
import ErrorScreen from './ErrorScreen';
import StepFooter from './StepFooter';
import {
  StyledForm,
  StyledInputWrapper,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

const EmailVerification = ({ emailToken, contactEmail, setEmailToken, setStep, setShowHeader }) => {
  const [isLoading, setIsLoading] = useState(false);

  const { timerText, isTimerRunning, resetTimer } = useOTPCountdownTimer(
    EMAIL_RESEND_OTP_COUNTDOWN,
  );
  const [otpTriesLeft, setOtpTriesLeft] = useState(EMAIL_MAX_OTP_TRIES);
  const [otpError, setOTPError] = useState(null);
  const onCTAClick = (emailOtp) => {
    trackWithSegment({
      objectName: 'Email Verify OTP',
      actionName: 'Entered',
      location: SCREEN_NAME[STEPS.EMAIL_VERIFICATION],
    });
    const payload = emailToken ? { otp: emailOtp, token: emailToken } : { otp: emailOtp };
    setIsLoading(true);

    return verifyEmailOTP(payload)
      .then((res) => {
        if (res?.data?.user?.email_verified) {
          setIsLoading(false);
          window.location = '/app/partners';
        }
        trackWithSegment({
          objectName: 'Email Verify OTP',
          actionName: 'Result',
          location: SCREEN_NAME[STEPS.EMAIL_VERIFICATION],
          properties: {
            status: res?.data?.success,
          },
        });
      })
      .catch((err) => {
        setIsLoading(false);
        const errorDescription = err.errors?.[0];
        trackWithSegment({
          objectName: 'Email Verify OTP',
          actionName: 'Result',
          location: SCREEN_NAME[STEPS.EMAIL_VERIFICATION],
          properties: {
            status: false,
            errorMessage: errorDescription,
          },
        });
        if (err.status_code === 400 || errorDescription === EMAIL_INCORRECT_OTP_ERROR_DESC)
          setOTPError('wrong_otp');
        else {
          setOTPError('server_error');
          setShowHeader(false);
        }
      });
  };

  const resendOTP = () => {
    trackWithSegment({
      objectName: 'Resend OTP CTA',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.EMAIL_VERIFICATION],
    });

    const payload = emailToken ? { email: contactEmail, emailToken } : { email: contactEmail };
    resetTimer();
    setOtpTriesLeft(otpTriesLeft - 1);
    setIsLoading(true);
    return newAuthFetch({
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
        }
      })
      .catch(() => {
        setIsLoading(false);
        setOTPError('server_error');
        setShowHeader(false);
      });
  };

  const changeEmail = () => {
    setStep(STEPS.CONGRATS);
    setShowHeader(true);
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
        <StyledForm onSubmit={(e) => e.preventDefault()} onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Verify your Email</StyledTitle>
            <StyledSubtitle>
              An email with an OTP has been sent to {contactEmail} &nbsp;
              <span className="change-text" onClick={changeEmail}>
                Change
              </span>
            </StyledSubtitle>
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
                    : formikProps.errors.emailOtp
                }
                validationState={
                  formikProps.errors.emailOtp || otpError === 'wrong_otp' ? 'error' : false
                }
              />
            </StyledInputWrapper>
            {isTimerRunning && !isLoading ? (
              <Text size="small" color="shade.950">
                {otpError !== 'wrong_otp' && (
                  <div className="resend-otp text-success">
                    Verification Email Successfully Sent
                  </div>
                )}
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
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.values.emailOtp)}
          />
        </StyledForm>
      )}
    </Formik>
  );
};

export default EmailVerification;

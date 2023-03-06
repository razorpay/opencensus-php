import React, { useState } from 'react';
import { TextInput, Text } from '@razorpay/blade/components';
import { Formik } from 'formik';
import StepFooter from './StepFooter';
import {
  StyledStepWrapper,
  StyledTitle,
  StyledSubtitle,
  StyledInputWrapper,
  StyledForm,
} from './styled';
import {
  STEPS,
  SCREEN_NAME,
  MOBILE_MAX_OTP_TRIES,
  MOBILE_RESEND_OTP_COUNTDOWN,
  MOBILE_INCORRECT_OTP_ERROR_DESC,
  mobileVerificationSchema,
} from 'newAuth/signup/Constants';
import {
  verifyMobileOTP,
  registerMobileOTP,
  userWhatsappOptIn,
} from 'newAuth/signup/components/PartnerSignup/components/api';
import { trackWithSegment } from 'newAuth/trackEvents';
import useOTPCountdownTimer from 'newAuth/utils/useOtpCountdownTimer';
import isEmpty from '@universe/utils/isEmpty';
import ErrorScreen from './ErrorScreen';
import imageUnableToSendOTP from 'assets/partner-dashboard/error-unable-to-send-otp.svg';
import imageTooManyAttempts from 'assets/partner-dashboard/error-too-many-attempts.svg';

const MobileVerification = ({
  mobileNumber,
  setStep,
  otpVerifyToken,
  setOtpVerifyToken,
  isSendWhatsapp,
  setShowHeader,
}) => {
  const { timerText, isTimerRunning, resetTimer } = useOTPCountdownTimer(
    MOBILE_RESEND_OTP_COUNTDOWN,
  );
  const [otpTriesLeft, setOtpTriesLeft] = useState(MOBILE_MAX_OTP_TRIES);
  const [otpError, setOTPError] = useState(null);
  const [isLoading, setIsLoading] = useState(false);

  const onCTAClick = (otp) => {
    trackWithSegment({
      objectName: 'Sign up Verify OTP',
      actionName: 'Entered',
      location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
    });
    setIsLoading(true);
    return verifyMobileOTP({
      captcha: 'Faked', // TODO: in a separate PR
      contact_mobile: mobileNumber,
      otp,
      partner_intent: true,
      token: otpVerifyToken,
    })
      .then(({ success }) => {
        if (success) {
          trackWithSegment({
            objectName: 'Sign up Verify OTP',
            actionName: 'Result',
            location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
            properties: {
              status: success,
            },
          });
          if (isSendWhatsapp) {
            userWhatsappOptIn();
          }
          setStep((step) => step + 1);
        }
        setIsLoading(false);
      })
      .catch((err) => {
        setIsLoading(false);
        const errorDescription = err.errors?.[0];
        trackWithSegment({
          objectName: 'Sign up Verify OTP',
          actionName: 'Result',
          location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
          properties: {
            status: 'Error',
            message: errorDescription,
          },
        });
        if (err.status_code === 400 || errorDescription === MOBILE_INCORRECT_OTP_ERROR_DESC)
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
      location: 'Mobile Number OTP Verify',
    });
    resetTimer();
    setOtpTriesLeft(otpTriesLeft - 1);
    setIsLoading(true);
    return registerMobileOTP(mobileNumber)
      .then(({ data }) => {
        setIsLoading(false);
        setOTPError(null);
        setOtpVerifyToken(data?.token);
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

  return (
    <Formik initialValues={{}} validationSchema={mobileVerificationSchema} onSubmit={() => {}}>
      {(formikProps) => (
        <StyledForm onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle>Verify your Mobile</StyledTitle>
            <StyledSubtitle>
              A text message with an OTP has been sent to{' '}
              <span className="mobile-num">+91&nbsp;{mobileNumber}</span>&nbsp;
              <span
                className="change-text"
                onClick={() => {
                  setStep(STEPS.MOBILE_NUMBER);
                  setShowHeader(true);
                }}
              >
                Change
              </span>
            </StyledSubtitle>

            <StyledInputWrapper>
              <TextInput
                width="auto"
                label="Enter OTP"
                name="otp"
                autoFocus
                placeholder="••••••"
                type="telephone"
                maxCharacters={6}
                value={formikProps.values.otp}
                errorText={
                  otpError === 'wrong_otp'
                    ? 'Entered OTP is incorrect. Kindly try another number or try again after sometime'
                    : formikProps.errors.otp
                }
                validationState={
                  formikProps.errors.otp || otpError === 'wrong_otp' ? 'error' : false
                }
              />
            </StyledInputWrapper>

            {isTimerRunning && !isLoading ? (
              <Text size="small" color="shade.950">
                {otpError !== 'wrong_otp' && (
                  <div className="resend-otp text-success">OTP Successfully Sent</div>
                )}
                <div className="resend-otp help-text">Resend OTP after {timerText}</div>
              </Text>
            ) : (
              <Text size="small" color="shade.950">
                {otpTriesLeft > 0 && (
                  <div className="resend-otp" onClick={resendOTP}>
                    <div className={`resend-otp-btn ${isLoading ? 'resend-otp-disabled' : ''}`}>
                      Resend OTP
                    </div>
                    {otpTriesLeft < MOBILE_MAX_OTP_TRIES && (
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
            onClick={() => onCTAClick(formikProps.values.otp)}
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.values.otp)}
          />
        </StyledForm>
      )}
    </Formik>
  );
};

export default MobileVerification;

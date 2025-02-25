import { Text, TextInput } from '@razorpay/blade/components';
import imageTooManyAttempts from 'assets/partner-dashboard/error-too-many-attempts.svg';
import imageUnableToSendOTP from 'assets/partner-dashboard/error-unable-to-send-otp.svg';
import { Formik, useFormikContext } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { setMerchantID } from '@dashboards/payments/reducers/newAuth/actions';
import {
  registerMobileOTP,
  userWhatsappOptIn,
  verifyMobileOTP,
} from 'newAuth/signup/components/PartnerSignup/components/api';
import {
  CAPTCHA_FAILED,
  LOW_CAPTCHA_SCORE,
  mobileVerificationSchema,
  MOBILE_INCORRECT_OTP_ERROR_DESC,
  MOBILE_MAX_OTP_TRIES,
  MOBILE_RESEND_OTP_COUNTDOWN,
  SCREEN_NAME,
  STEPS,
  V3,
} from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import useOTPCountdownTimer from 'newAuth/utils/useOtpCountdownTimer';
import React, { useRef, useState } from 'react';
import ReCaptchaV2 from 'react-google-recaptcha';
import { useGoogleReCaptcha as useReCaptchaV3 } from 'react-google-recaptcha-v3';
import { connect } from 'react-redux';
import ErrorScreen from './ErrorScreen';
import StepFooter from './StepFooter';
import {
  StyledForm,
  StyledInputWrapper,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

const Captcha = ({ invisibleCaptchaRef }) => {
  const { setFieldValue } = useFormikContext();

  const handleInvisibleCaptchaChange = (value) => {
    setFieldValue('captcha', value);
  };

  return (
    <ReCaptchaV2
      ref={invisibleCaptchaRef}
      size="invisible"
      sitekey={window.INVISIBLE_CAPTCHA_SITE_KEY}
      onChange={handleInvisibleCaptchaChange}
    />
  );
};

const MobileVerification = ({
  mobileNumber,
  setStep,
  otpVerifyToken,
  setOtpVerifyToken,
  isSendWhatsapp,
  setShowHeader,
  setMerchantID,
}) => {
  const { timerText, isTimerRunning, resetTimer } = useOTPCountdownTimer(
    MOBILE_RESEND_OTP_COUNTDOWN,
  );
  const [otpTriesLeft, setOtpTriesLeft] = useState(MOBILE_MAX_OTP_TRIES);
  const [otpError, setOTPError] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const { executeRecaptcha: executeRecaptchaV3 } = useReCaptchaV3();

  // if captcha v3 returns Validation Failed, we set this to true
  const invisibleCaptchaRef = useRef(null);

  const triggerV2Captcha = () => {
    invisibleCaptchaRef.current.reset();
    invisibleCaptchaRef.current.execute();
  };

  const onCTAClick = async (otp) => {
    const captchaMode = V3;
    const token = await executeRecaptchaV3('signup');
    trackWithSegment({
      objectName: 'Sign up Verify OTP',
      actionName: 'Entered',
      location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
    });
    setIsLoading(true);
    return verifyMobileOTP(
      {
        captcha: token || 'Faked',
        contact_mobile: mobileNumber,
        otp,
        partner_intent: true,
        token: otpVerifyToken,
      },
      captchaMode,
    )
      .then(({ success, data }) => {
        if (success) {
          const merchantID = data?.id;
          setMerchantID(merchantID);
          trackWithSegment({
            objectName: 'Sign up Verify OTP',
            actionName: 'Result',
            location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
            properties: {
              status: success,
            },
          });

          trackWithSegment({
            objectName: 'Sign up Create Account',
            actionName: 'Result',
            location: SCREEN_NAME[STEPS.MOBILE_VERIFICATION],
            properties: {
              status: success,
              mid: merchantID,
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
        if (err.status_code === 400 && errorDescription === MOBILE_INCORRECT_OTP_ERROR_DESC)
          setOTPError('wrong_otp');
        else if (captchaMode === V3) {
          if (err.message === LOW_CAPTCHA_SCORE || err.message === CAPTCHA_FAILED) {
            // When v3 returns with low score or fails, we can't be sure whether the user is bot or human.
            // Thus, we trigger v2 to help with the final differentiation.
            triggerV2Captcha();
          }
        } else {
          setOTPError('server_error');
          setShowHeader(false);
        }

        if (invisibleCaptchaRef.current) {
          invisibleCaptchaRef.current.reset();
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
        <StyledForm onSubmit={(e) => e.preventDefault()} onChange={formikProps.handleChange}>
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
            <Captcha invisibleCaptchaRef={invisibleCaptchaRef} />
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

export default connect(null, { setMerchantID })(MobileVerification);

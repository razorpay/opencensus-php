import React, { useState, useEffect } from 'react';
import { OtpInput } from 'common/new-ui/Input/OtpInput';
import { verifyOtp, sendOtp, addEmail, verifyEmail, OTPMETHOD } from './services';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { closeModal as fnCloseModal } from 'merchant_common/reducers/modals';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import RTracking from 'react-tracking';
import { AsyncBtn } from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const OTPModal = ({
  heading,
  email,
  otpMethod,
  otpAuthToken,
  onSubmit,
  onOTPCompleteCallback,
  phone,
  reset,
  screen,
  showNotification,
  closeModal,
  onClose,
}) => {
  const [token, setToken] = useState(null);
  const [otp, setOtp] = useState(null);
  const [wrongOtp, setWrongOtp] = useState(false);
  const [isComplete, setIsComplete] = useState(false);
  const [isVerifyingOtp, setIsVerifyingOtp] = useState(false);

  const handleOTPComplete = (otpInput) => {
    setWrongOtp(false);
    setIsComplete(true);
    setOtp(otpInput);
  };

  const handleOTPChange = (otpInput) => {
    setWrongOtp(false);
    if (otpInput.length < 6) {
      setIsComplete(false);
    }
  };

  const sendOtpOnMedium = () => {
    if (otpMethod === OTPMETHOD.PHONE) {
      sendOtp({
        action: 'second_factor_auth',
        medium: 'sms',
      })
        .then((res) => {
          if (res.success) {
            analyticsTrack({
              objectName: 'add email 2fa otp',
              actionName: 'sent',
              screen,
              properties: {
                result: 'Success',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            setToken(res.data.token);
          }
        })
        .catch((err) => {
          analyticsTrack({
            objectName: 'add email 2fa otp',
            actionName: 'sent',
            screen,
            properties: {
              result: 'Failure',
              failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          showNotification({
            type: 'error',
            message:
              err.errors && err.errors.length
                ? err.errors[0]
                : 'Some error occured. Please refresh',
          });
        });
    } else {
      addEmail({ email, otpAuthToken })
        .then((res) => {
          if (res.success) {
            // do something
          }
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err.errors[0],
          });
        });
    }
  };

  const onOTPComplete = () => {
    setIsVerifyingOtp(true);
    if (otpMethod === OTPMETHOD.PHONE) {
      const body = {
        otp,
        token,
      };

      verifyOtp(body)
        .then((res) => {
          setIsVerifyingOtp(false);
          if (res.success) {
            analyticsTrack({
              objectName: 'add email 2fa otp',
              actionName: 'verify',
              screen,
              properties: {
                result: 'Success',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            onSubmit({ token: res.data.otp_auth_token });
          }
        })
        .catch((err) => {
          analyticsTrack({
            objectName: 'add email 2fa otp',
            actionName: 'verify',
            screen,
            properties: {
              result: 'Failure',
              failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          setIsVerifyingOtp(false);
          setWrongOtp(true);
          showNotification({
            type: 'error',
            message:
              err.errors && err.errors.length
                ? err.errors[0]
                : 'Some error occured. Please refresh',
          });
        });
    } else {
      const body = {
        otp,
        email,
      };

      verifyEmail(body)
        .then((res) => {
          setIsVerifyingOtp(false);
          if (res.success) {
            analyticsTrack({
              objectName: 'add email',
              actionName: 'verify',
              screen,
              properties: {
                result: 'Success',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            onSubmit();
          }
        })
        .catch((err) => {
          analyticsTrack({
            objectName: 'add email',
            actionName: 'verify',
            screen,
            properties: {
              result: 'Failure',
              failureMessage: err.errors && err.errors.length ? `${err.errors[0]}` : null,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          setIsVerifyingOtp(false);
          setWrongOtp(true);
          showNotification({
            type: 'error',
            message:
              err.errors && err.errors.length
                ? err.errors[0]
                : 'Some error occured. Please refresh',
          });
        });
    }

    if (onOTPCompleteCallback) {
      onOTPCompleteCallback();
    }
  };

  const onPopupClose = () => {
    if (onClose) onClose();
    closeModal();
  };

  useEffect(() => {
    if (otpMethod === OTPMETHOD.PHONE) sendOtpOnMedium();
  }, []);

  return (
    <div className="add-email-modal-content">
      <div className="merchant-heading mb-20">
        {heading
          ? heading
          : otpMethod === OTPMETHOD.EMAIL
          ? 'Confirm your Email'
          : 'Verify your Phone Number'}
        <button type="button" className="close" onClick={onPopupClose}>
          <i className="i i-close" />
        </button>
      </div>
      <div className="add-email-modal-otp-section">
        <div className="otp-section-message">
          {otpMethod === OTPMETHOD.EMAIL && (
            <p>
              An email with a 6-digit OTP has been sent to your new email address{' '}
              <strong>{email}</strong>
            </p>
          )}
          {otpMethod === OTPMETHOD.PHONE && (
            <p>
              A SMS with a 6-digit OTP has been sent to your registered phone number{' '}
              <strong>+91-{phone}</strong>
            </p>
          )}
          {reset && (
            <>
              {'['}
              <AsyncBtn.Transparent onClick={reset} className="m-l" showLoader={false}>
                Change
              </AsyncBtn.Transparent>
              ,{']'}
            </>
          )}
        </div>
        <OtpInput
          onComplete={handleOTPComplete}
          onChange={handleOTPChange}
          wrong={wrongOtp}
          autoFocus={false}
        />
        <div className="resend-link">
          {`Didn't receive the ${otpMethod === OTPMETHOD.EMAIL ? 'e-mail' : 'SMS'}? `}
          <AsyncBtn.Transparent
            pendingState="Sending OTP..."
            onClick={sendOtpOnMedium}
            className="m-l"
            showLoader={false}
          >
            Resend
          </AsyncBtn.Transparent>
        </div>
      </div>
      <AsyncBtn.Primary
        type="submit"
        className="Button--full-width"
        onClick={onOTPComplete}
        disabled={wrongOtp || !isComplete || isVerifyingOtp}
      >
        {isVerifyingOtp ? 'Verifying...' : 'Confirm'}
      </AsyncBtn.Primary>
    </div>
  );
};

export default compose(
  connect((state) => ({ user: state.session.user }), {
    closeModal: fnCloseModal,
    showNotification: fnShowNotification,
  }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('OTPModal')),
)(OTPModal);

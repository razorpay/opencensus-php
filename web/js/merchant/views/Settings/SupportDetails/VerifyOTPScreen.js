import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { showNotification } from 'merchant_common/reducers/notifications';
import { createSupportDetail } from 'merchant/reducers/support_detail';
import {
  trackSupportDetailSubmitAction,
  trackSupportDetailPopupClose,
} from 'merchant/containers/Home/ga';
import { merchantFetch } from 'merchant/utils/ajax';
import { OtpInput } from 'common/new-ui/Input/OtpInput';
import { AsyncBtn } from 'common/new-ui/Button';
import { getSupportDetailsPayload } from './utils';

const VerifyOTP = ({
  email,
  phone,
  url,
  closeModal,
  reset,
  // eslint-disable-next-line no-shadow
  createSupportDetail,
  // eslint-disable-next-line no-shadow
  showNotification,
  supportDetail,
  tracking,
  isIndividual,
}) => {
  const [phoneToken, setPhoneToken] = useState('');
  const [emailToken, setEmailToken] = useState('');
  const [wrongEmailOtp, setWrongEmailOtp] = useState(false);
  const [wrongPhoneOtp, setWrongPhoneOtp] = useState(false);
  const [isVerified, setIsVerfied] = useState(false);
  const [isVerifyingOtp, setIsVerifyingOtp] = useState(false);

  const EMAIL = 0;
  const PHONE = 1;

  // const shouldRenderEmailSection = !!email,   /* to be enabled later */
  const shouldRenderEmailSection = false;
  const shouldRenderPhoneSection = phone !== supportDetail.data.phone;

  const onSubmit = () => {
    const supportDetailsPayload = getSupportDetailsPayload(
      { email, url, phone },
      supportDetail,
      isIndividual,
    );
    if (!Object.keys(supportDetailsPayload).length) {
      return null;
    }
    return createSupportDetail(supportDetailsPayload)
      .then((res) => {
        if (res.success) {
          showNotification({
            type: 'success',
            message: 'Support detail successfully added',
          });
        }
        trackSupportDetailSubmitAction({
          email: `${!!email}`,
          url: `${!!url}`,
          phone: `${!!phone}`,
        });

        tracking.trackEvent(
          window.rzpQ.onbr().success('support_details.popup_submit', {
            action: 'add_support_details',
          }),
        );
        tracking.trackEvent(
          window.rzpQ.onbr().initiated('action_popup', {
            clickSource: 'Submit',
          }),
        );
        trackSupportDetailPopupClose();
        closeModal();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors ? err.errors[0] : 'Some error occured. Please refresh',
        });
      });
  };

  const verifyOtp = (otpInput, context) => {
    let body = {
      otp: otpInput,
      token: phoneToken,
      action: 'verify_support_contact',
      contact_mobile: phone,
    };
    if (context !== PHONE) {
      body = {
        otp: otpInput,
        token: emailToken,
        action: 'verify_support_contact',
        contact_email: email,
      };
    }
    setIsVerifyingOtp(true);
    return merchantFetch({
      url: 'users/verify_otp',
      method: 'POST',
      data: body,
    })
      .then((res) => {
        setIsVerifyingOtp(false);
        if (res.success) {
          setIsVerfied(true);
        }
      })
      .catch((err) => {
        setIsVerifyingOtp(false);
        if (context === PHONE) setWrongPhoneOtp(true);
        else setWrongEmailOtp(true);
        showNotification({
          type: 'error',
          message: err.errors ? err.errors[0] : 'Some error occured. Please refresh',
        });
      });
  };

  const sendOtp = () => {
    // eslint-disable-next-line no-shadow
    const url = 'otp/send';
    const method = 'POST';

    if (shouldRenderEmailSection) {
      const body = {
        medium: 'email',
        action: 'verify_support_contact',
        email,
      };
      return merchantFetch({
        url,
        method,
        data: body,
      }).then((res) => {
        if (res.success) {
          setEmailToken(res.data.token);
        }
      });
    }
    if (shouldRenderPhoneSection) {
      const body = {
        medium: 'sms',
        action: 'verify_support_contact',
        contact_mobile: phone,
      };
      return merchantFetch({
        url,
        method,
        data: body,
      })
        .then((res) => {
          if (res.success) {
            setPhoneToken(res.data.token);
          }
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err.errors[0],
          });
        });
    }
    return null;
  };

  const renderOtpSection = (context) => (
    <div className="support-modal-otp-section">
      <div className="otp-section-message m-0">
        An {context === EMAIL ? 'e-mail' : 'SMS'} with 6-digit OTP has been sent to
        <br />
        <b>{context === EMAIL ? email : phone} </b>[
        <AsyncBtn.Transparent onClick={reset} className="m-l" showLoader={false}>
          Change
        </AsyncBtn.Transparent>
        ]
      </div>
      <OtpInput
        onComplete={(otpInput) => {
          verifyOtp(otpInput, context);
        }}
        onChange={() => {
          if (context === PHONE) setWrongPhoneOtp(false);
          else setWrongEmailOtp(false);
        }}
        wrong={context === EMAIL ? wrongEmailOtp : wrongPhoneOtp}
        autoFocus={false}
      />
      <div className="resend-link">
        Didn't receive the {context === EMAIL ? 'e-mail' : 'SMS'}?{' '}
        <AsyncBtn.Transparent
          pendingState="Sending OTP..."
          onClick={sendOtp}
          className="m-l"
          showLoader={false}
        >
          Resend
        </AsyncBtn.Transparent>
      </div>
    </div>
  );

  const getHeading = () => {
    if (shouldRenderEmailSection && shouldRenderPhoneSection) return 'Verify your Details';
    if (shouldRenderEmailSection) return 'Verify your E-Mail';
    if (shouldRenderPhoneSection) return 'Verify your Support Number';
    return '';
  };

  useEffect(() => {
    sendOtp();
  }, []);

  return (
    <div className="support-modal-content">
      <div className="merchant-heading">
        {getHeading()}
        <button type="button" className="close" onClick={closeModal}>
          <i className="i i-close" />
        </button>
      </div>
      {shouldRenderEmailSection && shouldRenderPhoneSection && (
        <div>
          <p className="merchant-subtitle">Please verify your submitted support details</p>
          <hr />
          <p className="otp-section-subtitle">Support Number</p>
        </div>
      )}
      {shouldRenderPhoneSection && renderOtpSection(PHONE)}
      {shouldRenderEmailSection && shouldRenderPhoneSection && (
        <div>
          <hr />
          <p className="otp-section-subtitle">Support E-Mail</p>
        </div>
      )}
      {shouldRenderEmailSection && renderOtpSection(EMAIL)}
      {shouldRenderPhoneSection && shouldRenderEmailSection && <hr />}
      <AsyncBtn.Primary
        type="submit"
        className="Button--full-width"
        onClick={onSubmit}
        disabled={!isVerified}
      >
        {isVerifyingOtp ? 'Verifying...' : 'Submit'}
      </AsyncBtn.Primary>
    </div>
  );
};

export default compose(
  connect(null, { showNotification, createSupportDetail }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('MerchantDataCollectionModal')),
)(VerifyOTP);

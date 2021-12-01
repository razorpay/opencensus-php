import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { OTPMETHOD } from './services';
import SuccessModal from './SuccessModal';
import OTPModal from './OTPModal';
import NewEmailModal from './NewEmailModal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const AddEmailModal = ({ user, screen, successButtonText, successButtonLink }) => {
  const STEPS = {
    VERIFY_MOBILE_OTP: 'verifyMobileOTP',
    ENTER_EMAIL: 'enterEmail',
    VERIFY_EMAIL_OTP: 'verifyEmailOTP',
    SUCCESS: 'success',
  };

  const [step, setStep] = useState(STEPS.VERIFY_MOBILE_OTP);
  const [newEmail, setNewEmail] = useState(null);
  const [otpAuthToken, setOtpAuthToken] = useState(null);

  const onMobileOTPSubmit = ({ token }) => {
    setOtpAuthToken(token);
    setStep(STEPS.ENTER_EMAIL);
  };

  const onEmailOTPSubmit = () => {
    setStep(STEPS.SUCCESS);
  };

  const onEnterEmailSubmit = ({ email }) => {
    setNewEmail(email);
    setStep(STEPS.VERIFY_EMAIL_OTP);
  };

  const onClose = () => {
    analyticsTrack({
      objectName: 'add email pop up cancel',
      actionName: 'clicked',
      screen: 'add email',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  return (
    <>
      {step == STEPS.VERIFY_MOBILE_OTP && (
        <OTPModal
          otpMethod={OTPMETHOD.PHONE}
          onSubmit={onMobileOTPSubmit}
          phone={user.user?.contact_mobile}
          screen={screen}
          onClose={onClose}
        />
      )}
      {step == STEPS.ENTER_EMAIL && (
        <NewEmailModal
          onSubmit={onEnterEmailSubmit}
          otpAuthToken={otpAuthToken}
          screen={screen}
          onClose={onClose}
        />
      )}
      {step == STEPS.VERIFY_EMAIL_OTP && (
        <OTPModal
          otpMethod={OTPMETHOD.EMAIL}
          email={newEmail}
          screen={screen}
          otpAuthToken={otpAuthToken}
          onSubmit={onEmailOTPSubmit}
          onClose={onClose}
        />
      )}
      {step == STEPS.SUCCESS && (
        <SuccessModal
          heading="Email added successfully &#127881;"
          screen={screen}
          note={`Your email ${newEmail} has been added successfully`}
          successButtonLink={successButtonLink}
          successButtonText={successButtonText}
          onClose={onClose}
        />
      )}
    </>
  );
};

export default compose(
  connect((state) => ({ user: state.session.user }), { showNotification: fnShowNotification }),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('AddEmailModal')),
)(AddEmailModal);

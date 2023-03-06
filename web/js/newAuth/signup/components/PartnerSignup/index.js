import React, { useState, useEffect } from 'react';
import './modal.styl';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { StyledSignupWrapper } from './styled';
import { trackWithSegment } from 'newAuth/trackEvents';
import {
  closeModal as closeModalFn,
  openModal as openModalFn,
} from 'merchant_common/reducers/modals';
import { showNotification as showNotificationFn } from 'merchant_common/reducers/notifications';
import CongratsForm from './components/CongratsForm';
import imageFullLogo from 'assets/logo_full.png';
import imagePartnerLogo from 'assets/partner-dashboard/partner-logo.svg';
import LeftSideInfo from './components/LeftSideInfo';
import SignupForm from './components/SignupForm';
import SignupHeader from './components/SignupHeader';
import { isOnboardAllAsResellers } from 'newAuth/splitz/index';

const PartnerSignup = ({ openModal, closeModal, showNotification }) => {
  const [step, setStep] = useState(STEPS.MOBILE_NUMBER);
  const [contactEmail, setContactEmail] = useState(null);
  const [emailToken, setEmailToken] = useState(null);
  useEffect(() => {
    trackWithSegment({
      objectName: 'Signup',
      actionName: 'Displayed',
      location: SCREEN_NAME[step],
    });
  }, [step]);

  const onboardAllAsResellerFlag = isOnboardAllAsResellers();

  return (
    <StyledSignupWrapper>
      <div className="logo-wrap">
        <img className="logo" src={imageFullLogo} alt="Razorpay" />
        <img className="partner-logo" src={imagePartnerLogo} />
      </div>
      <LeftSideInfo step={step} />
      {step === STEPS.CONGRATS ? (
        <CongratsForm
          contactEmail={contactEmail}
          setContactEmail={setContactEmail}
          setEmailToken={setEmailToken}
          setStep={setStep}
          showNotification={showNotification}
          onboardAllAsResellerFlag={onboardAllAsResellerFlag}
        />
      ) : (
        <>
          <SignupHeader step={step} setStep={setStep} />
          <SignupForm
            emailToken={emailToken}
            step={step}
            setEmailToken={setEmailToken}
            setStep={setStep}
            contactEmail={contactEmail}
            closeModal={closeModal}
            openModal={openModal}
            showNotification={showNotification}
            onboardAllAsResellerFlag={onboardAllAsResellerFlag}
          />
        </>
      )}
      <div className="signup-footer-mweb">
        <img className="logo logo-footer" src={imageFullLogo} alt="Razorpay" />
        <img src={imagePartnerLogo} />
      </div>
    </StyledSignupWrapper>
  );
};

export default compose(
  connect(null, {
    closeModal: closeModalFn,
    openModal: openModalFn,
    showNotification: showNotificationFn,
  }),
)(PartnerSignup);

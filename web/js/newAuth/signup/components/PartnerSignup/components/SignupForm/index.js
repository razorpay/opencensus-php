import React, { useState } from 'react';

import { STEPS, STEP_TO_PROGRESS_WIDTH } from 'newAuth/signup/Constants';

import BusinessTypeSelection from './components/BusinessTypeSelection';
import ContactInfo from './components/ContactInfo';
import EmailVerification from './components/EmailVerification';
import MobileNumber from './components/MobileNumber';
import MobileVerification from './components/MobileVerification';
import PartnerTypeSelection from './components/PartnerTypeSelection';
import WelcomeBack from './components/WelcomeBack';
import {
  StyledFormWrap,
  StyledFormContentWrap,
  StyledProgressBarContainer,
  StyledProgressBarSkill,
  StyledFormContent,
} from './components/styled';

const {
  MOBILE_NUMBER,
  WELCOME_BACK,
  MOBILE_VERIFICATION,
  CONTACT_INFO,
  BUSINESS_TYPE_SELECTION,
  PARTNER_TYPE_SELECTION,
  EMAIL_VERIFICATION,
} = STEPS;

const SignupForm = ({
  emailToken,
  step,
  setEmailToken,
  setStep,
  contactEmail,
  showNotification,
  openModal,
  closeModal,
  onboardAllAsResellerFlag,
}) => {
  const [showHeader, setShowHeader] = useState(true);
  const [mobileNumber, setMobileNumber] = useState('');
  const [contactName, setContactName] = useState('');
  const [isSendWhatsapp, setIsSendWhatsapp] = useState(true);
  const [otpVerifyToken, setOtpVerifyToken] = useState('');

  return (
    <StyledFormWrap>
      <StyledFormContentWrap>
        {showHeader && (
          <StyledProgressBarContainer>
            <StyledProgressBarSkill $progress={STEP_TO_PROGRESS_WIDTH[step]} />
          </StyledProgressBarContainer>
        )}
        <StyledFormContent>
          {step === MOBILE_NUMBER && (
            <MobileNumber
              closeModal={closeModal}
              isSendWhatsapp={isSendWhatsapp}
              openModal={openModal}
              setIsSendWhatsapp={setIsSendWhatsapp}
              setMobileNumber={setMobileNumber}
              setOtpVerifyToken={setOtpVerifyToken}
              setStep={setStep}
              showNotification={showNotification}
            />
          )}
          {step === WELCOME_BACK && (
            <WelcomeBack
              mobileNumber={mobileNumber}
              setMobileNumber={setMobileNumber}
              setStep={setStep}
            />
          )}
          {step === MOBILE_VERIFICATION && (
            <MobileVerification
              mobileNumber={mobileNumber}
              otpVerifyToken={otpVerifyToken}
              setOtpVerifyToken={setOtpVerifyToken}
              isSendWhatsapp={isSendWhatsapp}
              setShowHeader={setShowHeader}
              setStep={setStep}
            />
          )}
          {step === CONTACT_INFO && (
            <ContactInfo setContactName={setContactName} setStep={setStep} />
          )}
          {step === BUSINESS_TYPE_SELECTION && (
            <BusinessTypeSelection
              closeModal={closeModal}
              contactName={contactName}
              openModal={openModal}
              setStep={setStep}
              showNotification={showNotification}
              onboardAllAsResellerFlag={onboardAllAsResellerFlag}
            />
          )}
          {step === PARTNER_TYPE_SELECTION && (
            <PartnerTypeSelection
              setStep={setStep}
              showNotification={showNotification}
              onboardAllAsResellerFlag={onboardAllAsResellerFlag}
            />
          )}
          {step === EMAIL_VERIFICATION && (
            <EmailVerification
              contactEmail={contactEmail}
              emailToken={emailToken}
              setEmailToken={setEmailToken}
              setShowHeader={setShowHeader}
              setStep={setStep}
            />
          )}
        </StyledFormContent>
      </StyledFormContentWrap>
    </StyledFormWrap>
  );
};

export default SignupForm;

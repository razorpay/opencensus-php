import React, { useState } from 'react';
import rTracking from 'react-tracking';
import Input from 'common/new-ui/Input';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import GetOtpScreen from './GetOtpScreen';
import VerifyOtpScreen from './VerifyOtpScreen';
import { analyticsTrack } from 'common/utils/analytics';

const EAadhar = ({
  isAadharEkycMandatory,
  aadharStatus,
  isAadharLinked,
  mobileLinkedOnChange,
  tracking,
  activeTab,
  isDigilockerEkyc,
}) => {
  const [aadharNumber, setAadharNumber] = useState('');
  const [captcha, setCaptchaValue] = useState('');
  const [error, setError] = useState('');
  const [isStartAgain, setIsStartAgain] = useState(false);
  const [screen, setScreen] = useState(aadharStatus ? 'Success' : '');
  const [requestId, setRequestId] = useState(null);
  const trackEvent = tracking.trackEvent;

  const analyticsProperties = {
    properties: {
      location: 'Activation page',
      error_code: error,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  };

  const onChangeHandler = ({ target }) => {
    setError('');
    const name = target.name;
    if (name === 'aadhar_number') {
      setAadharNumber(target.value);
    } else {
      setCaptchaValue(target.value);
    }
  };

  const VerifiedAadhar = () => {
    return (
      <>
        <Input
          type="text"
          class="Input--small"
          label="Aadhaar Verification"
          defaultValue="XXXXXXXXXXXX"
          style={{ border: '1px solid rgba(31, 137, 14, 0.54)' }}
          addonAfter={<i className="i i-check text-success" />}
          readOnly={true}
        />
        <div className="Input-content e-aadhar-success-text">
          We have receive your Aadhaar details
        </div>
      </>
    );
  };

  const ProviderErrorScreen = () => {
    return (
      <div className="Input Input--small Input--vTop is-mature">
        <div className="Input-label">Aadhaar Verification</div>
        {isDigilockerEkyc ? (
          <div className="Input-content e-aadhar-provider-error">
            We can not support OTP based Aadhaar verification because of downtime on Digilocker
            servers. Please upload copies of one the address proofs listed below.
          </div>
        ) : (
          <div className="Input-content e-aadhar-provider-error">
            We can not support OTP based Aadhaar verification because of downtime on UIDAI servers.
            Please upload copies of one the address proofs listed below.
          </div>
        )}
      </div>
    );
  };

  const handleDownTimeError = (resData = {}) => {
    mobileLinkedOnChange(false);
    trackEvent(window.rzpQ.onbr().initiated('kyc.e_aadhar_downtime_fallback'));
    analyticsTrack({
      objectName: 'kyc',
      actionName: 'e aadhar downtime fallback initiated',
      screen: 'Documents',
      properties: {
        ...resData,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    setScreen('ProviderError');
  };

  const commonProps = {
    analyticsProperties,
    mobileLinkedOnChange,
    trackEvent,
    setScreen,
    setRequestId,
    setCaptchaValue,
    setIsStartAgain,
    setError,
    captcha,
    aadharNumber,
    handleDownTimeError,
    isDigilockerEkyc,
    requestId,
  };

  const renderScreens = () => {
    switch (screen) {
      case 'VerifyOTP':
        return <VerifyOtpScreen {...commonProps} />;
      case 'Success':
        return <VerifiedAadhar />;
      case 'ProviderError':
        return <ProviderErrorScreen />;
      default:
        return (
          <GetOtpScreen
            isAadharLinked={isAadharLinked}
            activeTab={activeTab}
            error={error}
            onChange={onChangeHandler}
            setAadharNumber={setAadharNumber}
            isStartAgain={isStartAgain}
            isAadharEkycMandatory={isAadharEkycMandatory}
            isDigilockerEkyc={isDigilockerEkyc}
            {...commonProps}
          />
        );
    }
  };

  return <div className="e-aadhar">{renderScreens()}</div>;
};

export default rTracking(() => {
  return window.rzpQ.component('EAadhar');
})(EAadhar);

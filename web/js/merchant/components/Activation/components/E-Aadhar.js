import React, { useState } from 'react';
import RTracking from 'react-tracking';
import Input from 'common/new-ui/Input';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import GetOtpScreen from './GetOtpScreen';
import VerifyOtpScreen from './VerifyOtpScreen';

const EAadhar = ({ aadharStatus, isAadharLinked, mobileLinkedOnChange, tracking, activeTab }) => {
  const [aadharNumber, setAadharNumber] = useState('');
  const [captcha, setCaptchaValue] = useState('');
  const [error, setError] = useState('');
  const [isStartAgain, setIsStartAgain] = useState(false);
  const [screen, setScreen] = useState(aadharStatus ? 'Success' : '');

  const trackEvent = tracking.trackEvent;

  const analyticsProperties = {
    properties: {
      location: 'Activation page',
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
          label="Aadhar Verification"
          defaultValue="XXXXXXXXXXXX"
          style={{ border: '1px solid rgba(31, 137, 14, 0.54)' }}
          addonAfter={<i className="i i-check text-success" />}
          readOnly={true}
        />
        <div className="Input-content e-aadhar-success-text">
          We have recieved your Aadhar details
        </div>
      </>
    );
  };

  const ProviderErrorScreen = () => {
    return (
      <div className="Input Input--small Input--vTop is-mature">
        <div className="Input-label">Aadhar Verification</div>
        <div className="Input-content e-aadhar-provider-error">
          We are unable to verify your Aadhar details with your number at the moment. Please upload
          scanned copies of any of these address proofs listed below
        </div>
      </div>
    );
  };

  const commonProps = {
    analyticsProperties,
    mobileLinkedOnChange,
    trackEvent,
    setScreen,
    setCaptchaValue,
    setIsStartAgain,
    setError,
    captcha,
    aadharNumber,
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
            {...commonProps}
          />
        );
    }
  };

  return <div className="e-aadhar">{renderScreens()}</div>;
};

export default RTracking(() => {
  return window.rzpQ.component('EAadhar');
})(EAadhar);

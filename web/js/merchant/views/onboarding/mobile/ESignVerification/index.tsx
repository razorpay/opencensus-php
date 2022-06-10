import React, { useState, useEffect } from 'react';
import GetOTP from './GetOTP';
import VerifyOTP from './VerifyOTP';
import AadharSuccess from './AadharSuccess';
import Card from 'common/components/Card';
import useActivation from '../hooks/useActivation';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import AadharError from './AadharError';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

interface ESignPropsT {
  disabled?: boolean;
  showAddressProofDoc: () => void;
}

const ESignVerification = ({ disabled = false, showAddressProofDoc }: ESignPropsT): JSX.Element => {
  const [aadharNumber, setHasAadharNumber] = useState('');
  const [requestId, setHasRequestId] = useState('');
  const [nextStep, setNextStep] = useState('GetOTP');
  const [otp, setHasOTP] = useState('');
  const [inputCaptcha, setInputCaptcha] = useState('');
  const [aadharError, setAadharInputError] = useState('');
  const { user } = useApp();
  const { data, postData } = useActivation();
  const trackEvents = useTrackEvents();

  useEffect(() => {
    if (data && data.stakeholder && data.stakeholder.aadhaar_esign_status === 'verified') {
      setNextStep('AadharSuccess');
    }
  }, [data]);

  const goToNextScreen = ({ nextScreen }) => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: `EAdhar ${nextScreen}`,
      screen: 'home page',
      eventAction: 'initiated',
      user,
    });
    setNextStep(nextScreen);
  };

  const setAadharNumber = (aadharNum) => {
    setHasAadharNumber(aadharNum);
  };

  const setRequestId = (request_id) => {
    setHasRequestId(request_id);
  };

  const setOTP = (otpInput) => {
    setHasOTP(otpInput);
  };

  const handleDownTimeError = (resData = {}) => {
    goToNextScreen({ nextScreen: 'AadharError' });
    showAddressProofDoc();
    postData({ stakeholder: { aadhaar_linked: 0 } });
    trackEvents({
      objectName: 'kyc',
      actionName: 'e aadhar downtime fallback',
      eventAction: 'initiated',
      screen: 'Documents',
      properties: {
        ...resData,
      },
    });
  };

  const renderComponent = () => {
    switch (nextStep) {
      case 'GetOTP':
        return (
          <GetOTP
            setOTP={setOTP}
            otp={otp}
            setAadharNumber={setAadharNumber}
            goToNextScreen={goToNextScreen}
            setUserEnteredCaptcha={setInputCaptcha}
            aadharError={aadharError}
            disabled={disabled}
            handleDownTimeError={handleDownTimeError}
            setRequestId={setRequestId}
          />
        );
      case 'VerifyOTP':
        return (
          <VerifyOTP
            goToNextScreen={goToNextScreen}
            aadharNumber={aadharNumber}
            inputCaptcha={inputCaptcha}
            requestId={requestId}
            setAadharInputError={setAadharInputError}
            handleDownTimeError={handleDownTimeError}
          />
        );
      case 'AadharSuccess':
        return <AadharSuccess />;
      case 'AadharError':
        return <AadharError />;
      default:
        return (
          <GetOTP
            setOTP={setOTP}
            otp={otp}
            setAadharNumber={setAadharNumber}
            goToNextScreen={goToNextScreen}
            setUserEnteredCaptcha={setInputCaptcha}
            aadharError={aadharError}
            disabled={disabled}
            handleDownTimeError={handleDownTimeError}
            setRequestId={setRequestId}
          />
        );
    }
  };

  return (
    <Card padding={[2, 2, 0, 2]} margin={[0, 0, 2, 0]}>
      {renderComponent()}
    </Card>
  );
};

export default ESignVerification;

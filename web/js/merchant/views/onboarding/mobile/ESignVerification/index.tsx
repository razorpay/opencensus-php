import React, { useState, useEffect } from 'react';
import GetOTP from './GetOTP';
import VerifyOTP from './VerifyOTP';
import AadharSuccess from './AadharSuccess';
import Card from 'common/components/Card';
import useActivation from '../hooks/useActivation';
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';
import AadharError from './AadharError';

interface ESignPropsT {
  disabled?: boolean;
  showAddressProofDoc: () => void;
}

const ESignVerification = ({ disabled = false, showAddressProofDoc }: ESignPropsT): JSX.Element => {
  const [aadharNumber, setHasAadharNumber] = useState('');
  const [nextStep, setNextStep] = useState('GetOTP');
  const [otp, setHasOTP] = useState('');
  const [inputCaptcha, setInputCaptcha] = useState('');
  const [aadharError, setAadharInputError] = useState('');
  const { user } = useApp();
  const { data, postData } = useActivation();

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

  const setOTP = (otpInput) => {
    setHasOTP(otpInput);
  };

  const handleDownTimeError = () => {
    goToNextScreen({ nextScreen: 'AadharError' });
    showAddressProofDoc();
    postData({ stakeholder: { aadhaar_linked: 0 } });
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
          />
        );
      case 'VerifyOTP':
        return (
          <VerifyOTP
            goToNextScreen={goToNextScreen}
            aadharNumber={aadharNumber}
            inputCaptcha={inputCaptcha}
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

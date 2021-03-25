import React, { useState, useEffect } from 'react';
import AadharInput from './AadharInput';
import GetOTP from './GetOTP';
import VerifyOTP from './VerifyOTP';
import AadharSuccess from './AadharSuccess';
import Card from '../../../../components/Card';
import useActivation from '../hooks/useActivation';
import { analyticsTrack } from '../../../../services/tracking/segment';
import { useApp } from 'v2/context/App';

interface ESignPropsT {
  disabled?: boolean;
}

const ESignVerification: React.FC<ESignPropsT> = ({ disabled = false }) => {
  const [aadharNumber, setHasAadharNumber] = useState('');
  const [pin, setHasPin] = useState('');
  const [nextStep, setNextStep] = useState('');
  const [otp, setHasOTP] = useState('');
  const [inputCaptcha, setInputCaptcha] = useState('');
  const [aadharError, setAadharInputError] = useState('');
  const { user } = useApp();
  const { data } = useActivation();

  useEffect(() => {
    if (data && data.stakeholder && data.stakeholder.aadhaar_esign_status === 'verified') {
      setNextStep('AadharSuccess');
    }
  }, []);

  const goToNextScreen = ({ nextScreen }) => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: `EAdhar ${nextScreen} initiated`,
      screen: 'home page',
      properties: {
        userId: user.id,
      },
    });
    setNextStep(nextScreen);
  };

  const setAadharNumber = (aadharNum) => {
    setHasAadharNumber(aadharNum);
  };

  const setPin = (pinInput) => {
    setHasPin(pinInput);
  };

  const setOTP = (otpInput) => {
    setHasOTP(otpInput);
  };

  const renderComponent = () => {
    switch (nextStep) {
      case 'GetOTP':
        return (
          <GetOTP
            setPin={setPin}
            setOTP={setOTP}
            otp={otp}
            aadharNumber={aadharNumber}
            setAadharNumber={setAadharNumber}
            goToNextScreen={goToNextScreen}
            setUserEnteredCaptcha={setInputCaptcha}
            setAadharInputError={setAadharInputError}
            aadharError={aadharError}
          />
        );
      case 'VerifyOTP':
        return (
          <VerifyOTP
            goToNextScreen={goToNextScreen}
            pin={pin}
            aadharNumber={aadharNumber}
            inputCaptcha={inputCaptcha}
            setAadharInputError={setAadharInputError}
          />
        );
      case 'AadharSuccess':
        return <AadharSuccess />;
      default:
        return (
          <AadharInput
            setAadharNumber={setAadharNumber}
            goToNextScreen={goToNextScreen}
            aadharError={aadharError}
            setAadharInputError={setAadharInputError}
            disabled={disabled}
          />
        );
    }
  };

  return (
    <Card padding={[2]} margin={[0, 0, 2, 0]}>
      {renderComponent()}
    </Card>
  );
};

export default ESignVerification;

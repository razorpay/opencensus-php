import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Box } from '@razorpay/blade/components';
import { Info } from './components/Info';
import { SaveGateway } from './components/SaveGateway';
import { showNotification } from 'merchant_common/reducers/notifications';

const OptimizerOnBoarding = (props): JSX.Element => {
  const { session, showNotification } = props;
  const [showSaveGatewayFlow, setShowSaveGatewayFlow] = useState(false);
  const [successfullySubmitted, setSuccessfullySubmitted] = useState(false);

  const nextStep = () => {
    setShowSaveGatewayFlow(true);
  };

  const prevStep = (sucess = false) => {
    if (sucess) {
      setSuccessfullySubmitted(true);
    }
    setShowSaveGatewayFlow(false);
  };

  return (
    <Box>
      {!showSaveGatewayFlow && (
        <Info
          mode={session.mode}
          nextStep={nextStep}
          successfullySubmitted={successfullySubmitted}
        />
      )}
      {showSaveGatewayFlow && (
        <SaveGateway prevStep={prevStep} showNotification={showNotification} />
      )}
    </Box>
  );
};

const mapStateToProps = (state) => {
  const { session } = state;
  return {
    session,
  };
};

export default compose(
  connect(mapStateToProps, {
    showNotification: showNotification,
  }),
)(OptimizerOnBoarding);

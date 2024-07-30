import React from 'react';
import { ArrowRightIcon, Box, Button, Heading } from '@razorpay/blade/components';
import { PaymentSuccessContainer } from './styles';
import SuccessIcon from 'apps/pos/src/assets/paymentSuccess.svg';

interface DevicePaymentSuccessProps {
  handleGoToNextStep: () => void;
}

const DevicePaymentSuccess = ({ handleGoToNextStep }: DevicePaymentSuccessProps): JSX.Element => {
  return (
    <PaymentSuccessContainer>
      <Box
        height="100%"
        display="flex"
        justifyContent="center"
        flexDirection="column"
        alignItems="center"
      >
        <Box marginBottom="spacing.5">
          <img src={SuccessIcon} alt="success-icon" height="100px" />
        </Box>
        <Heading
          weight="semibold"
          color="interactive.text.positive.normal"
          marginBottom="spacing.5"
          textAlign="center"
        >
          Order is successfully placed!
        </Heading>
        <Button icon={ArrowRightIcon} iconPosition="right" onClick={handleGoToNextStep}>
          Continue to next step
        </Button>
      </Box>
    </PaymentSuccessContainer>
  );
};

export default DevicePaymentSuccess;

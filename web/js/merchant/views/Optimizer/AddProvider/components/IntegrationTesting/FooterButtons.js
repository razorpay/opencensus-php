import React from 'react';
import { Button } from '@razorpay/blade/components';

export const FooterButtons = ({
  currentStep,
  isPaymentSuccessfull,
  isPaymentDone,
  testPayment,
  raiseTicket,
  testAnotherPayment,
  changeIntegrationTestingStep,
}) => {
  switch (currentStep) {
    case 'payment_testing':
      return (
        <>
          {isPaymentDone ? (
            <Button variant="secondary" onClick={testAnotherPayment}>
              Test another
            </Button>
          ) : (
            <Button variant="primary" onClick={testPayment}>
              Test payment
            </Button>
          )}
          {isPaymentDone ? (
            isPaymentSuccessfull ? (
              <Button
                variant="primary"
                onClick={() => changeIntegrationTestingStep('refund_testing')}
              >
                Continue
              </Button>
            ) : (
              <Button variant="primary" onClick={raiseTicket}>
                Raise a ticket
              </Button>
            )
          ) : null}
        </>
      );
    default:
      return null;
  }
};

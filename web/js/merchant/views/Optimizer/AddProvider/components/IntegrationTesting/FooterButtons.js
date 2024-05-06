import React from 'react';
import { Button } from '@razorpay/blade/components';

export const FooterButtons = ({
  currentStep,
  isPaymentSuccessfull,
  isPaymentDone,
  testPayment,
  raiseTicket,
  isRefundDone,
  testAnotherPayment,
  changeIntegrationTestingStep,
  takeProviderLive,
  isUpdatingProvider,
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
                onClick={() => changeIntegrationTestingStep({ name: 'refund_testing' })}
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
    case 'refund_testing':
      return (
        <>
          <Button
            variant="secondary"
            onClick={() => changeIntegrationTestingStep({ name: 'payment_testing' })}
          >
            Previous
          </Button>
          <Button
            variant="primary"
            onClick={() => changeIntegrationTestingStep({ name: 'integration_audit_summary' })}
            isDisabled={!isRefundDone}
          >
            Continue
          </Button>
        </>
      );
    case 'integration_audit_summary':
      return (
        <>
          <Button
            variant="secondary"
            onClick={() => changeIntegrationTestingStep({ name: 'refund_testing' })}
          >
            Previous
          </Button>
          <Button
            variant="primary"
            onClick={() => changeIntegrationTestingStep({ name: 'provider_settings' })}
          >
            Continue
          </Button>
        </>
      );
    case 'provider_settings':
      return (
        <>
          <Button
            variant="secondary"
            onClick={() => changeIntegrationTestingStep({ name: 'integration_audit_summary' })}
            isDisabled={isUpdatingProvider}
          >
            Previous
          </Button>
          <Button variant="primary" onClick={takeProviderLive} isLoading={isUpdatingProvider}>
            Go live
          </Button>
        </>
      );
    default:
      return null;
  }
};

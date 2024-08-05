import React from 'react';
import { Button } from '@razorpay/blade/components';

export const FooterButtons = ({
  currentStep,
  steps,
  isPaymentSuccessfull,
  isPaymentDone,
  testPayment,
  raiseTicket,
  isRefundDone,
  testAnotherPayment,
  changeIntegrationTestingStep,
  takeProviderLive,
  isUpdatingProvider,
  isTicketLoading,
}) => {
  let isPaymentTestingBlocked, isRefundTestingBlocked, isIntegrationAuditSummaryBlocked;
  steps.forEach(({ value, blocked }) => {
    switch (value) {
      case 'payment_testing':
        isPaymentTestingBlocked = blocked;
        break;
      case 'refund_testing':
        isRefundTestingBlocked = blocked;
        break;
      case 'integration_audit_summary':
        isIntegrationAuditSummaryBlocked = blocked;
        break;
      default:
    }
  });

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
                isDisabled={isRefundTestingBlocked}
              >
                Continue
              </Button>
            ) : (
              <Button variant="primary" onClick={raiseTicket} isLoading={isTicketLoading}>
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
            isDisabled={isRefundTestingBlocked && isPaymentTestingBlocked}
            onClick={() =>
              changeIntegrationTestingStep({
                name: isRefundTestingBlocked ? 'payment_testing' : 'refund_testing',
              })
            }
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
            isDisabled={isUpdatingProvider || isIntegrationAuditSummaryBlocked}
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

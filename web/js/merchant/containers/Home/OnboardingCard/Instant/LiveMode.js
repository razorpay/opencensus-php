import React from 'react';
import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

export default ({
  instantActivation,
  isActivated,
  isSubmitted,
  isRejected,
  integration,
  showProductsModal,
  showTransactionsModal,
}) => {
  const { isLoading, keysGenerated, paymentsMade, isKLA } = integration;

  let status = possibleStatuses.locked,
    title = 'Live Payments',
    content = null;

  const { isL1Submitted, isGraylistFlow, isBlacklistFlow } = instantActivation;

  if (!isActivated) {
    if (!isL1Submitted) {
      content = 'Fill the Activation Form in order to unlock Live Payments';
    } else if (isGraylistFlow) {
      status = possibleStatuses.pending;

      if (!isSubmitted) {
        content = 'Fill the KYC Form in order to unlock Live Payments';
      } else {
        content =
          'Live payments will be enabled after your KYC form is verified';
      }
    } else if (isBlacklistFlow) {
      status = possibleStatuses.blocked;
      title += ' (Locked)';
      content = 'We currently do not support your business model';
    }
  } else {
    if (isRejected) {
      status = possibleStatuses.blocked;
      content = 'Transactions are not allowed as your account has been blocked';
    } else {
      if (isLoading) {
        status = possibleStatuses.loading;
      } else {
        if (!paymentsMade) {
          status = possibleStatuses.active;
          title = 'Transact in Live Mode';
          content = (
            <div>
              <div>
                Receive payments by integrating in Live mode or view products
              </div>
              <button
                className="btn btn-primary m-t"
                onClick={() => showTransactionsModal(isKLA)}
              >
                How do I accept payments?
              </button>
            </div>
          );
        } else {
          status = possibleStatuses.done;
          content = (
            <div>View payments in Transactions tab, or keep using products</div>
          );
        }
      }
    }
  }

  return (
    <Step status={status}>
      <StepTitle>{title}</StepTitle>
      <StepContent>{content}</StepContent>
    </Step>
  );
};

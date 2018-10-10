import React from 'react';
import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

export default ({
  instantActivation,
  isSubmitted,
  needsClarification,
  isActivated,
}) => {
  let status = possibleStatuses.active,
    content = null,
    title = 'Account Activation';

  const {
    isL1Submitted,
    isWhitelistFlow,
    isBlacklistFlow,
    isGraylistFlow,
  } = instantActivation;

  if (!isL1Submitted) {
    content =
      'Give a few details to start transacting immediately CTA: Activate Account';
  } else if (isActivated) {
    title = 'Account Activated';
    status = possibleStatuses.done;
    content = 'Your account activation is complete.';
  } else if (isGraylistFlow) {
    if (!isSubmitted) {
      content =
        'For your business model, we need a few more details for activation Fill KYC Form';
    } else {
      if (needsClarification) {
        status = possibleStatuses.blocked;
        content = 'Check your email ID to complete clarification of KYC';
      } else {
        status = possibleStatuses.progress;
        content =
          'We are reviewing your form. Expect confirmation in 2-3 days.';
      }
    }
  } else if (isBlacklistFlow) {
    status = possibleStatuses.blocked;
    content =
      'We do not support your selected business model. In case you entered it wrong, change it here';
  }

  return (
    <Step status={status}>
      <StepTitle>{title}</StepTitle>
      <StepContent>{content}</StepContent>
    </Step>
  );
};

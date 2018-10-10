import React from 'react';
import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

export default props => {
  const { mode, payments, isKLA, hasKeyAccess } = props;

  let status = possibleStatuses.done,
    content = null;

  if (mode === 'live') {
    content = 'You can try out the Dashboard in Test Mode';
  } else {
    if (!hasKeyAccess && !isKLA) {
      content = 'Generate Test Keys and use Test Products';
    } else if (payments.loading) {
      status = possibleStatuses.loading;
    } else {
      const hasPayments = payments.items && payments.items.length > 0;

      if (hasPayments) {
        content = 'View all payments received in Test mode in Transactions tab';
      } else if (isKLA) {
        content = 'Use Test Products to start creating test payments now.';
      } else {
        content = 'Create Test payments now. For details, Read documentation';
      }
    }
  }

  return (
    <Step status={status}>
      <StepTitle>Test Mode Enabled</StepTitle>
      <StepContent>{content}</StepContent>
    </Step>
  );
};

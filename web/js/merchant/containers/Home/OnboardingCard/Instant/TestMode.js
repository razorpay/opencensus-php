import React from 'react';
import { Link } from 'react-router-dom';

import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

export default props => {
  const { mode, integration } = props,
    { isLoading, keysGenerated, paymentsMade } = integration;

  let status = possibleStatuses.done,
    content = null;

  if (mode === 'live') {
    content = 'You can try out the Dashboard in Test Mode';
  } else if (isLoading) {
    status = possibleStatuses.loading;
  } else {
    if (!keysGenerated) {
      content = (
        <span>
          <Link to="/keys" className="btn-link">
            Generate Test Keys
          </Link>{' '}
          and use Test Products
        </span>
      );
    } else if (!paymentsMade) {
      content = (
        <span>
          Create Test payments now. For details, Read
          <a
            target="_blank"
            className="btn-link"
            href="https://docs.razorpay.com/"
          >
            documentation
          </a>
          or use Test Products
        </span>
      );
    } else {
      content = (
        <span>
          View all payments received in Test mode in{' '}
          <Link to="/payments">Transactions</Link> tab
        </span>
      );
    }
  }

  return (
    <Step status={status}>
      <StepTitle>Test Mode Enabled</StepTitle>
      <StepContent>{content}</StepContent>
    </Step>
  );
};

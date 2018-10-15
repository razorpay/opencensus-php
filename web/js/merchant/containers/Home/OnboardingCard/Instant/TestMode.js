import React from 'react';
import { Link } from 'react-router-dom';

import Step, { StepTitle, StepContent, possibleStatuses } from './Step';

const TestProducts = ({ onClick }) => (
  <span className="btn-link cursor-pointer" onClick={onClick}>
    Test Products
  </span>
);

export default props => {
  const { mode, integration, showProductsModal } = props,
    { isLoading, keysGenerated, paymentsMade } = integration;

  let title = 'Test Mode Enabled',
    status = possibleStatuses.done,
    content = null;

  if (mode === 'live') {
    content = 'You can try out the Dashboard in Test Mode';
  } else if (isLoading) {
    status = possibleStatuses.loading;
  } else {
    if (paymentsMade) {
      title = 'Test Mode Payments';
      content = (
        <span>
          View all payments received in Test mode in{' '}
          <Link to="/payments">Transactions</Link> tab
        </span>
      );
    } else if (!keysGenerated) {
      content = (
        <span>
          <Link to="/keys" className="btn-link">
            Generate Test Keys
          </Link>{' '}
          and use <TestProducts onClick={showProductsModal} />
        </span>
      );
    } else {
      title = 'Transact in Test Mode';
      content = (
        <span>
          Create Test payments now. For details, Read{' '}
          <a
            target="_blank"
            className="btn-link"
            href="https://docs.razorpay.com/"
          >
            documentation
          </a>{' '}
          or use <TestProducts onClick={showProductsModal} />
        </span>
      );
    }
  }

  return (
    <Step status={status}>
      <StepTitle>{title}</StepTitle>
      <StepContent>{content}</StepContent>
    </Step>
  );
};

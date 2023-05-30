import React from 'react';
import { render, screen } from 'test-utils';
import PaymentsListFilter from 'merchant/views/PaymentButton/PaymentButton/Details/PaymentsList/PaymentsListFilter';

const defaultProps = {
  onSubmitMock: jest.fn(),
  fetchAllMock: jest.fn(),
  showBatchIdFilter: true,
};

describe('PaymentsListFilter', () => {
  const renderApp = (props) => {
    return render(
      <PaymentsListFilter form="paymentListFilter" count="5" {...defaultProps} {...props} />,
    );
  };

  test('renders all filter fields', () => {
    renderApp();
    expect(screen.getByText('Payment Id')).toBeInTheDocument();
    expect(screen.getByText('Status')).toBeInTheDocument();
    expect(screen.getByText('Email')).toBeInTheDocument();
    expect(screen.getByText('Count')).toBeInTheDocument();
  });

  test('renders batch ID filter if showBatchIdFilter prop is true', () => {
    renderApp();
    expect(screen.getByText('Batch Id')).toBeInTheDocument();
  });

  test('should not renders batch ID filter if showBatchIdFilter prop is false', () => {
    const props = {
      showBatchIdFilter: false,
    };
    renderApp(props);
    expect(screen.queryByText('Batch Id')).not.toBeInTheDocument();
  });
});

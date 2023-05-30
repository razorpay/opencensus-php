import ListFilter from 'merchant/views/PaymentButton/SubscriptionButton/Details/PaymentsList/PaymentsListFilter';
import React from 'react';
import { render } from 'test-utils';

describe('ListFilter', () => {
  const initProps = {
    count: '25',
    form: 'paymentListFilter',
  };
  it('should render all input fields', () => {
    const { getByText } = render(<ListFilter {...initProps} />);
    expect(getByText('Payment Id')).toBeInTheDocument();
    expect(getByText('Status')).toBeInTheDocument();
    expect(getByText('Email')).toBeInTheDocument();
    expect(getByText('Count')).toBeInTheDocument();
  });

  it('should render batch id filter when showBatchIdFilter prop is true', () => {
    const { getByText } = render(<ListFilter {...initProps} showBatchIdFilter />);
    expect(getByText('Batch Id')).toBeInTheDocument();
  });

  it('should render batch id filter when showBatchIdFilter prop is false', () => {
    const { queryByText } = render(<ListFilter {...initProps} showBatchIdFilter={false} />);
    expect(queryByText('Batch Id')).not.toBeInTheDocument();
  });
});

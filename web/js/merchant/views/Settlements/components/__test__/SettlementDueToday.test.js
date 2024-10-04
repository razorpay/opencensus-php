import React from 'react';
import moment from 'moment';
import '@testing-library/jest-dom/extend-expect';
import SettlementDueTodayCard from 'merchant/views/Settlements/components/SettlementDueTodayCard';
import { render, screen } from 'test-utils';
import { HEADING_INFO } from 'merchant/views/Settlements/components/utils';

describe('SettlementDueTodayCard', () => {
  const App = (props) => <SettlementDueTodayCard {...props} />;

  const currency = 'INR';

  test('should render SettlementDueToday Card', () => {
    const settlementsList = [
      {
        amount: 10099,
        status: 'created',
        created_at: moment().format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Settlement due today')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.SETTLEMENT_DUE_TODAY)).toBeInTheDocument();
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');
    expect(screen.getByText(/Created/)).toBeInTheDocument();
  });

  test('should render SettlementDueToday Card - Delayed case', () => {
    const settlementsList = [
      {
        amount: 10001,
        status: 'created',
        created_at: moment().format('X'),
      },
      {
        amount: 10099,
        status: 'created',
        created_at: moment().subtract(5, 'hours').format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Settlement due today')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.SETTLEMENT_DUE_TODAY)).toBeInTheDocument();
    expect(screen.getByText('201')).toBeInTheDocument();
    expect(screen.getByText('.00')).toBeInTheDocument();

    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');

    expect(screen.getByText(/100.99 Delayed/)).toBeInTheDocument();
  });
});

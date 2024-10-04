import React from 'react';
import moment from 'moment';
import '@testing-library/jest-dom/extend-expect';
import PreviousSettlementCard from 'merchant/views/Settlements/components/PreviousSettlementCard';
import { render, screen } from 'test-utils';
import { HEADING_INFO } from 'merchant/views/Settlements/components/utils';

describe('PreviousSettlementCard', () => {
  const App = (props) => <PreviousSettlementCard {...props} />;

  const currency = 'INR';

  test('should render PreviousSettlement Card', () => {
    const settlementsList = [
      {
        amount: 10099,
        status: 'created',
        created_at: moment().format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Previous settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.PREVIOUS_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');
    expect(screen.getByText(/Created/)).toBeInTheDocument();
  });

  test('should render PreviousSettlement Card - Processed case', () => {
    const settlementsList = [
      {
        amount: 10001,
        status: 'processed',
        created_at: moment().format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Previous settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.PREVIOUS_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('.01')).toBeInTheDocument();

    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');

    expect(screen.getByText(/Processed/)).toBeInTheDocument();
  });

  test('should render PreviousSettlement Card - Failed case', () => {
    const settlementsList = [
      {
        amount: 9999,
        status: 'failed',
        created_at: moment().format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Previous settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.PREVIOUS_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('99')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();

    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');

    expect(screen.getByText(/Failed/)).toBeInTheDocument();
  });

  test('should not render badge for default case', () => {
    const settlementsList = [
      {
        amount: 9999,
        status: 'reversed',
        created_at: moment().format('X'),
      },
    ];
    render(<App settlementsList={settlementsList} currency={currency} />);
    expect(screen.getByText('Previous settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.PREVIOUS_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('99')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();

    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');

    expect(screen.queryByText('Reversed')).not.toBeInTheDocument();
  });
});

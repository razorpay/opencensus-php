import React from 'react';
import moment from 'moment';
import '@testing-library/jest-dom/extend-expect';
import UpcomingSettlementCard from 'merchant/views/Settlements/components/UpcomingSettlementCard';
import { render, screen } from 'test-utils';
import { HEADING_INFO } from 'merchant/views/Settlements/components/utils';

describe('UpcomingSettlementCard', () => {
  const App = (props) => <UpcomingSettlementCard {...props} />;

  test('should render UpcomingSettlement Card', () => {
    const next_settlement = {
      settlement_amount: 9999,
      next_settlement_time: moment().add(3, 'hours').format('X'),
    };
    render(<App next_settlement={next_settlement} currency="INR" />);
    expect(screen.getByText('Upcoming settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.UPCOMING_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('99')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');
  });

  test('should render blocked badge in case of FOH/SOH/Block/No-executions ', () => {
    const settlementConfig = {
      data: {
        config: {
          features: {
            hold: {
              status: true,
            },
          },
        },
      },
    };
    render(<App settlementConfig={settlementConfig} currency="INR" />);
    expect(screen.getByText('Upcoming settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.UPCOMING_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('NA')).toBeInTheDocument();
    expect(screen.getByText('Blocked')).toBeInTheDocument();
  });

  test('should render footer in case the amount is < 1 rupee', () => {
    const current_balance = {
      data: {
        balance: 10099,
      },
    };
    const next_settlement = {
      settlement_amount: 99,
      next_settlement_time: moment().add(3, 'hours').format('X'),
    };
    render(
      <App current_balance={current_balance} next_settlement={next_settlement} currency="INR" />,
    );
    expect(screen.getByText('Upcoming settlement')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.UPCOMING_SETTLEMENT)).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    expect(screen.getByText('Amount more than ₹1 is settled')).toBeInTheDocument();
  });
});

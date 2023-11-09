import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BalanceCard from 'merchant/views/Settlements/components/BalanceCard';
import { render, screen } from 'test-utils';
import { HEADING_INFO } from 'merchant/views/Settlements/components/utils';

const user = {
  merchant: {
    currency: 'INR',
  },
};

describe('BalanceCard', () => {
  const App = (props) => <BalanceCard {...props} />;

  test('should render Balance Card', () => {
    const current_balance = {
      data: {
        balance: 10099,
      },
    };
    render(<App current_balance={current_balance} user={user} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.CURRENT_BALANCE)).toBeInTheDocument();
    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance');
    expect(amountDiv).not.toHaveClass('amount-current-balance negative-balance');
  });

  test('should render Balance Card with negative amount', () => {
    const current_balance = {
      data: {
        balance: -10099,
      },
    };
    render(<App current_balance={current_balance} user={user} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('100')).toBeInTheDocument();
    expect(screen.getByText('.99')).toBeInTheDocument();
    expect(screen.getByText(HEADING_INFO.CURRENT_BALANCE)).toBeInTheDocument();
    const amountDiv = screen.getByLabelText('amount');
    expect(amountDiv).toHaveClass('amount-current-balance negative-balance');
  });

  test('should render Malaysian Currency', () => {
    const current_balance = {
      data: {
        balance: 10099,
      },
    };
    render(<App current_balance={current_balance} user={user} balanceCurrency={'MYR'} />);
    const currencySymbol = screen.getByText('RM');
    expect(currencySymbol).toHaveTextContent('RM');
  });
});

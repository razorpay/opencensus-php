import React from 'react';
import { render, screen } from 'test-utils';

import AmountCard from 'merchant/views/Wallet/Funds/components/AmountCard';

jest.mock('common/components/Shimmer', () => () => <div>Shimmer Loading</div>);

describe('AmountCard tests', () => {
  test('it should render when label, currency and amount is passed', () => {
    render(<AmountCard isLoading={false} label="Total debits" amount={200 * 100} currency="INR" />);

    expect(screen.getByText('Total debits')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('200')).toBeInTheDocument();
  });

  test('it should render when label is passed as a component', () => {
    render(
      <AmountCard
        isLoading={false}
        label={<div>Custom Label</div>}
        amount={200 * 100}
        currency="INR"
      />,
    );
    expect(screen.getByText('Custom Label')).toBeInTheDocument();
  });

  test('it should show shimmer when isLoading=false', () => {
    render(<AmountCard label="Total Debits" isLoading={true} />);
    expect(screen.getByText('Shimmer Loading')).toBeInTheDocument();
  });
});

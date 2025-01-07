import React from 'react';
import { render, screen } from 'test-utils';
import SettlementInfoBar from '../MerchantOverviewData/Settlement/SettlementInfoBar';
import { useBreakpoint } from '@razorpay/blade/utils';

jest.mock('@razorpay/i18nify-js/currency', () => ({
  convertToMajorUnit: jest.fn((amount) => (amount / 100).toFixed(2)),
}));

jest.mock('@razorpay/blade/utils', () => ({
  useBreakpoint: jest.fn(),
}));

jest.mock(
  'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/ViewSettlements',
  () => () => <a href="/settlements">View all settlements</a>,
);

const mockData = {
  hero_card_data: {
    settlement: {
      previous: {
        total_amount: 123456,
        created_at: 929022002,
      },
      current_balance: 789012,
      current_balance_currency: 'INR',
    },
  },
};

const zeroData = {
  hero_card_data: {
    settlement: {
      previous: { total_amount: 0, created_at: 0 },
      current_balance: 0,
      current_balance_currency: 'INR',
    },
  },
};

describe('SettlementInfoBar Component', () => {
  test('should render current balance and last deposit details on large screen', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    render(<SettlementInfoBar data={mockData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('7890.12')).toBeInTheDocument();
    expect(screen.getByText('1234.56')).toBeInTheDocument();
    expect(screen.getByText('Last deposited on June 10')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /view all settlements/i })).toHaveAttribute(
      'href',
      '/settlements',
    );
  });

  test('should render current balance and last deposit details on small screen', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });
    render(<SettlementInfoBar data={mockData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('7890.12')).toBeInTheDocument();
    expect(screen.getByText('1234.56')).toBeInTheDocument();
    expect(screen.getByText('Last Deposit:')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /view all settlements/i })).toHaveAttribute(
      'href',
      '/settlements',
    );
  });

  test('should render current balance and last deposit details on medium screen', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'm' });
    render(<SettlementInfoBar data={mockData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('7890.12')).toBeInTheDocument();
    expect(screen.getByText('1234.56')).toBeInTheDocument();
    expect(screen.getByText('Last deposited on June 10')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /view all settlements/i })).toHaveAttribute(
      'href',
      '/settlements',
    );
  });

  test('should handle zero balance and settlement amounts gracefully', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    render(<SettlementInfoBar data={zeroData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('0.00')).toBeInTheDocument();
  });

  test('should render without errors when no data is passed', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    expect(() => render(<SettlementInfoBar data={null} />)).not.toThrow();
  });
});

import React from 'react';
import { render, screen } from 'test-utils';
import CurrentBalance from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/CurrentBalance';

jest.mock('@razorpay/blade/utils', () => ({
  useBreakpoint: jest.fn(),
}));

const mockData = {
  hero_card_data: {
    settlement: {
      current_balance: 789012,
      current_balance_currency: 'INR',
    },
  },
};

const ZeroBalanceMockData = {
  hero_card_data: {
    settlement: {
      current_balance: 0,
      current_balance_currency: 'INR',
    },
  },
};

const MissingBalanceData = {
  hero_card_data: {
    settlement: {
      current_balance_currency: 'INR',
    },
  },
};

const MissingCurrencyData = {
  hero_card_data: {
    settlement: {
      current_balance: 123456,
    },
  },
};

describe('CurrentBalance Component', () => {
  beforeAll(() => {
    const mockUseBreakpoint = require('@razorpay/blade/utils').useBreakpoint;
    mockUseBreakpoint.mockReturnValue({ matchedBreakpoint: 'xl' });
  });

  test('should render current balance correctly', () => {
    render(<CurrentBalance data={mockData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('7,890')).toBeInTheDocument();
    expect(screen.getByText('.12')).toBeInTheDocument();
  });

  test('should render current balance as 0', () => {
    render(<CurrentBalance data={ZeroBalanceMockData} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('0')).toBeInTheDocument();
    expect(screen.getByText('.00')).toBeInTheDocument();
  });

  test('should render with no data gracefully', () => {
    render(<CurrentBalance data={null} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('0')).toBeInTheDocument();
    expect(screen.getByText('.00')).toBeInTheDocument();
  });

  test('should render current balance as 0 when current_balance is missing', () => {
    render(<CurrentBalance data={MissingBalanceData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('0')).toBeInTheDocument();
    expect(screen.getByText('.00')).toBeInTheDocument();
  });

  test('should default to INR if currency is missing', () => {
    render(<CurrentBalance data={MissingCurrencyData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('1,234')).toBeInTheDocument();
    expect(screen.getByText('.56')).toBeInTheDocument();
  });
  test('should render without errors when no data is passed', () => {
    render(<CurrentBalance data={null} />);
    expect(screen.getByText('Current balance')).toBeInTheDocument();
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('0')).toBeInTheDocument();
    expect(screen.getByText('.00')).toBeInTheDocument();
  });
});

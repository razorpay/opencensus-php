import React from 'react';
import { render, screen } from 'test-utils';
import PreviousSettlements from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/components/PreviousSettlements';
import { useBreakpoint } from '@razorpay/blade/utils';
jest.mock('@razorpay/blade/utils', () => ({
  useBreakpoint: jest.fn(),
}));
describe('PreviousSettlements Component', () => {
  const mockData = {
    hero_card_data: {
      settlement: {
        previous: {
          total_amount: 123456,
          created_at: 929022002,
        },
        current_balance_currency: 'INR',
      },
    },
  };
  const NoPreviousMockData = {
    hero_card_data: {
      settlement: {
        //no previous settlement data
        current_balance_currency: 'INR',
      },
    },
  };
  const MissingCurrencyData = {
    hero_card_data: {
      settlement: {
        previous: {
          total_amount: 789012,
          created_at: 1672531200,
        },
        //no currency
      },
    },
  };

  test('should render previous settlement correctly in large screen', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    render(<PreviousSettlements data={mockData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('1,234')).toBeInTheDocument();
    expect(screen.getByText('.56')).toBeInTheDocument();
    expect(screen.getByText('Last deposited on June 10')).toBeInTheDocument();
  });
  test('should render previous settlement correctly in small screen', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });
    render(<PreviousSettlements data={mockData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('1,234')).toBeInTheDocument();
    expect(screen.getByText('.56')).toBeInTheDocument();
    expect(screen.getByText('Last Deposit:')).toBeInTheDocument();
  });

  test('should default to INR if currency is missing in large screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    render(<PreviousSettlements data={MissingCurrencyData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('7,890')).toBeInTheDocument();
    expect(screen.getByText('.12')).toBeInTheDocument();
    expect(screen.getByText('Last deposited on January 1')).toBeInTheDocument();
  });
  test('should default to INR if currency is missing in small screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });
    render(<PreviousSettlements data={MissingCurrencyData} />);
    expect(screen.getByText('₹')).toBeInTheDocument();
    expect(screen.getByText('7,890')).toBeInTheDocument();
    expect(screen.getByText('.12')).toBeInTheDocument();
    expect(screen.getByText('Last Deposit:')).toBeInTheDocument();
  });

  test('should render no previous settlement data in small screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 's' });
    render(<PreviousSettlements data={NoPreviousMockData} />);
    expect(screen.queryByText('Last Deposit:')).not.toBeInTheDocument();
  });
  test('should render no previous settlement data in large screens', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    render(<PreviousSettlements data={NoPreviousMockData} />);
    expect(screen.queryByText('Last deposited on')).not.toBeInTheDocument();
  });
  test('should render without errors when no data is passed', () => {
    (useBreakpoint as jest.Mock).mockReturnValue({ matchedBreakpoint: 'lg' });
    expect(() => render(<PreviousSettlements data={null} />)).not.toThrow();
  });
});

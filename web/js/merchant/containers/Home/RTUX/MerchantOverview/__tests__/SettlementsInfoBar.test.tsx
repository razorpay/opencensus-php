import React from 'react';
import { render, screen } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';
import SettlementInfoBar from '../MerchantOverviewData/Settlement/SettlementInfoBar';
import { getFormattedDate } from 'merchant_common/containers/ReportsAsync/utils';

jest.mock('common/hooks/useMobile');
jest.mock('@razorpay/i18nify-js/currency', () => ({
  convertToMajorUnit: jest.fn(),
}));
jest.mock('merchant_common/containers/ReportsAsync/utils', () => ({
  getFormattedDate: jest.fn(),
}));

describe('SettlementInfoBar Component', () => {
  const mockUseMobile = useMobile as jest.Mock;
  const mockConvertToMajorUnit = convertToMajorUnit as jest.Mock;
  const mockGetFormattedDate = getFormattedDate as jest.Mock;

  beforeEach(() => {
    mockUseMobile.mockReturnValue(false);
    mockConvertToMajorUnit.mockImplementation((amount) => (amount / 100).toFixed(2));
    mockGetFormattedDate.mockReturnValue('Feb 09, 2023');
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const mockData = {
    hero_card_data: {
      settlement: {
        previous: {
          total_amount: 123456,
          created_at: '2023-02-09T00:00:00Z',
        },
        current_balance: 789012,
        current_balance_currency: 'INR',
      },
    },
  };

  test('should render current balance and last deposit details', () => {
    render(<SettlementInfoBar data={mockData} />);
    expect(screen.getByText('Current Balance')).toBeInTheDocument();
    expect(screen.getByText('7890.12')).toBeInTheDocument();
    expect(screen.getByText('1234.56')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /view all settlements/i })).toHaveAttribute(
      'href',
      '/settlements',
    );
  });
});

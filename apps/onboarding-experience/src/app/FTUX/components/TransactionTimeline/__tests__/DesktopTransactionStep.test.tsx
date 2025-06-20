import React from 'react';
import { screen, fireEvent } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import DesktopTransactionStep from '../DesktopTransactionStep';
import { IMerchantPayments } from '@federated/dashboards/payments/types/payments';
import { analyticsTrackWithUserInfo } from '@libs/shared-utils';

// Mock blade components
jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  ArrowRightIcon: () => 'ArrowRightIcon',
  Amount: jest.fn(({ value }) => <span>{value}</span>),
}));

// Mock shared-utils
jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  i18CurrencyConversionFromMinorUnitToCommonUnit: (paise: number) => paise / 100,
  analyticsTrackWithUserInfo: jest.fn(),
}));

const mockTransaction: IMerchantPayments = {
  id: 'pay_1',
  amount: '50000',
  status: 'captured',
  createdAt: '1672531200', // Jan 1, 2023
  currency: 'INR',
};

describe('DesktopTransactionStep', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Completed Step', () => {
    it('should render transaction details when completed', () => {
      renderWithWrappers(
        <DesktopTransactionStep
          step={1}
          isCompleted={true}
          transaction={mockTransaction}
          isUpcoming={false}
        />,
      );

      expect(screen.getByText('500')).toBeInTheDocument();
      expect(screen.getByText('1 Jan, ‘23')).toBeInTheDocument();
      const viewLink = screen.getByRole('link', { name: /View/i });
      expect(viewLink).toBeInTheDocument();
      expect(viewLink).toHaveAttribute(
        'href',
        `/app/payments/${mockTransaction.id}?init_page=ftux`,
      );
    });

    it('should call analytics on view link click', () => {
      renderWithWrappers(
        <DesktopTransactionStep
          step={1}
          isCompleted={true}
          transaction={mockTransaction}
          isUpcoming={false}
        />,
      );

      const viewLink = screen.getByRole('link', { name: /View/i });
      fireEvent.click(viewLink);

      expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
        objectName: 'FTUX View Transaction Link',
        actionName: 'Clicked',
        screen: 'home page',
        properties: {
          step: 1,
        },
      });
    });

    it('should not render if completed but no transaction is provided', () => {
      const { container } = renderWithWrappers(
        <DesktopTransactionStep
          step={1}
          isCompleted={true}
          transaction={undefined}
          isUpcoming={false}
        />,
      );

      expect(screen.getByText('Transaction 1')).toBeInTheDocument();
      expect(screen.getByText('Pending')).toBeInTheDocument();
      expect(container.querySelector('a')).not.toBeInTheDocument();
    });
  });

  describe('Pending Step', () => {
    it('should render pending state', () => {
      renderWithWrappers(
        <DesktopTransactionStep step={2} isCompleted={false} isUpcoming={false} />,
      );

      expect(screen.getByText('Transaction 2')).toBeInTheDocument();
      expect(screen.getByText('Pending')).toBeInTheDocument();
    });
  });

  describe('Upcoming Step', () => {
    it('should render upcoming state', () => {
      renderWithWrappers(<DesktopTransactionStep step={3} isCompleted={false} isUpcoming={true} />);

      expect(screen.getByText('Transaction 3')).toBeInTheDocument();
      expect(screen.getByText('Upcoming')).toBeInTheDocument();
    });
  });
});

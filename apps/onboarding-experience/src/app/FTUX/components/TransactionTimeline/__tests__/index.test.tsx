import React from 'react';
import { screen, fireEvent } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import TransactionTimeline from '../index';
import {
  IMerchantPayments,
  UpcomingSettlementKeys,
} from '@federated/dashboards/payments/types/payments';

// Mock necessary icons and components from Blade
jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  ArrowRightIcon: () => 'ArrowRightIcon',
}));

// Mock shared-utils
jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  isMobileDevice: jest.fn().mockReturnValue(false),
  i18CurrencyConversionFromMinorUnitToCommonUnit: (paise: number) => paise / 100,
  analyticsTrackWithUserInfo: jest.fn(),
}));

// Mock image imports
jest.mock('../assets/TransactionCompletedIcon.svg', () => 'CompletedIcon');
jest.mock('../assets/TransactionPendingIcon.svg', () => 'PendingIcon');
jest.mock('../assets/TransactionUpcomingIcon.svg', () => 'UpcomingIcon');
jest.mock('../assets/StepperWhiteBase.png', () => 'StepperWhitebg');
jest.mock('../assets/StepperWhiteBaseMobile.png', () => 'StepperWhitebgMobile');
jest.mock('../assets/SteppedBlueBg.png', () => 'StepperBlueBg');
jest.mock('../assets/SteppedBlueBgMobile.png', () => 'StepperBlueBgMobile');

// Mock child components
jest.mock('../DesktopTransactionStep', () => ({
  __esModule: true,
  default: jest.fn(({ transaction, step, isUpcoming, isCompleted }) => (
    <div data-testid={`desktop-step-${step}`}>
      {`Step ${step}, isUpcoming: ${isUpcoming}, isCompleted: ${isCompleted}`}
      {transaction && (
        <div data-testid={`desktop-transaction-${transaction.id}`}>{transaction.amount}</div>
      )}
    </div>
  )),
}));

jest.mock('../SettlementStatusInfo', () => ({
  __esModule: true,
  default: jest.fn(({ settlementData }) => (
    <div data-testid="settlement-status-info">
      {settlementData?.upcoming_settlement
        ? `Next settlement: ${settlementData.upcoming_settlement.next_settlement_time}`
        : 'No settlement data'}
    </div>
  )),
}));

const mockTransactions: IMerchantPayments[] = [
  {
    id: 'pay_1',
    amount: '50000',
    status: 'captured',
    createdAt: '1672531200',
    currency: 'INR',
  },
  {
    id: 'pay_2',
    amount: '10000',
    status: 'captured',
    createdAt: '1672617600',
    currency: 'INR',
  },
];

describe('TransactionTimeline Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (require('@libs/shared-utils').isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  it('should render the heading', () => {
    renderWithWrappers(<TransactionTimeline transactions={[]} />);
    expect(screen.getByText('Your first 5 transactions')).toBeInTheDocument();
  });

  it('should render correctly with no transactions on desktop', () => {
    renderWithWrappers(<TransactionTimeline transactions={[]} />);

    expect(screen.getByTestId('desktop-step-1')).toHaveTextContent('isUpcoming: true');
    expect(screen.getByTestId('desktop-step-1')).toHaveTextContent('isCompleted: false');

    // Check other steps
    for (let i = 2; i <= 5; i++) {
      expect(screen.getByTestId(`desktop-step-${i}`)).toHaveTextContent('isUpcoming: false');
      expect(screen.getByTestId(`desktop-step-${i}`)).toHaveTextContent('isCompleted: false');
    }

    expect(screen.getByTestId('settlement-status-info')).toBeInTheDocument();
  });

  it('should render correctly with some transactions on desktop', () => {
    renderWithWrappers(<TransactionTimeline transactions={mockTransactions} />);

    // Completed transaction
    expect(screen.getByTestId('desktop-step-1')).toHaveTextContent('isCompleted: true');
    expect(screen.getByTestId('desktop-transaction-pay_1')).toBeInTheDocument();

    // Upcoming transaction
    expect(screen.getByTestId('desktop-step-3')).toHaveTextContent('isUpcoming: true');

    // Pending transaction
    expect(screen.getByTestId('desktop-step-4')).toHaveTextContent('isUpcoming: false');
    expect(screen.getByTestId('desktop-step-4')).toHaveTextContent('isCompleted: false');
  });

  it('should render all steps as completed when there are 5 or more transactions', () => {
    const fiveTransactions = [
      { id: 'pay_1', amount: '100', status: 'captured', createdAt: '1', currency: 'INR' },
      { id: 'pay_2', amount: '100', status: 'captured', createdAt: '1', currency: 'INR' },
      { id: 'pay_3', amount: '100', status: 'captured', createdAt: '1', currency: 'INR' },
      { id: 'pay_4', amount: '100', status: 'captured', createdAt: '1', currency: 'INR' },
      { id: 'pay_5', amount: '100', status: 'captured', createdAt: '1', currency: 'INR' },
    ];
    renderWithWrappers(<TransactionTimeline transactions={fiveTransactions} />);

    for (let i = 1; i <= 5; i++) {
      expect(screen.getByTestId(`desktop-step-${i}`)).toHaveTextContent('isCompleted: true');
      expect(screen.getByTestId(`desktop-step-${i}`)).toHaveTextContent('isUpcoming: false');
    }
  });

  describe('Mobile View', () => {
    beforeEach(() => {
      (require('@libs/shared-utils').isMobileDevice as jest.Mock).mockReturnValue(true);
    });

    it('should render correctly with no transactions on mobile', () => {
      renderWithWrappers(<TransactionTimeline transactions={[]} />);
      expect(screen.getByText('Your first 5 transactions')).toBeInTheDocument();
      expect(screen.getByText('Your first transaction is pending')).toBeInTheDocument();
    });

    it('should render correctly with transactions on mobile', () => {
      renderWithWrappers(<TransactionTimeline transactions={mockTransactions} />);
      expect(screen.getByText('Your first 5 transactions')).toBeInTheDocument();
      const summary = screen.getByText(/2nd Transaction of/);
      expect(summary).toBeInTheDocument();
      expect(screen.getByText('View')).toBeInTheDocument();
    });

    it('should call analytics on view link click', () => {
      renderWithWrappers(<TransactionTimeline transactions={mockTransactions} />);
      const viewLink = screen.getByRole('link', { name: /view/i });
      fireEvent.click(viewLink);
      expect(require('@libs/shared-utils').analyticsTrackWithUserInfo).toHaveBeenCalledWith({
        objectName: 'FTUX Last Transaction Link',
        actionName: 'Clicked',
        screen: 'home page',
      });
    });
  });

  it('should pass settlementData to SettlementStatusInfo', () => {
    const settlementData = {
      current_balance: 440000,
      current_balance_currency: 'INR' as any,
      settlement_currency: 'INR' as any,
      upcoming_settlement: {
        settlement_amount: 46000,
        next_settlement_time: 0,
        title_key: UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK,
      },
    };
    renderWithWrappers(<TransactionTimeline transactions={[]} settlementData={settlementData} />);
    expect(screen.getByTestId('settlement-status-info')).toHaveTextContent('Next settlement: 0');
  });
});

import React from 'react';
import { screen, fireEvent } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import SettlementStatusInfo from '../SettlementStatusInfo';
import {
  ISettlementData,
  UpcomingSettlementKeys,
} from '@federated/dashboards/payments/types/payments';
import { isMobileDevice, analyticsTrackWithUserInfo } from '@libs/shared-utils';

// Mock blade components
jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  ArrowRightIcon: () => 'ArrowRightIcon',
  Amount: jest.fn(({ value }) => <span>{value}</span>),
}));

// Mock shared-utils
jest.mock('@libs/shared-utils', () => ({
  ...jest.requireActual('@libs/shared-utils'),
  isMobileDevice: jest.fn(),
  i18CurrencyConversionFromMinorUnitToCommonUnit: (paise: number) => paise / 100,
  analyticsTrackWithUserInfo: jest.fn(),
}));

// Mock date formatter
jest.mock('@OnboardingExperienceCommons/utils/dateAndTime', () => ({
  getFormattedDateFromTimestamp: jest.fn(
    (timestamp) => `Formatted: ${new Date(timestamp * 1000).toDateString()}`,
  ),
}));

// Mock window.open
global.open = jest.fn();

const mockSettlementData: ISettlementData = {
  current_balance: 100000,
  current_balance_currency: 'INR',
  settlement_currency: 'INR',
  upcoming_settlement: {
    settlement_amount: 50000,
    next_settlement_time: 1672617600, // Mon Jan 02 2023
    title_key: UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK,
  },
};

describe('SettlementStatusInfo', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false); // Default to desktop
  });

  describe('Desktop View', () => {
    it('should render upcoming settlement info correctly', () => {
      renderWithWrappers(<SettlementStatusInfo settlementData={mockSettlementData} />);

      expect(screen.getByText(/Upcoming settlement of/)).toBeInTheDocument();
      expect(screen.getByText('500')).toBeInTheDocument(); // settlement_amount
      expect(screen.getByText(/from a total balance of/)).toBeInTheDocument();
      expect(screen.getByText('1000')).toBeInTheDocument(); // current_balance
      expect(screen.getByText(/will be deposited on/)).toBeInTheDocument();
      const dateString = new Date(1672617600 * 1000).toDateString();
      expect(screen.getByText(new RegExp(`Formatted: ${dateString}\\.`))).toBeInTheDocument();
    });

    it('should render "No upcoming settlements" message when there is no upcoming settlement', () => {
      const settlementDataNoUpcoming = {
        ...mockSettlementData,
        upcoming_settlement: undefined,
      };
      renderWithWrappers(<SettlementStatusInfo settlementData={settlementDataNoUpcoming} />);

      expect(screen.getByText('No upcoming settlements.')).toBeInTheDocument();
    });

    it('should render "No upcoming settlements" message when title_key is not ON_TRACK', () => {
      const settlementDataWrongKey = {
        ...mockSettlementData,
        upcoming_settlement: {
          ...(mockSettlementData.upcoming_settlement as any),
          title_key: 'some_other_key' as UpcomingSettlementKeys,
        },
      };
      renderWithWrappers(<SettlementStatusInfo settlementData={settlementDataWrongKey} />);

      expect(screen.getByText('No upcoming settlements.')).toBeInTheDocument();
    });

    it('should render balance info and View All link', () => {
      renderWithWrappers(<SettlementStatusInfo settlementData={mockSettlementData} />);

      expect(screen.getByText(/Keep your balance above/)).toBeInTheDocument();
      expect(screen.getByText('1')).toBeInTheDocument();
      expect(screen.getByText(/to settle./)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: 'View All ArrowRightIcon' })).toBeInTheDocument();
    });
  });

  describe('Mobile View', () => {
    beforeEach(() => {
      (isMobileDevice as jest.Mock).mockReturnValue(true);
    });

    it('should render upcoming settlement info correctly for mobile', () => {
      renderWithWrappers(<SettlementStatusInfo settlementData={mockSettlementData} />);

      expect(screen.queryByText('Upcoming settlement of')).not.toBeInTheDocument();
      expect(screen.getByText('500')).toBeInTheDocument();
      expect(screen.getByText(/from your/)).toBeInTheDocument();
      expect(screen.getByText('1000')).toBeInTheDocument();
      expect(screen.getByText(/balance will be settled on/)).toBeInTheDocument();
      const dateString = new Date(1672617600 * 1000).toDateString();
      expect(screen.getByText(new RegExp(`Formatted: ${dateString}\\.`))).toBeInTheDocument();
    });
  });

  describe('User Actions', () => {
    it('should call analytics and window.open on "View All" click', () => {
      renderWithWrappers(<SettlementStatusInfo settlementData={mockSettlementData} />);

      const viewAllLink = screen.getByRole('button', { name: 'View All ArrowRightIcon' });
      fireEvent.click(viewAllLink);

      expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
        objectName: 'FTUX View All Settlements Link',
        actionName: 'Clicked',
        screen: 'home page',
      });
      expect(global.open).toHaveBeenCalledWith('/app/settlements', '_blank');
    });
  });
});

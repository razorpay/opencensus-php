import React from 'react';

import { render, screen, waitForLoadingToFinish } from 'common/services/test/test-utils';
import List from 'merchant/views/PartnerDashboard/SubMerchant/List';

const mockIsConfigTagEnabled = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: mockIsConfigTagEnabled }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: jest.fn(),
  }),
}));

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
  isPartnershipCapitalBureauLinkEnabled: true,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hocs/withPartnerDashboardExperiments', () => {
  return {
    __esModule: true,
    default: (Component) => (props) =>
      <Component {...props} experiments={mockPartnerDashboardExperiments} />,
  };
});
const isPartner = jest.fn();
const isPartnerIntent = jest.fn();
const isFeatureEnabled = jest.fn();
const instantActivation = { isWhitelistFlow: false };
const isOrgAllowedFunctionality = jest.fn();

const state = {
  session: {
    user: {
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
      isOrgAllowedFunctionality,
    },
  },
};
const location = {
  search: '',
  pathname: '/partners/submerchants',
};

const renderApp = (newState = state, props = {}, experiments = {}) => {
  mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
  render(<List location={location} {...props} />, {
    initialState: {
      ...newState,
    },
  });
};

describe('List', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });
  it('should render all the affliate accounts tab', () => {
    isPartner.mockImplementation((value) => {
      if (value === 'pure_platform') return false;
      return true;
    });
    mockIsConfigTagEnabled.mockReturnValue(false);
    renderApp(state);
    expect(screen.getByText('Payments')).toBeInTheDocument();
    expect(screen.getByText('RazorpayX')).toBeInTheDocument();
    expect(screen.getByText('Line Of Credit')).toBeInTheDocument();
  });

  it('should not render Line of Credit if the feature is not enabled', () => {
    const newState = {
      session: {
        user: {
          ...state.session.user,
          isPartnershipForCapitalEnabled: false,
        },
      },
    };
    renderApp(newState);
    expect(screen.queryByText('Line Of Credit')).not.toBeInTheDocument();
  });

  it('should render Invites flow navlinks if the feature is enabled', async () => {
    renderApp(state, {}, { isPartnershipsInviteFlowEnabled: true });
    await waitForLoadingToFinish();
    expect(screen.getByText('All Invites')).toBeInTheDocument();
    expect(screen.getByText('Accepted Invites')).toBeInTheDocument();
  });

  describe('should not render RazorpayX if...', () => {
    beforeEach(() => {
      isPartner.mockImplementation((value) => {
        if (value === 'pure_platform') return false;
        return true;
      });
    });

    test('...the merchant is not from india (international merchants)', () => {
      mockIsConfigTagEnabled.mockImplementation((value) => {
        if (value === 'partnership.razorpay_x_affiliate_account') return true;
        return false;
      });
      renderApp(state);
      expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    });
  });

  // Todo add more tests for analytics
});

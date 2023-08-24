import React from 'react';
import { render, screen, waitForLoadingToFinish } from 'common/services/test/test-utils';
import List from 'merchant/views/PartnerDashboard/SubMerchant/List';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

const isPartner = jest.fn();
const isPartnerIntent = jest.fn();
const isFeatureEnabled = jest.fn();
const instantActivation = { isWhitelistFlow: false };
const findTag = jest.fn();

const state = {
  session: {
    user: {
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      findTag,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      isPartnershipsInviteFlowEnabled: false,
      instantActivation,
    },
  },
};
const location = {
  search: '',
  pathname: '/partners/submerchants',
};

const renderApp = (newState = state) => {
  render(<List location={location} />, {
    initialState: {
      ...newState,
    },
  });
};

describe('List', () => {
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
    findTag.mockReturnValue(false);
    renderApp();
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
    const newState = {
      session: {
        user: {
          ...state.session.user,
          isPartnershipsInviteFlowEnabled: true,
        },
      },
    };
    renderApp(newState);
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
      findTag.mockImplementation((value) => {
        if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.RazorpayXAffiliateAccount) return true;
        return false;
      });
      renderApp();
      expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    });
  });

  // Todo add more tests for analytics
});

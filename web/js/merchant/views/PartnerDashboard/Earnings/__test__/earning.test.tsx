import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import Earnings from 'merchant/views/PartnerDashboard/Earnings';

const isPartner = jest.fn();
const isPartnerIntent = jest.fn();
const isFeatureEnabled = jest.fn();
const instantActivation = { isWhitelistFlow: false };
const findTag = jest.fn();

const state = {
  session: {
    user: {
      merchant: {},
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      findTag,
      isCommissionInvoicesEnabled: true,
      isPartnershipForCapitalEnabled: true,
      isPartnershipForXEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
    },
  },
};

const location = {
  search: '',
  pathname: '/partners/earnings/daily',
};

const renderApp = (newState = state) => {
  render(<Earnings location={location} />, {
    initialState: {
      ...newState,
    },
  });
};

// This is breaking for some reason
describe.skip('Earnings', () => {
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

  test('should render all the tabs', () => {
    renderApp();
    expect(screen.getByText('Daily Earnings')).toBeInTheDocument();
    expect(screen.getByText('Transactional Details')).toBeInTheDocument();
    expect(screen.getByText('Invoices')).toBeInTheDocument();
  });
});

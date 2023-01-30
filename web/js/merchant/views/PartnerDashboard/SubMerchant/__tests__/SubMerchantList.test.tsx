import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'common/services/test/test-utils';
import SubMerchantList from 'merchant/views/PartnerDashboard/SubMerchant/List';

// TODO : covered only Capital use case, have to cover others later

const isPartner = jest.fn();
const isPartnerIntent = jest.fn();
const instantActivation = { isWhitelistFlow: false };
const state = {
  session: {
    user: {
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
    },
  },
};

const match = {
  path: '/partners/submerchants',
  isExact: false,
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

  const renderApp = () => {
    return render(<SubMerchantList match={match} />, {
      initialState: {
        ...state,
      },
    });
  };

  test('should render component with default props', () => {
    renderApp();

    expect(screen.getByText('Payments Affiliate Accounts'));
    expect(screen.getByText('Corporate Credit Card Affiliate Accounts'));
  });
});

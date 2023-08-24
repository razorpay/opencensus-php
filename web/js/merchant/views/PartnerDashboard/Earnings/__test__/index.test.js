import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import Earnings from 'merchant/views/PartnerDashboard/Earnings';
import cloneDeep from 'lodash/cloneDeep';

const instantActivation = { isWhitelistFlow: false };

const initialState = {
  session: {
    user: {
      merchant: {},
      isOrgRZP: true,
      isPartner: (partner_type) => partner_type == 'reseller',
      isPartnerIntent: () => true,
      isFeatureEnabled: () => true,
      findTag: () => true,
      isCommissionInvoicesEnabled: true,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
      isOrgAllowedFunctionality: () => false,
    },
    org: {
      custom_code: 'rzp',
      business_name: 'Razorpay',
    },
  },
};

const location = {
  search: '',
  pathname: '/partners/earnings/daily',
};

describe('Earnings', () => {
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

  test('should not render transactional tab for reseller', async () => {
    render(<Earnings location={location} />, {
      initialState,
    });
    await waitFor(() => {
      expect(screen.getByText('Daily Earnings')).toBeVisible();
      expect(screen.queryByText('Transactional Details')).not.toBeInTheDocument();
      expect(screen.getByText('Invoices')).toBeVisible();
    });
  });

  test('should render all the tabs for aggregator', async () => {
    const initialStateCopy = cloneDeep(initialState);
    initialStateCopy.session.user.isPartner = (partner_type) => partner_type == 'aggregator';
    render(<Earnings location={location} />, {
      initialState: initialStateCopy,
    });
    await waitFor(() => {
      expect(screen.getByText('Daily Earnings')).toBeVisible();
      expect(screen.getByText('Transactional Details')).toBeVisible();
      expect(screen.getByText('Invoices')).toBeVisible();
    });
  });
});

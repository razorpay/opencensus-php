import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import TwoFaVerificationContextProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';
import ProductTabsWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

import { staticUserExtra, staticLocation as defaultLocation } from './mocks/fixtures';

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPartnershipsForPosEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const isPartner = jest.fn();
const isFeatureEnabled = jest.fn();
const defaultUserExtra = {
  ...staticUserExtra,
  isPartner,
  isFeatureEnabled,
};
let mockLocation = defaultLocation;
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: () => mockLocation,
}));

const renderApp = (
  { userExtra = {}, orgExtra = {}, initialEntries = ['/'] }: any = {},
  props = {},
  experiments = {},
) => {
  mockPartnerDashboardExperiments = {
    ...defaultPartnerDashboardExperiments,
    ...experiments,
  };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      ...defaultUserExtra,
      ...userExtra,
    },
    orgExtra,
  });
  render(
    <TwoFaVerificationContextProvider>
      <ProductTabsWrapper {...props} />
    </TwoFaVerificationContextProvider>,
    {
      showModal: true,
      initialEntries,
      path: '',
      initialState: {
        session,
      },
    },
  );
};

describe('ProductTabsWrapper', () => {
  describe('SideHeader', () => {
    beforeEach(() => {
      document.execCommand = jest.fn();
      server.use(fetchReferralsHandler());
      isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
    });
    afterEach(() => {
      jest.clearAllMocks();
      mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
      mockLocation = defaultLocation;
    });

    test('should render Refer merchant modal after clicking Refer button', async () => {
      const productType = PRODUCT_TYPE.PG;
      renderApp({}, { productType });

      await userEvent.click(screen.getByRole('button', { name: 'Share Referral Link' }));
      await userEvent.click(screen.getByText('Razorpay Payments'));

      await waitFor(() => {
        expect(screen.getByRole('button', { name: 'Copy Link' })).toBeInTheDocument();
      });
    }, 20000);

    test('should render Add merchant modal after clicking Add button', async () => {
      const productType = PRODUCT_TYPE.X;
      renderApp({}, { productType });

      await userEvent.click(screen.getByRole('button', { name: 'Add New Clients' }));

      await waitFor(() => {
        expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
      });
    });
  });
});

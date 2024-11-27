import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import TwoFaVerificationContextProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';
import ClientAccounts from 'merchant/views/PartnerDashboard/ClientAccounts';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
import {
  render,
  screen,
  server,
  updateUseI18ServiceSpy,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

import { defaultUserExtra } from './mocks/fixtures';

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPartnershipsForPosEnabled: true,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));
const isPartner = jest.fn();
const isFeatureEnabled = jest.fn();
const location = {
  search: '',
  pathname: '/partners/submerchants',
};

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: () => location,
}));

const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, props = {}, experiments = {}) => {
  mockPartnerDashboardExperiments = {
    ...defaultPartnerDashboardExperiments,
    ...experiments,
  };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      isPartner,
      isFeatureEnabled,
      ...defaultUserExtra,
      ...userExtra,
    },
    orgExtra,
  });
  render(
    <TwoFaVerificationContextProvider>
      <ClientAccounts {...props} />
    </TwoFaVerificationContextProvider>,
    {
      initialState: {
        session,
      },
    },
  );
};

describe('ClientAccounts', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
  });
  beforeEach(() => {
    isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
    server.use(fetchReferralsHandler());
  });
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  it('should render all the default Affliate accounts tabs', () => {
    updateUseI18ServiceSpy('false-path');
    renderApp();
    expect(screen.getByText('Payments')).toBeInTheDocument();
    expect(screen.getByText('POS')).toBeInTheDocument();
    expect(screen.getByText('Line Of Credit')).toBeInTheDocument();
    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
  });

  it('should render only POS if isPartnerAgentRole is true and the feature is enabled', () => {
    renderApp(
      { userExtra: { isPartnerAgentRole: true } },
      {},
      {
        isPartnershipsInviteFlowEnabled: true,
        isPartnershipsForPosEnabled: true,
      },
    );
    expect(screen.queryByText('Payments')).not.toBeInTheDocument();
    expect(screen.queryByText('Line of Credit')).not.toBeInTheDocument();
    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    expect(screen.getByText('POS')).toBeInTheDocument();
  });

  it('should not render POS if the feature is not enabled', () => {
    renderApp(
      {},
      {},
      {
        isPartnershipsInviteFlowEnabled: true,
        isPartnershipsForPosEnabled: false,
      },
    );
    expect(screen.queryByText('POS')).not.toBeInTheDocument();
    expect(screen.getByText('RazorpayX')).toBeInTheDocument();
  });

  it('should not render Line of Credit if the feature is not enabled', () => {
    renderApp({ userExtra: { isPartnershipForCapitalEnabled: false } });
    expect(screen.queryByText('Line Of Credit')).not.toBeInTheDocument();
  });

  it('should render Invites flow navlinks if the feature is enabled', async () => {
    server.use(allInvitesListSuccess());
    renderApp({}, {}, { isPartnershipsInviteFlowEnabled: true });
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('All Invites')).toBeInTheDocument();
    expect(screen.getByText('Accepted Invites')).toBeInTheDocument();
  });

  describe('should not render RazorpayX if...', () => {
    beforeEach(() => {
      isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
    });

    test('...the merchant is not from india (international merchants)', () => {
      updateUseI18ServiceSpy('partnership.razorpay_x_affiliate_account');
      renderApp();
      expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    });
  });
});

import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import InviteMerchantTabs from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent, updateUseI18ServiceSpy } from 'test-utils';

const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  shouldShowHeaderAndTabs: true,
  setShowHeaderAndTabs: jest.fn(),
  setShouldShowFooter: jest.fn(),
  onInviteTabsBackClick: jest.fn(),
  onDismiss: jest.fn(),
};

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab',
  () => ({
    __esModule: true,
    default: () => <>SingleInviteTab</>,
  }),
);
jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleAddMerchant',
  () => ({
    __esModule: true,
    default: () => <>SingleAddMerchant</>,
  }),
);
jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleOAuthInvite',
  () => ({
    __esModule: true,
    default: () => <>SingleOAuthInvite</>,
  }),
);

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab',
  () => ({
    __esModule: true,
    default: () => <>BulkInviteTab</>,
  }),
);

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkAddMerchant',
  () => ({
    __esModule: true,
    default: () => <>BulkAddMerchant</>,
  }),
);

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/BulkInviteTab/BulkOAuthInvite',
  () => ({
    __esModule: true,
    default: () => <>BulkOAuthInvite</>,
  }),
);

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab',
  () => ({
    __esModule: true,
    default: () => <>PublicLinksTab</>,
  }),
);
jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab/PublicOAuthLinks',
  () => ({
    __esModule: true,
    default: () => <>PublicOAuthLinks</>,
  }),
);
const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: true,
  isPartnershipsForPosEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const defaultUserExtra = {
  findTag: jest.fn(),
  isPartnershipForCapitalEnabled: true,
};
const defaultOrgExtra = {};
describe('InviteMerchantTabs', () => {
  const renderApp = (
    props = {},
    { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {},
    experiments = {},
  ) => {
    mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<InviteMerchantTabs {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });

  test('should show correct tabs content for Reseller PG Invite Flow', async () => {
    renderApp();
    expect(screen.getByText('SingleInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Bulk Upload'));
    expect(screen.getByText('BulkInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Public Link'));
    expect(screen.getByText('PublicLinksTab')).toBeInTheDocument();
  });

  test('should show correct tabs content for OAuth PG Invite Flow', async () => {
    renderApp({}, {}, { isPlatformPartnerInviteFlowEnabled: true });
    expect(screen.getByText('SingleOAuthInvite')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Bulk Upload'));
    expect(screen.getByText('BulkOAuthInvite')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Public Link'));
    expect(screen.getByText('PublicOAuthLinks')).toBeInTheDocument();
  });

  test('should hide tab headers when shouldShowHeaderAndTabs = false ', () => {
    renderApp({ shouldShowHeaderAndTabs: false });
    expect(screen.queryByText('Using Email')).not.toBeVisible();
    expect(screen.queryByText('Public Link')).not.toBeVisible();
    expect(screen.getByText('SingleInviteTab')).toBeInTheDocument();
  });

  test('should show correct tabs content and hide Single Invite tab for Capital', async () => {
    renderApp({ productType: PRODUCT_TYPE.CAPITAL });
    expect(screen.queryByText('Using Email')).not.toBeInTheDocument();

    await userEvent.click(screen.getByText('Bulk Upload'));
    expect(screen.getByText('BulkAddMerchant')).toBeInTheDocument();

    await userEvent.click(screen.getByText('Public Link'));
    expect(screen.getByText('PublicLinksTab')).toBeInTheDocument();
  });

  test('should show correct tabs content for POS Invite Flow', async () => {
    renderApp(
      { productType: PRODUCT_TYPE.POS },
      {},
      { isPartnershipsInviteFlowEnabled: false, isPartnershipsForPosEnabled: true },
    );
    expect(screen.getByText('SingleInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Bulk Upload'));
    expect(screen.getByText('BulkInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Public Link'));
    expect(screen.getByText('PublicLinksTab')).toBeInTheDocument();
  });

  test('should hide Public Links tab with international flag', () => {
    // Enable all options
    updateUseI18ServiceSpy('partnership.referral_links');
    renderApp(
      { productType: PRODUCT_TYPE.PG },
      { userExtra: { isPartnershipForCapitalEnabled: true } },
    );
    expect(screen.queryByText('Public Link')).not.toBeInTheDocument();
    expect(screen.getByText('Using Email')).toBeInTheDocument();
    expect(screen.getByText('Bulk Upload')).toBeInTheDocument();
  });
});

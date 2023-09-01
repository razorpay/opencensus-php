import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import InviteMerchantTabs from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent } from 'test-utils';

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
  'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab',
  () => ({
    __esModule: true,
    default: () => <>PublicLinksTab</>,
  }),
);

jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => ({ isEasierAccessToSubmerchantKycEnabled: true }),
}));

const defaultUserExtra = {
  findTag: jest.fn(),
  isPartnershipForCapitalEnabled: true,
};
const defaultOrgExtra = {};
describe('InviteMerchantTabs', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
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
  });

  test('should show correct tab content on switching tabs', async () => {
    renderApp();
    expect(screen.getByText('SingleInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Bulk Upload'));
    expect(screen.getByText('BulkInviteTab')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Public Link'));
    expect(screen.getByText('PublicLinksTab')).toBeInTheDocument();
  });
  test('should hide tab headers when shouldShowHeaderAndTabs = false ', () => {
    renderApp({ shouldShowHeaderAndTabs: false });
    expect(screen.queryByText('Using Email')).not.toBeVisible();
    expect(screen.queryByText('Public Link')).not.toBeVisible();
    expect(screen.getByText('SingleInviteTab')).toBeInTheDocument();
  });
  test('should hide Single Invite tab for Capital', () => {
    renderApp({ productType: PRODUCT_TYPE.CAPITAL });
    expect(screen.queryByText('Using Email')).not.toBeInTheDocument();
    expect(screen.getByText('Bulk Upload')).toBeInTheDocument();
    expect(screen.getByText('Public Link')).toBeInTheDocument();
  });
  test('should hide Public Links tab with international flag', () => {
    // Enable all options
    const findTag = jest.fn();
    findTag.mockImplementation((value) => {
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.AddNewRazorpayXMerchant) return false;
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.ReferalLinks) return true;
      return true;
    });

    renderApp(
      { productType: PRODUCT_TYPE.PG },
      { userExtra: { findTag, isPartnershipForCapitalEnabled: true } },
    );
    expect(screen.queryByText('Public Link')).not.toBeInTheDocument();
    expect(screen.getByText('Using Email')).toBeInTheDocument();
    expect(screen.getByText('Bulk Upload')).toBeInTheDocument();
  });
});

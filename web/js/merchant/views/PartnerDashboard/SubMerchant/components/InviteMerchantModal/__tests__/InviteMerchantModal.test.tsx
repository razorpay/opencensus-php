import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import { INVITE_MERCHANT_STEPS } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/constants';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent, waitFor } from 'test-utils';
const { SELECT_PRODUCT, INVITE_TABS } = INVITE_MERCHANT_STEPS;
const defaultProps = {
  initialProductType: PRODUCT_TYPE.PG,
  initialStep: SELECT_PRODUCT,
  isOpen: true,
  onDismiss: jest.fn(),
};

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
  isPartnershipsForPosEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));
const defaultUserExtra = {
  findTag: jest.fn(),
  isPartner: (partner_type = 'reseller') => partner_type === 'reseller',
  isOrgAllowedFunctionality: () => true,
  isPartnershipForCapitalEnabled: false,
  isPartnerAgentRole: false,
};
const defaultOrgExtra = {
  business_name: 'Razorpay',
};
describe('InviteMerchantModal', () => {
  const renderApp = async (
    { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {},
    props = {},
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
    const response = render(<InviteMerchantModal {...defaultProps} {...props} />, {
      initialState: { session },
    });

    // lazy loaded components
    await waitFor(() => {
      expect(screen.queryByRole('loader')).not.toBeInTheDocument();
    });
    return response;
  };
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });

  test('should show correct header for partnerships invite flow', async () => {
    await renderApp(
      {},
      { initialProductType: PRODUCT_TYPE.PG },
      { isPartnershipsInviteFlowEnabled: true },
    );
    expect(screen.getByText('Add New Clients')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Next'));
    expect(screen.getByText('Add New Clients - Razorpay Payments')).toBeInTheDocument();
  });

  test('should update header when product type is changed', async () => {
    await renderApp({}, { initialStep: SELECT_PRODUCT });
    expect(screen.getByText('Add New Merchants')).toBeInTheDocument();
    await userEvent.click(screen.getByText('RazorpayX'));
    await userEvent.click(screen.getByText('Next'));
    expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
  });

  test('should directly open invite form for capital with initialStep = INVITE_TABS', async () => {
    await renderApp(
      {
        userExtra: {
          isPartnershipForCapitalEnabled: true,
        },
      },
      { initialStep: INVITE_TABS, initialProductType: PRODUCT_TYPE.CAPITAL },
    );
    expect(screen.queryByText('Using Email')).not.toBeInTheDocument();
    expect(screen.getByText('Bulk Upload')).toBeInTheDocument();
    expect(screen.getByText('Public Link')).toBeInTheDocument();
    expect(screen.getByText('Add New Merchants - Line Of Credit')).toBeInTheDocument();
  });
});

import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import { INVITE_MERCHANT_STEPS } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/constants';
import { getInitialUserOrgState } from 'common/tests/utils';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
const { SELECT_PRODUCT, INVITE_TABS } = INVITE_MERCHANT_STEPS;
const defaultProps = {
  initialProductType: PRODUCT_TYPE.PG,
  initialStep: SELECT_PRODUCT,
  isOpen: true,
  onDismiss: jest.fn(),
};
const defaultUserExtra = {
  findTag: jest.fn(),
  isOrgAllowedFunctionality: () => true,
};
const defaultOrgExtra = {
  business_name: 'Razorpay',
};
describe('InviteMerchantModal', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<InviteMerchantModal {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should show correct header for partnerships invite flow', async () => {
    renderApp(
      { initialProductType: PRODUCT_TYPE.PG },
      {
        userExtra: {
          isPartnershipsInviteFlowEnabled: true,
        },
      },
    );
    expect(screen.getByText('Add New Clients')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Next'));
    expect(screen.getByText('Add New Clients - Razorpay Payments')).toBeInTheDocument();
  });

  test('should update header when product type is changed', async () => {
    renderApp({ initialStep: SELECT_PRODUCT });
    expect(screen.getByText('Add New Merchants')).toBeInTheDocument();
    await userEvent.click(screen.getByText('RazorpayX'));
    await userEvent.click(screen.getByText('Next'));
    expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
  });

  test('should directly open invite form for capital with initialStep = INVITE_TABS', () => {
    renderApp(
      { initialStep: INVITE_TABS, initialProductType: PRODUCT_TYPE.CAPITAL },
      {
        userExtra: {
          isPartnershipForCapitalEnabled: true,
        },
      },
    );
    expect(screen.queryByText('Using Email')).not.toBeInTheDocument();
    expect(screen.getByText('Public Link')).toBeInTheDocument();
    expect(screen.getByText('Bulk Upload')).toBeInTheDocument();
    expect(screen.getByText('Add New Merchants - Line Of Credit')).toBeInTheDocument();
  });
});

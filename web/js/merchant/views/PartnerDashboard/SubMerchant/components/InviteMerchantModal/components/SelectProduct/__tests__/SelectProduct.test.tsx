import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import SelectProduct from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SelectProduct';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent } from 'test-utils';
const defaultProps = {
  orgName: 'Razorpay',
  productType: null,
  setProductType: jest.fn(),
  onNextClick: jest.fn(),
};
const defaultUserExtra = {
  isPartner: (partner_type = 'reseller') => partner_type === 'reseller',
  findTag: jest.fn(),
  isPartnershipForCapitalEnabled: true,
  isPartnerAgentRole: false,
};

const defaultPartnerDashboardExperiments = {
  isPartnershipsForPosEnabled: false,
};
let mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments };
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

describe('SelectProduct', () => {
  const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, props = {}, experiments = {}) => {
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
    // eslint-disable-next-line
    // @ts-ignore
    render(<SelectProduct {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test('should fire callbacks on click events', async () => {
    renderApp(
      { userExtra: { isPartnershipForCapitalEnabled: true } },
      { productType: PRODUCT_TYPE.PG },
    );
    await userEvent.click(screen.getByText('RazorpayX'));
    // Call parent's setProductType prop
    expect(defaultProps.setProductType).toHaveBeenCalledWith(PRODUCT_TYPE.X);
    await userEvent.click(screen.getByText('Razorpay Payments'));
    expect(defaultProps.setProductType).toHaveBeenCalledWith(PRODUCT_TYPE.PG);
    await userEvent.click(screen.getByText('Line of credit'));
    expect(defaultProps.setProductType).toHaveBeenCalledWith(PRODUCT_TYPE.CAPITAL);

    await userEvent.click(screen.getByText('Next'));
    expect(defaultProps.onNextClick).toHaveBeenCalled();
  });
  test('show Line of credit when isPartnershipForCapitalEnabled is true', () => {
    renderApp(
      { userExtra: { isPartnershipForCapitalEnabled: true } },
      { productType: PRODUCT_TYPE.PG },
    );

    expect(screen.getByText('Line of credit')).toBeInTheDocument();
  });
  test('hide Banking when the hidden features tag AddNewRazorpayXMerchant is present or if the product type is POS', () => {
    const findTag = jest.fn();
    findTag.mockImplementation((value) => {
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.AddNewRazorpayXMerchant) return true;
      return false;
    });
    renderApp({}, {}, { isPartnershipsForPosEnabled: true });
    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
  });
  test('show POS option if experiment is enabled and product type is POS should fire callback with POS when clicked', async () => {
    renderApp({}, { productType: PRODUCT_TYPE.POS }, { isPartnershipsForPosEnabled: true });
    const mainText = screen.getByText('Razorpay POS');
    expect(mainText).toBeInTheDocument();
    await userEvent.click(mainText);
    expect(defaultProps.setProductType).toHaveBeenCalledWith(PRODUCT_TYPE.POS);
  });

  test('should render only POS if isPartnerAgentRole is true and the feature is enabled', () => {
    renderApp(
      { userExtra: { isPartnerAgentRole: true } },
      {
        productType: PRODUCT_TYPE.POS,
      },
      {
        isPartnershipsInviteFlowEnabled: true,
        isPartnershipsForPosEnabled: true,
      },
    );
    expect(screen.queryByText('Razorpay Payments')).not.toBeInTheDocument();
    expect(screen.queryByText('Line of Credit')).not.toBeInTheDocument();
    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
    expect(screen.getByText('Razorpay POS')).toBeInTheDocument();
  });
});

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
  findTag: jest.fn(),
  isPartnershipForCapitalEnabled: true,
};
const defaultOrgExtra = {};

const defaultPartnerDashboardExperiments = {
  isPartnershipsForPosEnabled: false,
};
let mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments };
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

describe('SelectProduct', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
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
    // Enable all options
    const findTag = jest.fn();
    findTag.mockImplementation((value) => {
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.AddNewRazorpayXMerchant) return false;
      return true;
    });

    renderApp(
      { productType: PRODUCT_TYPE.PG },
      { userExtra: { findTag, isPartnershipForCapitalEnabled: true } },
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
      { productType: PRODUCT_TYPE.PG },
      { userExtra: { isPartnershipForCapitalEnabled: true } },
    );

    expect(screen.getByText('Line of credit')).toBeInTheDocument();
  });
  test('hide Banking when the hidden features tag AddNewRazorpayXMerchant is present or if the product type is POS', () => {
    const findTag = jest.fn();
    findTag.mockImplementation((value) => {
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.AddNewRazorpayXMerchant) return true;
      return false;
    });
    mockPartnerDashboardExperiments = {
      ...defaultPartnerDashboardExperiments,
      isPartnershipsForPosEnabled: true,
    };

    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
  });
  test('show POS option if experiment is enabled and product type is POS should fire callback with POS when clicked', async () => {
    mockPartnerDashboardExperiments = {
      ...defaultPartnerDashboardExperiments,
      isPartnershipsForPosEnabled: true,
    };
    renderApp({ productType: PRODUCT_TYPE.POS });
    const mainText = screen.getByText('Razorpay POS');
    expect(mainText).toBeInTheDocument();
    await userEvent.click(mainText);
    expect(defaultProps.setProductType).toHaveBeenCalledWith(PRODUCT_TYPE.POS);
  });
});

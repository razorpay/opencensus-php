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
  isOnboardingDisabled: true,
};
const defaultUserExtra = {
  findTag: jest.fn(),
  isPartnershipForCapitalEnabled: true,
};
const defaultOrgExtra = {};
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
  });
  test('should show the note for resuming partner onboarding', () => {
    renderApp({});

    expect(
      screen.getByText(
        'Note: New business onboarding temporarily paused! Your clients can submit their details for quick activation when we resume onboarding',
      ),
    ).toBeInTheDocument();
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
  test('hide Banking when the hidden features tag AddNewRazorpayXMerchant is present', () => {
    const findTag = jest.fn();
    findTag.mockImplementation((value) => {
      if (value === HIDDEN_INTERNATIONAL_FEATURES_TAGS.AddNewRazorpayXMerchant) return true;
      return false;
    });

    expect(screen.queryByText('RazorpayX')).not.toBeInTheDocument();
  });
});

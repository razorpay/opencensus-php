import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import Marketplace from 'merchant/views/Marketplace/Index';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

global.rzpQ = {
  productOnboarding: () => ({
    success: jest.fn(),
  }),
  marketplace: () => ({ interaction: jest.fn() }),
  component: jest.fn(),
};

jest.mock('merchant/reducers/onboarding', () => ({
  ...jest.requireActual('merchant/reducers/onboarding'),
  getCurrentProductOnBoardingDetails: jest.fn().mockReturnValue({
    showOnboarding: false,
    isQuickGuideOpen: false,
    isTour: false,
    isEnabled: false,
  }),
}));

describe('marketplace', () => {
  test('should render the alert correctly when fee_bearer is customer', async () => {
    const reduxStateWithCustomerFeeBearer = getInitialReduxState({
      merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
      isMarketplaceEnabled: true,
    });
    render(<Marketplace />, {
      initialState: reduxStateWithCustomerFeeBearer,
    });
    await waitFor(() => {
      expect(screen.getByText('Route is not available for you')).toBeInTheDocument();
    });
    expect(
      screen.getByText(
        'This product is not supported for merchants accepting payments as per the convenience fee model. Any payments accepted via QR will be auto refunded.',
      ),
    ).toBeInTheDocument();
  });

  test('should render the onboarding blocker correctly when fee_bearer is customer and marketplace feature is disabled', () => {
    const reduxState = getInitialReduxState({
      merchant: { fee_bearer: FEE_BEARER_TYPES.CUSTOMER },
      isMarketplaceEnabled: false,
    });
    render(<Marketplace />, {
      initialState: reduxState,
    });
    expect(screen.getByText('Route')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This product is not supported for merchants accepting payments as per the convenience fee model. If you wish to enable this product click',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
});

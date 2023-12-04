// removing this file from tests, because this flow have been removed
// ui imports
import CouponEngineTab from 'merchant/views/MagicCheckout/CouponEngine/index';

// test utils
import { render, screen, waitFor, userEvent } from 'test-utils';

global.rzpQ = {
  productOnboarding: () => ({
    success: jest.fn(),
  }),
  merchantActions: () => ({
    success: jest.fn(),
  }),
  component: jest.fn(),
};

jest.mock(
  'merchant/views/MagicCheckout/CouponEngine/components/EnableCouponBanner/EnableCouponBanner',
  () => () => {
    return (
      <div>
        <h1>Announcement Banner</h1>
      </div>
    );
  },
);

const variantOn = { variables: { result: 'on' } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      magic_shopify_coupon_sync: variantOn,
    },
  }),
  withSplitzService: jest.fn(),
}));

jest.mock(
  'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponsTab',
  () => () => {
    return (
      <div>
        <h1>Create and sync coupons on Magic checkout</h1>
      </div>
    );
  },
);

test('should render coupon engine tab', async () => {
  render(<CouponEngineTab />);

  await waitFor(() => {
    expect(screen.getByText('Coupons 360')).toBeInTheDocument();
  });

  await userEvent.click(screen.getByText('Continue'));

  await waitFor(() => {
    expect(screen.getByText('Announcement Banner')).toBeInTheDocument();
    expect(screen.getByText('Create and sync coupons on Magic checkout')).toBeInTheDocument();
  });
});

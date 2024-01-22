// ui imports
import CouponEngineTab from 'merchant/views/MagicCheckout/CouponEngine/index';

// test utils
import { render, screen, waitFor, userEvent } from 'test-utils';

// function imports
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

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

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
}));

listCoupons.mockReturnValue({
  data: {
    coupons: [],
  },
});

const variantOn = { variables: { result: 'on' } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      magic_shopify_coupon_sync: variantOn,
      magic_hide_cod_when_disabled: variantOn,
      magic_free_shipping_coupon: variantOn,
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

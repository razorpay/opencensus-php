import ActiveCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/ActiveCouponsTab';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { listCoupons, deactivateCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

import {
  ALL_COUPONS,
  ACTIVE_COUPONS,
} from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/DummyResponses';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { ModalProvider as CouponEngineContextProvider } from 'merchant/views/MagicCheckout/CouponEngine/context';

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
  deactivateCoupon: jest.fn(),
}));

listCoupons.mockReturnValue({
  data: ACTIVE_COUPONS,
});

deactivateCoupon.mockReturnValue({
  data: {
    status: 'success',
  },
});

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const ActiveCouponsTabWithProvider = () => {
  return (
    <CouponEngineContextProvider>
      <ActiveCouponsTab />
    </CouponEngineContextProvider>
  );
};

describe('active coupons tab', () => {
  test.each(ACTIVE_COUPONS.coupons)('should render coupon with code %s', async (coupon) => {
    render(<ActiveCouponsTabWithProvider />);

    await waitFor(() => {
      expect(screen.getByText(coupon.code)).toBeInTheDocument();
    });
  });

  test('should be able to select multiple coupons', async () => {
    render(<ActiveCouponsTabWithProvider />);

    await waitFor(() => {
      expect(screen.getByText(ACTIVE_COUPONS.coupons[0].code)).toBeInTheDocument();
    });

    const selectAllCouponCta = screen.getByTestId('select-all-coupons');

    await userEvent.click(selectAllCouponCta);

    ACTIVE_COUPONS.coupons.forEach((coupon) => {
      expect(screen.getByTestId(`coupon-${coupon.id}`)).toBeChecked();
    });

    expect(screen.queryByRole('button', { name: /Create Coupon/i })).not.toBeInTheDocument();

    expect(screen.getByTestId('coupon-bulk-actions')).toBeInTheDocument();

    const bulkActions = screen.getByTestId('coupon-bulk-actions');

    await userEvent.click(bulkActions);

    // click on activate all cta, then we click it then check how many coupons have status as active. Then expect that same number of api calls to be made
    const inactiveAllCta = screen.getByText(/^Deactivate all?/i);

    await userEvent.click(inactiveAllCta);

    const activeCoupons = ALL_COUPONS.coupons.filter((coupon) => coupon.status === 'active').length;

    expect(deactivateCoupon).toHaveBeenCalledTimes(activeCoupons);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});

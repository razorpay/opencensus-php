import AllCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/AllCouponsTab';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { listCoupons, publishCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';

import { ALL_COUPONS } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/DummyResponses';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { ModalProvider as CouponEngineContextProvider } from 'merchant/views/MagicCheckout/CouponEngine/context';

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
  publishCoupon: jest.fn(),
}));

listCoupons.mockReturnValue({
  data: ALL_COUPONS,
});

publishCoupon.mockReturnValue({
  data: {
    status: 'success',
  },
});

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const AllCouponsTabWithProvider = () => {
  return (
    <CouponEngineContextProvider>
      <AllCouponsTab />
    </CouponEngineContextProvider>
  );
};

describe('all coupons tab', () => {
  test.each(ALL_COUPONS.coupons)('should render coupon with code %s', async (coupon) => {
    render(<AllCouponsTabWithProvider />);

    await waitFor(() => {
      expect(screen.getByText(coupon.code)).toBeInTheDocument();
    });
  });

  test('should be able to select multiple coupons', async () => {
    render(<AllCouponsTabWithProvider />);

    await waitFor(() => {
      expect(screen.getByText(ALL_COUPONS.coupons[0].code)).toBeInTheDocument();
    });

    const selectAllCouponCta = screen.getByTestId('select-all-coupons');

    await userEvent.click(selectAllCouponCta);

    ALL_COUPONS.coupons.forEach((coupon) => {
      expect(screen.getByTestId(`coupon-${coupon.id}`)).toBeChecked();
    });

    expect(screen.queryByRole('button', { name: /Create Coupon/i })).not.toBeInTheDocument();

    expect(screen.getByTestId('coupon-bulk-actions')).toBeInTheDocument();

    const bulkActions = screen.getByTestId('coupon-bulk-actions');

    await userEvent.click(bulkActions);

    // click on publish all cta, then we click it then check how many coupons have status as created. Then expect that same number of api calls to be made
    const publishAllCta = screen.getByText(/^Publish all?/i);

    await userEvent.click(publishAllCta);

    const createdCoupons = ALL_COUPONS.coupons.filter(
      (coupon) => coupon.status === 'created',
    ).length;

    expect(publishCoupon).toHaveBeenCalledTimes(createdCoupons);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
    });
  });
});

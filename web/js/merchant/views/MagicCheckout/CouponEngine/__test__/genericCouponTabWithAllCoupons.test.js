import { render, screen, userEvent, waitFor } from 'test-utils';

// ui imports
import GenericCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab';

// api imports
import { listCoupons, publishCoupon } from 'merchant/views/MagicCheckout/CouponEngine/api';
import { ALL_COUPONS } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/DummyResponses';

// other helpers
import * as NotificationsActions from 'merchant_common/reducers/notifications';

// context helpers
import { ModalProvider as CouponEngineContextProvider } from 'merchant/views/MagicCheckout/CouponEngine/context';

// constants imports
import { INIITIAL_ALL_COUPON_FILTERS } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/contants';

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

const AllCouponsTab = () => {
  return (
    <CouponEngineContextProvider>
      <GenericCouponsTab initialFilters={INIITIAL_ALL_COUPON_FILTERS} />
    </CouponEngineContextProvider>
  );
};

describe('all coupons tab', () => {
  test.each(ALL_COUPONS.coupons)('should render coupon with code %s', async (coupon) => {
    render(<AllCouponsTab />);

    await waitFor(() => {
      expect(screen.getByText(coupon.code)).toBeInTheDocument();
    });
  });

  test('should be able to select multiple coupons', async () => {
    render(<AllCouponsTab />);

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

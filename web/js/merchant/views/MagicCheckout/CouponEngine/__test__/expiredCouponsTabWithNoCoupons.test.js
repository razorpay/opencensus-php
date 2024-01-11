import { render, screen, userEvent } from 'test-utils';

// ui elements
import GenericCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab';

// api functions
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

// context
import { ModalProvider as CouponEngineContextProvider } from 'merchant/views/MagicCheckout/CouponEngine/context';

// other helpers
import * as ModalActions from 'merchant_common/reducers/modals';

// constants
import { INIITIAL_EXPIRED_COUPON_FILTERS } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/contants';

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
  publishCoupon: jest.fn(),
}));

listCoupons.mockReturnValue({ data: { coupons: [] } });

const ExpiredCouponsTab = () => {
  return (
    <CouponEngineContextProvider>
      <GenericCouponsTab initialFilters={INIITIAL_EXPIRED_COUPON_FILTERS} />
    </CouponEngineContextProvider>
  );
};

describe('expired coupons tab with no coupons', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('should render expired coupons tab', async () => {
    render(<ExpiredCouponsTab />);

    await screen.findByText('You haven’t created any coupons');

    const createCouponCta = screen.getAllByRole('button', { name: /Create Coupon/i });

    await userEvent.click(createCouponCta[0]);

    await expect(openModalSpy).toHaveBeenCalled();
  });
});

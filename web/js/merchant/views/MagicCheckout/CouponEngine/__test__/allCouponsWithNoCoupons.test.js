import AllCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/AllCouponsTab';
import { render, screen, userEvent } from 'test-utils';
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

import * as ModalActions from 'merchant_common/reducers/modals';

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
}));

listCoupons.mockReturnValue({ data: { coupons: [] } });

describe('all coupons tab with no coupons', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('should render all coupons tab', async () => {
    render(<AllCouponsTab />);

    await screen.findByText('You haven’t created any coupons');

    const createCouponCta = screen.getAllByRole('button', { name: /Create Coupon/i });

    await userEvent.click(createCouponCta[0]);

    await expect(openModalSpy).toHaveBeenCalled();
  });
});

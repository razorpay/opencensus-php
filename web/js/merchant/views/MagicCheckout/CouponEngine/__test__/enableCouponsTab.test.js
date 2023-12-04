import EnableCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponsTab';
import { render, screen, waitFor, userEvent } from 'test-utils';

import * as ModalActions from 'merchant_common/reducers/modals';

describe('coupon engine setup tab', () => {
  test('should render enable coupons tab', () => {
    render(<EnableCouponsTab />);

    expect(screen.getByText('Create and sync coupons on Magic checkout')).toBeInTheDocument();
  });
});

describe('create coupon card', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  test('on click on the button create coupon, modal should open', async () => {
    render(<EnableCouponsTab />);

    expect(screen.getByText('Coupons on Magic')).toBeInTheDocument();

    expect(screen.getByText('Create Coupon')).toBeInTheDocument();

    const createCouponButton = screen.getByTestId('create-coupon-cta');

    await userEvent.click(createCouponButton);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});

describe('sync to shopify card', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  test('on click on the button sync now, modal should open', async () => {
    render(<EnableCouponsTab />);

    expect(screen.getByText('Coupons from Shopify')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('Sync now')).toBeInTheDocument();
    });

    const syncToShopifyButton = screen.getByTestId('sync-to-shopify-cta');

    await userEvent.click(syncToShopifyButton);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});

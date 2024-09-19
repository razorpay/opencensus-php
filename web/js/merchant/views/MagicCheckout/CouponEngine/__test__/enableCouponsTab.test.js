import React from 'react';
import { Provider } from 'react-redux';
import { render, screen, waitFor, userEvent, server } from 'test-utils';

import { storeWithInitialState } from 'merchant/store';
import EnableCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponsTab';
import GetStartedWithCoupons from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/GetStartedWithCoupons';
import CouponSettings from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/CouponSettings';

import * as ModalActions from 'merchant_common/reducers/modals';
import { magicCEConfigHandlers } from './mocks/handlers';

const initialState = {
  magic_settings: {
    platform: 'shopify',
    shop_id: 'abc-store',
    one_cc_coupon_engine: true,
    one_cc_auto_apply_coupons: true,
    one_cc_multi_coupons: false,
  },
};

const mockSuccessfulApiCall = () => {
  server.use(magicCEConfigHandlers.success);
};

const mockUnsuccessfulApiCall = () => {
  server.use(magicCEConfigHandlers.badRequest);
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initialState, ...state })}>
      <CouponSettings {...props} />
    </Provider>
  );
};

describe('EnableCouponsTab', () => {
  test('should render Settings and Get started sections', () => {
    render(<EnableCouponsTab />);

    expect(screen.getByText('Coupon Settings')).toBeInTheDocument();
    expect(screen.getByText('Get started with Coupons')).toBeInTheDocument();
  });
});

describe('CouponSettings', () => {
  test('should render three cards for toggling coupons settings', () => {
    render(<CouponSettings />);

    expect(screen.getByText(/enable coupons/i)).toBeInTheDocument();
    expect(screen.getByText(/auto-apply best coupon/i)).toBeInTheDocument();
    expect(screen.getByText(/let users combine coupons/i)).toBeInTheDocument();
  });

  test('should render cards reflecting the magic settings correctly', () => {
    render(<App />);

    expect(screen.getByRole('switch', { name: 'Toggle Coupons' })).toBeChecked();
    expect(screen.getByRole('switch', { name: 'Toggle Auto-apply Coupons' })).toBeChecked();
    expect(screen.getByRole('switch', { name: 'Toggle Multi Coupons' })).not.toBeChecked();
  });

  test('should disable all coupon settings when coupon engine if disabled', async () => {
    mockSuccessfulApiCall();
    render(<App />);

    const enableCouponsSwitch = screen.getByRole('switch', { name: 'Toggle Coupons' });
    await userEvent.click(enableCouponsSwitch);

    await waitFor(() => {
      expect(screen.getByRole('switch', { name: 'Toggle Coupons' })).not.toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Auto-apply Coupons' })).not.toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Multi Coupons' })).not.toBeChecked();
    });
  });

  test('should keep settings unchanged when API call fails', async () => {
    mockUnsuccessfulApiCall();
    render(<App />);

    const enableCouponsSwitch = screen.getByRole('switch', { name: 'Toggle Coupons' });
    await userEvent.click(enableCouponsSwitch);

    await waitFor(() => {
      expect(screen.getByRole('switch', { name: 'Toggle Coupons' })).toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Auto-apply Coupons' })).toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Multi Coupons' })).not.toBeChecked();
    });
  });

  test('should toggle only the specific coupon setting in case of auto-apply or multi coupon', async () => {
    mockSuccessfulApiCall();
    render(<App />);

    const autoApplyCouponsSwitch = screen.getByRole('switch', {
      name: 'Toggle Auto-apply Coupons',
    });
    await userEvent.click(autoApplyCouponsSwitch);

    await waitFor(() => {
      expect(screen.getByRole('switch', { name: 'Toggle Coupons' })).toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Auto-apply Coupons' })).not.toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Multi Coupons' })).not.toBeChecked();
    });

    const multiCouponsSwitch = screen.getByRole('switch', {
      name: 'Toggle Multi Coupons',
    });
    await userEvent.click(multiCouponsSwitch);

    await waitFor(() => {
      expect(screen.getByRole('switch', { name: 'Toggle Coupons' })).toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Auto-apply Coupons' })).not.toBeChecked();
      expect(screen.getByRole('switch', { name: 'Toggle Multi Coupons' })).toBeChecked();
    });
  });

  test('should render auto-apply and multi coupon disabled when coupon engine is turned off', async () => {
    render(
      <App
        state={{
          magic_settings: {
            ...initialState.magic_settings,
            one_cc_coupon_engine: false,
          },
        }}
      />,
    );

    const autoApplyCouponsSwitch = screen.getByRole('switch', {
      name: 'Toggle Auto-apply Coupons',
    });
    const multiCouponsSwitch = screen.getByRole('switch', {
      name: 'Toggle Multi Coupons',
    });
    expect(autoApplyCouponsSwitch).toBeDisabled();
    expect(multiCouponsSwitch).toBeDisabled();
  });
});

describe('GetStartedWithCoupons', () => {
  test('should open modal when create coupon button is clicked', async () => {
    const openModalSpy = jest.spyOn(ModalActions, 'openModal');
    render(<GetStartedWithCoupons />);

    expect(screen.getByText('Coupons on Magic')).toBeInTheDocument();
    expect(screen.getByText('Create Coupon')).toBeInTheDocument();

    const createCouponButton = screen.getByTestId('create-coupon-cta');
    await userEvent.click(createCouponButton);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('should open modal when Shopify sync now button is clicked', async () => {
    const openModalSpy = jest.spyOn(ModalActions, 'openModal');

    render(<GetStartedWithCoupons />);

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

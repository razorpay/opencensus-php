import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import OrderSummary from 'merchant/views/POS/OrderSummary';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import {
  getLatestOrderHandler,
  getProductPricingHandler,
} from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosStoreInitialState } from 'merchant/views/POS/constants';
import * as posHelpers from 'merchant/views/POS/helpers';
import * as posCustomHooks from 'merchant/views/POS/hooks';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ProductPlans } from 'merchant/views/POS/types';
import { render, screen, server, userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';

const MOCK_CART_ITEMS = [
  {
    code: 'mock-product',
    quantity: 3,
    plan: 'monthly' as ProductPlans,
  },
];

const queryClient = new QueryClient();

describe('<OrderSummary/>', () => {
  const renderApp = (cartItems = MOCK_CART_ITEMS, user = MOCK_USER) => {
    const initialState = {
      ...PosStoreInitialState,
      cartItems,
    };

    render(
      <QueryClientProvider client={queryClient}>
        <PosDeviceStoreProvider init={initialState} user={user}>
          <OrderSummary />
        </PosDeviceStoreProvider>
      </QueryClientProvider>,
    );
  };

  afterEach(() => {
    jest.resetAllMocks();
    queryClient.clear();
  });
  test('should render app on screen ', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Order Summary')).toBeVisible();
    expect(screen.getByText('Delivery Address')).toBeVisible();
    expect(screen.getByText('Payment Details')).toBeVisible();
  });

  test('should show P0 error if cart items is more than max number of items', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));

    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 11,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('This order can accommodate a maximum of 9 items')).toBeVisible();
    expect(screen.getByText('Reduce the total number of items for this order')).toBeVisible();
  });

  test('should show P0 error if KYC has been rejected', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));

    const newUser = {
      ...MOCK_USER,
      pos_activation_status: 'rejected',
    };
    renderApp(undefined, newUser);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Unfortunately! We can’t proceed with your order')).toBeVisible();
  });

  test('should render checkout CTA for desktop if no previous order is pending', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler());

    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByTestId('pos-checkout-cta')).toBeEnabled();
    });
  });

  test('should show errors for repeated orders and checkout cta should be disabled', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));

    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByText('Unfortunately! We can’t proceed with your order')).toBeVisible();
    });
    expect(screen.getByTestId('pos-checkout-cta')).toBeDisabled();
  });

  test('should show error and disable checkout if latest order fetch fails', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('', false));
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByText('Failed to create order')).toBeVisible();
    });
    expect(screen.getByTestId('pos-checkout-cta')).toBeDisabled();
  });

  test('should P1 errors on screen if exists', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));

    const validatorSpy = jest.spyOn(posHelpers, 'validatePrecheckout');
    validatorSpy.mockReturnValue({
      sev: 1,
      isHideCheckout: false,
      type: 'ORDER_NOT_DELIVERABLE',
      title: 'We are coming to your city soon!',
      description: 'Order Not Deliverable',
    });
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('We are coming to your city soon!')).toBeVisible();
    await waitFor(() => {
      expect(screen.getByTestId('pos-checkout-cta')).toBeEnabled();
    });
  });

  test('should render checkout cta for mobile with total amount and view details should open pricing sheet', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('latest_order_with_delivered'));
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByText('Confirm Address & Pay')).toBeVisible();
    });

    expect(screen.getByText('Total Order Price')).toBeVisible();
    expect(screen.getByText(/236/)).toBeVisible();
    await userEvent.click(screen.getByText('View Details'));
    await waitFor(() => {
      expect(screen.getByText('Payment Details')).toBeVisible();
    });
  });

  test('should render total order price to be 0 and view details as disabled if P0 error exists ', async () => {
    server.use(getProductPricingHandler(), getLatestOrderHandler('', false));

    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });

    const user = {
      ...MOCK_USER,
      submitted: true,
      business_website: 'www.mock-website.com',
    };
    const newCartItems = [
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp(newCartItems, user);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByText('Failed to create order')).toBeVisible();
    });

    expect(screen.getByTestId('total-amount-mobile')).toHaveTextContent('0');
    expect(screen.getByTestId('view-details-btn')).toBeDisabled();
    expect(screen.getByTestId('pos-checkout-cta')).toBeDisabled();
  });
});

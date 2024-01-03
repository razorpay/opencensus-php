import React from 'react';

import OrderItems from 'merchant/views/POS/OrderSummary/OrderItems';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posCustomHooks from 'merchant/views/POS/hooks';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { PosDeviceStoreState, ProductPlans } from 'merchant/views/POS/types';
import { render, screen, userEvent, waitFor, waitForElementToBeRemoved, server } from 'test-utils';

const MOCK_CART_ITEMS = [
  {
    code: 'mock-product',
    quantity: 1,
    plan: 'monthly' as ProductPlans,
  },
];

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
}));

const renderApp = ({ cartItems }) => {
  const initialState = {
    ...(jest.requireActual('merchant/views/POS/context').initialState as Record<string, string>),
    cartItems: cartItems ?? [],
  };

  render(
    <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={MOCK_USER}>
      <OrderItems />
    </PosDeviceStoreProvider>,
  );
};

describe('<OrderItems/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  test('should render all orderable items present in the cart on screen', async () => {
    renderApp({ cartItems: MOCK_CART_ITEMS });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Order Summary'));
    expect(screen.getByText('1 Item')).toBeVisible();
    expect(screen.getByText('Mock Product')).toBeVisible();
    expect(screen.getByText('Monthly Plan')).toBeVisible();
  });

  test('should render empty state if cart is empty with CTA', async () => {
    renderApp({ cartItems: [] });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Order Summary'));
    expect(screen.getByText('Looks like you haven’t made your choices yet..')).toBeVisible();
    await userEvent.click(screen.getByText('Show More'));
    expect(mockedUsedNavigate).toHaveBeenCalledWith('/pos/catalog');
  });

  test('should toggle to cart if clicked on edit', async () => {
    const newCartItem = {
      name: 'mock-product-new',
      quantity: 3,
      plan: 'lifetime' as ProductPlans,
    };
    renderApp({ cartItems: [...MOCK_CART_ITEMS, newCartItem] });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('2 Items')).toBeVisible();
    await userEvent.click(screen.getByText('Order Summary'));
    await userEvent.click(screen.getByText('Edit'));
    expect(screen.getByTestId('mock-product-monthly-cart-item')).toBeVisible();
  });

  test('should open cart in bottom sheet if clicked on edit and order items still present in the collapsible', async () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp({ cartItems: MOCK_CART_ITEMS });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Order Summary'));
    await userEvent.click(screen.getByText('Edit'));
    await waitFor(() => {
      expect(screen.getByText('Edit Order')).toBeVisible();
    });
  });

  test('should render empty state in the bottom sheet', async () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp({ cartItems: MOCK_CART_ITEMS });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Order Summary'));
    await userEvent.click(screen.getByText('Edit'));
    await waitFor(() => {
      expect(screen.getByText('Edit Order')).toBeVisible();
    });
    await userEvent.click(screen.getByLabelText('product delete icon'), { pointerEventsCheck: 0 });
    expect(screen.getByText('Looks like you haven’t made your choices yet..')).toBeVisible();
  });
});

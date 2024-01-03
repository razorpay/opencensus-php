import React from 'react';

import PdpActions from 'merchant/views/POS/ProductDescription/PdpActions';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosStoreInitialState } from 'merchant/views/POS/constants';
import * as posHooks from 'merchant/views/POS/hooks';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ProductPlans } from 'merchant/views/POS/types';
import { render, screen, userEvent, waitForElementToBeRemoved, within, server } from 'test-utils';

const mockNavigate = jest.fn();

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockNavigate,
}));

const renderApp = ({ initialState = PosStoreInitialState, productCode = 'mock-product' }) => {
  render(
    <PosDeviceStoreProvider init={initialState} user={MOCK_USER}>
      <PdpActions productCode={productCode} plan="monthly" containerRef={null} />
    </PosDeviceStoreProvider>,
  );
};

describe('<PdpActions/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  test('render Add to cart btn and Proceed to cart on screen as disabled', async () => {
    renderApp({});
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Add to cart')).toBeVisible();
    expect(screen.getByText('Proceed to Order')).toBeVisible();
    expect(screen.getByTestId('proceed-to-checkout')).toBeDisabled();
  });

  test('render Add to cart btn on screen and changes to quantity widget on clicking and proceed to cart gets enabled', async () => {
    const cartItems = [
      {
        code: 'mock-product',
        quantity: 3,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp({ initialState: { ...PosStoreInitialState, cartItems } });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByLabelText('reduce cart quantity')).toBeVisible();
    expect(screen.getByLabelText('increase cart quantity')).toBeVisible();
    expect(screen.getByTestId('proceed-to-checkout')).toBeEnabled();
  });

  test('clicking on Proceed to cart triggers navigate with correct route', async () => {
    const cartItems = [
      {
        code: 'mock-product',
        quantity: 3,
        plan: 'monthly' as ProductPlans,
      },
    ];
    renderApp({ initialState: { ...PosStoreInitialState, cartItems } });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('proceed-to-checkout'));
    expect(mockNavigate).toBeCalledWith('/pos/catalog/mock-product/order-summary');
  });

  test('should not render component if product description not available', async () => {
    renderApp({ initialState: { ...PosStoreInitialState }, productCode: 'dummy-product' });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.queryByText('Add to cart')).toBeNull();
    expect(screen.queryByText('Proceed to Order')).toBeNull();
  });

  test('should render floating widget if isActionsVisible is true', async () => {
    const useIsVisibleSpy = jest.spyOn(posHooks, 'useIsVisible');
    const useBladeBreakpointsSpy = jest.spyOn(posHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      isDesktop: true,
      isMobile: false,
      isLargeScreen: true,
      matchedBreakpoint: 'xl',
    });
    useIsVisibleSpy.mockReturnValue(false);
    renderApp({});
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('pdp-floating-actions')).toBeVisible();
  });

  test('should render floating widget with correct content if isActionsVisible is true', async () => {
    const useIsVisibleSpy = jest.spyOn(posHooks, 'useIsVisible');
    const useBladeBreakpointsSpy = jest.spyOn(posHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      isDesktop: true,
      isMobile: false,
      isLargeScreen: true,
      matchedBreakpoint: 'xl',
    });
    useIsVisibleSpy.mockReturnValue(false);
    renderApp({});
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    const floatinWidget = screen.getByTestId('pdp-floating-actions');
    expect(within(floatinWidget).getByText(/300/)).toBeVisible();
    expect(within(floatinWidget).getByText(/Monthly Subscription/)).toBeVisible();
    expect(within(floatinWidget).getByText(/Setup Fee/)).toBeVisible();
    expect(within(floatinWidget).getByText('Add to cart')).toBeVisible();
  });
});

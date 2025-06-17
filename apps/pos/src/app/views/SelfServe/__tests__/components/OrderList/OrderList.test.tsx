import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import OrderList from 'apps/pos/src/app/views/SelfServe/OrderList';
import {
  MOCK_ORDER_LIST,
  MOCK_USER,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import {
  getOrdersListHandler,
  getSubmerchantOrdersListHandler,
  getProductPricingHandler,
  getSubmerchantProductPricingHandler,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import * as posHooks from 'apps/pos/src/app/views/SelfServe/hooks';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import * as posServices from 'apps/pos/src/app/views/SelfServe/services';
import { render, screen, server, userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';

const mockedUseNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUseNavigate,
}));

const observerMap = new Map();

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn((cb) => ({
  observe: jest.fn((element: Element) => {
    observerMap.set(element, cb);
  }),
  unobserve: jest.fn(),
}));

const intersect = (element: Element, isIntersecting: boolean) => {
  const cb = observerMap.get(element);
  if (cb) cb([{ isIntersecting }]);
};

const queryClient = new QueryClient();

const renderApp = ({ isRenderedFromPartnerRoute = false, ...props } = {}) => {
  render(
    <QueryClientProvider client={queryClient}>
      <PosDeviceStoreProvider
        user={MOCK_USER}
        isRenderedFromPartnerRoute={isRenderedFromPartnerRoute}
      >
        <OrderList pageSize={3} {...props} />
      </PosDeviceStoreProvider>
    </QueryClientProvider>,
  );
};

describe('<OrderList/>', () => {
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });

  test('should trigger fetch call with correct paginated params', async () => {
    const fetchOrdersSpy = jest.spyOn(posServices, 'getOrderList');
    server.use(getProductPricingHandler(), getOrdersListHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
    expect(fetchOrdersSpy).toHaveBeenCalledWith({
      skip: '0',
      count: '3',
      status: ['paid', 'delivered', 'rejected'],
    });
    const nextPageLoader = screen.getByTestId('next-page-trigger-el');
    intersect(nextPageLoader, true);
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(6);
    });
    expect(fetchOrdersSpy).toHaveBeenCalledWith({
      skip: '1',
      count: '3',
      status: ['paid', 'delivered', 'rejected'],
    });
  });

  test('should render order listing component on screen with order list items', async () => {
    server.use(getProductPricingHandler(), getOrdersListHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
  });

  test('should render order listing component on screen with empty screen if no orders', async () => {
    server.use(getProductPricingHandler(), getOrdersListHandler(true));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getByText(/No Order History/)).toBeVisible();
    });
    expect(screen.getByAltText('empty order image')).toBeVisible();
    expect(
      screen.getByText(
        `It looks like you have not placed any orders yet. Explore our store and place your first order to get started.`,
      ),
    ).toBeVisible();

    await userEvent.click(screen.getByText('Shop now'));
    expect(mockedUseNavigate).toHaveBeenCalledWith('/pos/catalog');
  });
});

describe('OrderList with isRenderedFromPartnerRoute = true', () => {
  beforeEach(() => {
    server.use(
      getProductPricingHandler(),
      getOrdersListHandler(),
      getSubmerchantOrdersListHandler(),
      getSubmerchantProductPricingHandler(),
    );
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });
  test('should disable CTAs in order list in Desktop View', async () => {
    renderApp({ isRenderedFromPartnerRoute: true });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
    expect(screen.getAllByRole('button', { name: 'View Order Details' })[0]).toBeDisabled();
  });
  test('should disable CTAs in order list in Mobile View', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp({ isRenderedFromPartnerRoute: true });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
    // In Mobile device the Button is hidden and the tile itself becomes the CTA
    expect(screen.queryByRole('button', { name: 'View Order Details' })).not.toBeInTheDocument();

    // Click first tile
    await userEvent.click(screen.getByTestId(`order-list-item-${MOCK_ORDER_LIST[0].id}`));
    expect(mockedUseNavigate).not.toHaveBeenCalled();
  });

  test('should render order list with enabled CTA in Mobile View if isRenderedFromPartnerRoute = false', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp({ isRenderedFromPartnerRoute: false });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
    // Click first tile
    await userEvent.click(screen.getByTestId(`order-list-item-${MOCK_ORDER_LIST[0].id}`));
    expect(mockedUseNavigate).toHaveBeenCalledWith(`/pos/orders/${MOCK_ORDER_LIST[0].id}`);
  });
});

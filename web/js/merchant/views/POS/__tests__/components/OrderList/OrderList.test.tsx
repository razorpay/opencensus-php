import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import OrderList from 'merchant/views/POS/OrderList';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import {
  getOrdersListHandler,
  getProductPricingHandler,
} from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import * as posServices from 'merchant/views/POS/services';
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

const renderApp = () => {
  render(
    <QueryClientProvider client={queryClient}>
      <PosDeviceStoreProvider user={MOCK_USER}>
        <OrderList pageSize={3} />
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
    });
    const nextPageLoader = screen.getByTestId('next-page-trigger-el');
    intersect(nextPageLoader, true);
    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(6);
    });
    expect(fetchOrdersSpy).toHaveBeenCalledWith({
      skip: '1',
      count: '3',
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

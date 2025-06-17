import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import OrderDetails from 'apps/pos/src/app/views/SelfServe/OrderDetails/OrderDetails';
import { render, screen, server, waitFor, waitForElementToBeRemoved, within } from 'test-utils';
import {
  getOrderDetails,
  getProductPricingHandler,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { MOCK_USER } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';

const mockedUsedNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
  useParams: () => ({
    orderId: 'KwxwAgItmmXdp',
  }),
}));

const queryClient = new QueryClient();

const renderApp = (isOrderConfirmation = false) => {
  render(
    <QueryClientProvider client={queryClient}>
      <PosDeviceStoreProvider user={MOCK_USER}>
        <OrderDetails isOrderConfirmation={isOrderConfirmation} />
      </PosDeviceStoreProvider>
    </QueryClientProvider>,
  );
};

describe('Order Details Component', () => {
  afterEach(() => {
    jest.resetAllMocks();
    queryClient.clear();
  });

  test('show spinner if productDescriptions is not present', async () => {
    server.use(getProductPricingHandler(), getOrderDetails({ type: 'paid' }));

    jest.mock('apps/pos/src/app/views/SelfServe/constants', () => {
      const actual = jest.requireActual('apps/pos/src/app/views/SelfServe/constants') as Record<
        string,
        string
      >;
      return {
        ...actual,
        PRODUCT_DESCRIPTIONS: {},
      };
    });
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByLabelText('order-details-spinner')).toBeInTheDocument();
  });

  test('should render Order Details with products', async () => {
    server.use(getProductPricingHandler(), getOrderDetails({ type: 'paid' }));

    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    await waitFor(() => {
      expect(screen.getByText('Products in this purchase')).toBeInTheDocument();
    });

    expect(screen.getByText(/Ordered on October 04, 2023/)).toBeVisible();
    expect(screen.getByText('Mock Product')).toBeVisible();
    expect(screen.getByText('Monthly Plan')).toBeVisible();
  });

  test('should show delivery address on screen', async () => {
    server.use(getProductPricingHandler(), getOrderDetails({ type: 'paid' }));

    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    await waitFor(() => {
      expect(screen.getByText('Test Name')).toBeVisible();
    });

    expect(screen.getByText('8486467098')).toBeVisible();
    expect(screen.getByText(/test operation address/)).toBeVisible();
    expect(screen.getByText(/test operation state/)).toBeVisible();
    expect(screen.getByText(/test operation city/)).toBeVisible();
    expect(screen.getByText(/781019/)).toBeVisible();
  });

  test('should render order status timeline on screen', async () => {
    server.use(getProductPricingHandler(), getOrderDetails({ type: 'paid' }));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    await waitFor(() => {
      expect(screen.getByText('Arriving by')).toBeVisible();
    });

    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Confirmed')).toBeVisible();
    expect(screen.getByText('Delivered')).toBeVisible();
    expect(screen.getAllByText('October 04, 2023').length).toBe(2);
  });

  test('should render merchant email and phone number on screen', async () => {
    server.use(getProductPricingHandler(), getOrderDetails({ type: 'paid' }));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    await waitFor(() => {
      expect(screen.getByText('Arriving by')).toBeVisible();
    });

    const merchantContactContainer = screen.getByTestId('merchant-contact-container');
    expect(within(merchantContactContainer).getByText('1234567890')).toBeVisible();
    expect(within(merchantContactContainer).getByText('testemail@gmail.com')).toBeVisible();
  });
});

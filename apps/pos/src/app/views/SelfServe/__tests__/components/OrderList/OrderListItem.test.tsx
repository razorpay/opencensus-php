import React from 'react';

import OrderListItem from 'apps/pos/src/app/views/SelfServe/OrderList/OrderListItem';
import {
  MOCK_DELIVERED_ORDER_ITEM,
  MOCK_PAID_ORDER_ITEM,
  MOCK_REJECTED_ORDER_ITEM,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import * as posCustomHooks from 'apps/pos/src/app/views/SelfServe/hooks';
import { render, screen, userEvent } from 'test-utils';

jest.mock('apps/pos/src/app/views/SelfServe/OrderSummary/OrderItems/OrderItem', () => ({
  __esModule: true,
  default: ({ orderItem }) => <div>{orderItem.code}</div>,
}));

const mockedUseNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUseNavigate,
}));

describe('<OrderListItem/>', () => {
  test('should render paid order item with correct content on screen', async () => {
    render(<OrderListItem shouldDisableCTAs={false} orderListItem={MOCK_PAID_ORDER_ITEM} />);
    expect(screen.getByText('October 04, 2023')).toBeVisible();
    expect(screen.getByText('Order Placed')).toBeVisible();
    expect(screen.getByText('2,360')).toBeVisible();
    expect(screen.getByText(/Order ID: mock-order-id/)).toBeVisible();
    expect(screen.getByText('ORDER RECEIVED')).toBeVisible();
    expect(screen.getByText(/Arriving by October 05, 2023/)).toBeVisible();

    await userEvent.click(screen.getByText('View Order Details'));
    expect(mockedUseNavigate).toHaveBeenCalledWith('/pos/orders/mock-order-id');
  });

  test('should render delivered order item with correct content on screen', () => {
    render(<OrderListItem shouldDisableCTAs={false} orderListItem={MOCK_DELIVERED_ORDER_ITEM} />);
    expect(screen.getByText('October 04, 2023')).toBeVisible();
    expect(screen.getByText('Order Placed')).toBeVisible();
    expect(screen.getByText('2,360')).toBeVisible();
    expect(screen.getByText(/Order ID: mock-order-id/)).toBeVisible();
    expect(screen.getByText('DELIVERED')).toBeVisible();
    expect(screen.getByText(/Arrived on October 05, 2023/)).toBeVisible();
  });

  test('should render rejected order item with correct content on screen', () => {
    render(<OrderListItem shouldDisableCTAs={false} orderListItem={MOCK_REJECTED_ORDER_ITEM} />);
    expect(screen.getByText('October 04, 2023')).toBeVisible();
    expect(screen.getByText('Order Placed')).toBeVisible();
    expect(screen.getByText('2,360')).toBeVisible();
    expect(screen.getByText(/Order ID: mock-order-id/)).toBeVisible();
    expect(screen.getByText('ORDER REJECTED')).toBeVisible();
    expect(screen.getByText(/Order rejected on October 09, 2023/)).toBeVisible();
    expect(screen.getByText('Failed because of Something')).toBeVisible();
  });

  test('should redirect to order details if order list item clicked in case of mobile', async () => {
    const useBladeBreakpointsSpy = jest.spyOn(posCustomHooks, 'useBladeBreakpoints');
    useBladeBreakpointsSpy.mockReturnValue({
      matchedBreakpoint: 'sm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    render(<OrderListItem shouldDisableCTAs={false} orderListItem={MOCK_REJECTED_ORDER_ITEM} />);
    await userEvent.click(screen.getByText('Order Placed'));
    expect(mockedUseNavigate).toHaveBeenCalledWith('/pos/orders/mock-order-id-third');
  });
});

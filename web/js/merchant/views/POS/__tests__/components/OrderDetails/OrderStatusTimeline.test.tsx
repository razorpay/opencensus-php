import React from 'react';

import OrderStatusTimeline from 'merchant/views/POS/OrderDetails/OrderStatusTimeline';
import {
  MOCK_PAID_ORDER_ITEM,
  MOCK_DELIVERED_ORDER_ITEM,
  MOCK_REJECTED_ORDER_ITEM,
  MOCK_REJECTED_WITH_REFUND_INITIATED,
  MOCK_REJECTED_WITH_REFUND_COMPLETED,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { render, screen } from 'test-utils';

const renderApp = (orderDetails = MOCK_PAID_ORDER_ITEM) => {
  render(<OrderStatusTimeline orderDetails={orderDetails} />);
};

describe('Order Details Component', () => {
  test('should show order status timeline with order received, confirmed and delivered items if order is paid', () => {
    renderApp();
    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Confirmed')).toBeVisible();
    expect(screen.getByText('Delivered')).toBeVisible();
    expect(screen.getAllByText('October 04, 2023').length).toBe(2);
  });

  test('should show order status timeline with order received, confirmed and delivered items if order is delivered', () => {
    renderApp(MOCK_DELIVERED_ORDER_ITEM);
    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Confirmed')).toBeVisible();
    expect(screen.getByText('Delivered')).toBeVisible();
    expect(screen.getAllByText('October 04, 2023').length).toBe(2);
    expect(screen.getByText('October 05, 2023')).toBeVisible();
  });

  test('should show order status timeline with order received, rejected and refund initiated items if order is rejected and refund initiated', () => {
    renderApp(MOCK_REJECTED_WITH_REFUND_INITIATED);
    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Rejected')).toBeVisible();
    expect(screen.getByText('Failed because of Something')).toBeVisible();
    expect(screen.getByText('Refund Initiated')).toBeVisible();
    expect(screen.getByText('October 13, 2023')).toBeVisible();
  });

  test('should show order status timeline with order received, rejected and refund completed items if order is rejected and refund completed', () => {
    renderApp(MOCK_REJECTED_WITH_REFUND_COMPLETED);
    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Rejected')).toBeVisible();
    expect(screen.getByText('Failed because of Something')).toBeVisible();
    expect(screen.getByText('Amount Refunded')).toBeVisible();
    expect(screen.getByText('October 13, 2023')).toBeVisible();
  });

  test('should show order status timeline with order received, rejected and refund pending items if order is rejected', () => {
    renderApp(MOCK_REJECTED_ORDER_ITEM);
    expect(screen.getByText('Order Received')).toBeVisible();
    expect(screen.getByText('Order Rejected')).toBeVisible();
    expect(screen.getByText('Failed because of Something')).toBeVisible();
    expect(screen.getByText('Refund Pending')).toBeVisible();
  });
});

import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render, userEvent } from 'test-utils';

import Orders from '..';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderOrders = () => {
  render(
    <GCMSTestPageRenderer>
      <Orders />
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Orders', () => {
  it('should render orders list page', async () => {
    renderOrders();

    await waitFor(() => {
      expect(screen.getByText('Orders')).toBeInTheDocument();
      // expect(screen.getAllByText('January 31, 2024').length).toBe(
      //   ordersListResponse.data.items.length,
      // );
    });
  });

  it('should show empty screen when no orders are present for a reseller name', async () => {
    renderOrders();

    await userEvent.type(screen.getByTestId('reseller_name'), 'abc');
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no orders yet!!')).toBeInTheDocument();
    });
  });

  it('should show empty screen when no orders are present for a Order Id', async () => {
    renderOrders();

    await userEvent.type(screen.getByTestId('order_id'), 'abc');
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no orders yet!!')).toBeInTheDocument();
    });
  });

  it('should show empty screen when no orders are present for a status', async () => {
    renderOrders();
    await userEvent.selectOptions(screen.getByTestId('status'), 'cancelled');
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no orders yet!!')).toBeInTheDocument();
    });
  });

  it('should clear all filters and show orders for default filters', async () => {
    renderOrders();

    await userEvent.type(screen.getByTestId('reseller_name'), 'abc');
    await userEvent.selectOptions(screen.getByTestId('status'), 'cancelled');
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no orders yet!!')).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('Clear'));

    await waitFor(() => {
      expect(screen.getByText('Orders')).toBeInTheDocument();
      // expect(screen.getAllByText('January 31, 2024').length).toBe(
      //   ordersListResponse.data.items.length,
      // );
    });
  });
});

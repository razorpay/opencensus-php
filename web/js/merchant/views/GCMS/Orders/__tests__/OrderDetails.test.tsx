import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor, render } from '@testing-library/react';

import OrderDetails from 'merchant/views/GCMS/Orders/OrderDetails';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';

jest.mock('react-router-dom', () => ({
  useNavigate: () => jest.fn(),
  useLocation: () => ({
    pathname: `/gcms/orders/${jest.requireActual('./mocks/fixtures').orderId}`,
    search: '',
    hash: '',
    state: {},
    key: '',
  }),
  useParams: () => ({ orderId: jest.requireActual('./mocks/fixtures').orderId }),
}));

const renderOrderDetails = () =>
  render(
    <GCMSTestPageRenderer>
      <OrderDetails />
    </GCMSTestPageRenderer>,
  );

describe('GCMS: Order Details', () => {
  it('should render orders details page', async () => {
    renderOrderDetails();

    await waitFor(() => {
      expect(screen.getByText(/NMmhaRfFheRmhA/i)).toBeInTheDocument(); //Order Id
      expect(screen.getByText(/N91osUDdN9WdO9/i)).toBeInTheDocument(); //Reseller Id
      expect(screen.getByText('Ibaco')).toBeInTheDocument(); //Reseller name
      expect(screen.getByText('Thank You Gift Card')).toBeInTheDocument(); //Program name
      expect(screen.queryAllByText('NMmsZbvXKsxmBh')[0]).toBeInTheDocument(); //SKU Id
    });

    // Delivery Section
    await waitFor(() => {
      expect(screen.queryAllByText(/Delivery/i)[0]).toBeInTheDocument();
      expect(screen.getByText(/Cards delivered to test@gmail.com/i)).toBeInTheDocument(); //Order Id
    });

    // Delivery Breakup Section
    await waitFor(() => {
      expect(screen.getByText('Delivery Breakup')).toBeInTheDocument();
      expect(screen.getByText('Uploaded')).toBeInTheDocument();
      expect(screen.getByText('5')).toBeInTheDocument();
      expect(screen.getByText('Delivered')).toBeInTheDocument();
      expect(screen.getByText('4')).toBeInTheDocument();
      expect(screen.getByText('Failed')).toBeInTheDocument();
      expect(screen.getByText('6')).toBeInTheDocument();
      expect(
        screen.queryByRole('button', { name: /download gift cards/i }),
      ).not.toBeInTheDocument();
    });
  });

  it('should show download button if all the gift cards are processed', async () => {
    jest.spyOn(require('react-router-dom'), 'useLocation').mockReturnValue({
      pathname: `/gcms/orders/${jest.requireActual('./mocks/fixtures').processedOrderId}`,
      search: '',
      hash: '',
      state: {},
      key: '',
    });

    jest.spyOn(require('react-router-dom'), 'useParams').mockReturnValue({
      orderId: jest.requireActual('./mocks/fixtures').processedOrderId,
    });

    renderOrderDetails();

    await waitFor(() => {
      const downloadButton = screen.getByRole('button', { name: /download gift cards/i });

      expect(downloadButton).toBeInTheDocument();
    });
  });
});

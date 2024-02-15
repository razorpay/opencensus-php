import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor, render } from '@testing-library/react';

import OrderDetails from 'merchant/views/GCMS/Orders/OrderDetails';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';

jest.mock('react-router-dom', () => ({
  useNavigate: () => jest.fn(),
  useLocation: () => ({
    pathname: '/gcms/orders/NMmhaRfFheRmhA',
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
  });
});

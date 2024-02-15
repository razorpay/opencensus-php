import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render, userEvent } from 'test-utils';

import ResellerOrders from 'merchant/views/GCMS/Resellers/ResellerOrders';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderOrders = () => {
  render(
    <GCMSTestPageRenderer>
      <ResellerOrders />
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Reseller Orders', () => {
  it('should render resellers orders list page', async () => {
    renderOrders();

    await waitFor(() => {
      expect(screen.getByText('Duration')).toBeInTheDocument();
      expect(screen.getByText('Order ID')).toBeInTheDocument();
    });
  });

  it('should show empty screen when no reseller orders are present for a status', async () => {
    renderOrders();
    await userEvent.selectOptions(screen.getByTestId('status'), 'cancelled');
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no orders yet!!')).toBeInTheDocument();
    });
  });
});

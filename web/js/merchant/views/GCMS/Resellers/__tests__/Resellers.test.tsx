import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render, userEvent } from 'test-utils';

import { resellersListResponse } from './mocks/fixtures';
import Resellers from '..';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderResellers = () => {
  render(
    <GCMSTestPageRenderer>
      <Resellers />
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Resellers', () => {
  it('should render resellers list page', async () => {
    renderResellers();

    await waitFor(() => {
      expect(screen.getByText('Reseller')).toBeInTheDocument();
      expect(screen.getAllByText('Ibaco').length).toBe(resellersListResponse.data.items.length);
    });
  });

  it('should show empty screen when no resellers are present for a reseller name', async () => {
    renderResellers();

    const search = screen.getByRole('textbox', {
      name: 'Reseller Name',
    });
    await userEvent.type(search, 'abc');
    userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no resellers yet!!')).toBeInTheDocument();
    });
  });

  it('should show empty screen when no resellers are present for a status', async () => {
    renderResellers();
    await userEvent.click(screen.getByTestId('status'));
    await userEvent.click(screen.getByText('Approval Pending'));
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no resellers yet!!')).toBeInTheDocument();
    });
  });

  it('should clear all filters and show Resellers for default filters', async () => {
    renderResellers();

    await userEvent.type(screen.getByTestId('merchant_name'), 'abc');
    await userEvent.click(screen.getByTestId('status'));
    await userEvent.click(screen.getByText('Approval Pending'));
    await userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no resellers yet!!')).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('Clear'));

    await waitFor(() => {
      expect(screen.getAllByText('Ibaco').length).toBe(resellersListResponse.data.items.length);
    });
  });
});

import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render } from 'test-utils';

import ResellerAccounts from 'merchant/views/GCMS/Resellers/ResellerAccounts';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderAccounts = () => {
  render(
    <GCMSTestPageRenderer>
      <ResellerAccounts />
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Reseller Accounts', () => {
  it('should render resellers account page', async () => {
    renderAccounts();

    await waitFor(() => {
      expect(screen.getByText('Virtual Account Details')).toBeInTheDocument();
      expect(screen.getByText('Account number')).toBeInTheDocument();
    });
  });
});

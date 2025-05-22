import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';

import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render } from 'test-utils';

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

describe('<Resellers />', () => {



  
  it('should render resellers list page', async () => {
    renderResellers();

    await waitFor(() => {
      expect(screen.getByText('Resellers')).toBeInTheDocument();
    });
  });
  
});

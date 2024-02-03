import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import OrderCartBillingSection from 'merchant/views/GCMS/Orders/OrderCartBillingSection';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { waitForLoadingToFinish } from 'test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

jest.mock('react-router-dom', () => ({
  useParams: () => ({ resellerId: 'N91osUDdN9WdO9' }),
  useLocation: () => ({
    pathname: '/gcms/orders/create/cart',
    search: '',
    hash: '',
    state: { resellerId: 'N91osUDdN9WdO9' },
    key: '',
  }),
}));

describe('GCMS: Orders:Create:Cart:Billing', () => {
  it('should render cart billing section', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCartBillingSection />
      </GCMSTestPageRenderer>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Billing Details')).toBeInTheDocument();
    expect(screen.getByText('karantaka')).toBeInTheDocument();
    expect(screen.getByText('GSTIN: GST1234')).toBeInTheDocument();
  });
});

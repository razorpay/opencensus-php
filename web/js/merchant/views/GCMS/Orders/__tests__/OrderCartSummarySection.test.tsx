import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import OrderCartSummarySection from 'merchant/views/GCMS/Orders/OrderCartSummarySection';
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
    state: { resellerId: 'N91osUDdN9WdO9', orderId: 'NMmhaRfFheRmhA' },
    key: '',
  }),
}));

describe('GCMS: Orders:Create:Cart:Summary', () => {
  it('should render cart summary section', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCartSummarySection />
      </GCMSTestPageRenderer>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Order Details:')).toBeInTheDocument();
    expect(screen.getByText('Special New Year Gift Card')).toBeInTheDocument();
    expect(screen.getByText('Thank You Gift Card')).toBeInTheDocument();
  });
});

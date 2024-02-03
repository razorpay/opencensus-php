import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import { convertUnixToDate } from 'common/utils/rzp-utils';
import OrderCartStatusSection from 'merchant/views/GCMS/Orders/OrderCartStatusSection';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { waitForLoadingToFinish } from 'test-utils';

import { orderResponse } from './mocks/fixtures';

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

describe('GCMS: Orders:Create:Cart:Status', () => {
  it('should render status items section', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCartStatusSection />
      </GCMSTestPageRenderer>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Order ID:')).toBeInTheDocument();
    expect(screen.getByText('NMmhaRfFheRmhA')).toBeInTheDocument();

    expect(screen.getByText('Last Modified On:')).toBeInTheDocument();
    expect(
      screen.getByText(`${convertUnixToDate(orderResponse.data.updated_at)}`),
    ).toBeInTheDocument();
  });
});

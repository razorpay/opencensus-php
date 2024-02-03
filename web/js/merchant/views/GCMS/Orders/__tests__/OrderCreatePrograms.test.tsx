import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import OrderCreatePrograms from 'merchant/views/GCMS/Orders/OrderCreatePrograms';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { userEvent, waitForLoadingToFinish } from 'test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const mockUseNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  useParams: () => ({ resellerId: 'N91osUDdN9WdO9' }),
  useLocation: () => ({
    pathname: '/gcms/orders/create/cart',
    search: '',
    hash: '',
    state: { resellerId: 'N91osUDdN9WdO9', orderId: 'NMmhaRfFheRmhA' },
    key: '',
  }),
  useNavigate: () => mockUseNavigate,
}));

describe('GCMS: Orders:Create:Programs', () => {
  it('should render create programs', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCreatePrograms />
      </GCMSTestPageRenderer>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Create Order')).toBeInTheDocument();
    expect(screen.getByText('Thank You Gift Card')).toBeInTheDocument();
  });

  it('should be able to go back', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCreatePrograms />
      </GCMSTestPageRenderer>,
    );

    await userEvent.click(screen.getByText('Go back'));
    expect(mockUseNavigate).toHaveBeenCalled();
  });
});

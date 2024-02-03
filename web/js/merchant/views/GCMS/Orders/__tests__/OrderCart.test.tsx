import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import OrderCart from 'merchant/views/GCMS/Orders/OrderCart';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { userEvent } from 'test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const mockedUseNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  useParams: () => ({ resellerId: 'N91osUDdN9WdO9' }),
  useNavigate: () => mockedUseNavigate,
  useLocation: () => ({
    pathname: '/gcms/orders/create/cart',
    search: '',
    hash: '',
    state: { resellerId: 'N91osUDdN9WdO9' },
    key: '',
  }),
}));

const orderCartRenderer = () =>
  rootRender(
    <GCMSTestPageRenderer>
      <OrderCart />
    </GCMSTestPageRenderer>,
  );

describe('GCMS: Orders:Create:Cart', () => {
  it('should render orders cart page', () => {
    orderCartRenderer();
    expect(screen.getByText('Cart')).toBeInTheDocument();
    expect(screen.getByText('Program Details')).toBeInTheDocument();
  });

  it('should be able to click on add programs and navigate', async () => {
    orderCartRenderer();
    await userEvent.click(screen.getByText('Add Programs'));
    expect(mockedUseNavigate).toHaveBeenCalled();
  });

  it('should be able to click on back and navigate', async () => {
    orderCartRenderer();
    await userEvent.click(screen.getByText('Go back'));
    expect(mockedUseNavigate).toHaveBeenCalled();
  });
});

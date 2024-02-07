import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';

import OrderCreateProgramDenominationsModal from 'merchant/views/GCMS/Orders/OrderCreateProgramDenominationsModal';
import { programsListResponse } from 'merchant/views/GCMS/Resellers/__tests__/mocks/fixtures';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { userEvent } from 'test-utils';

import { orderItemsResponse } from './mocks/fixtures';

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

describe('GCMS: Orders:Create:Programs:DenominationModal', () => {
  it('should render program denominations modal', () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCreateProgramDenominationsModal
          orderItems={orderItemsResponse.data.order_items}
          isOpen={true}
          setIsOpen={jest.fn()}
          /* @ts-expect-error */
          sku={programsListResponse.data.items[0]}
        />
      </GCMSTestPageRenderer>,
    );

    expect(screen.getByText('Thank You Gift Card')).toBeInTheDocument();
  });

  it('should be able to click add to cart button', async () => {
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCreateProgramDenominationsModal
          orderItems={orderItemsResponse.data.order_items}
          isOpen={true}
          setIsOpen={jest.fn()}
          /* @ts-expect-error */
          sku={programsListResponse.data.items[0]}
        />
      </GCMSTestPageRenderer>,
    );

    await userEvent.click(screen.getByText('Add to cart'));
  });

  it('should be able to cancel the modal', async () => {
    const mockSetIsOpen = jest.fn();
    rootRender(
      <GCMSTestPageRenderer>
        <OrderCreateProgramDenominationsModal
          orderItems={orderItemsResponse.data.order_items}
          isOpen={true}
          setIsOpen={mockSetIsOpen}
          /* @ts-expect-error */
          sku={programsListResponse.data.items[0]}
        />
      </GCMSTestPageRenderer>,
    );

    await userEvent.click(screen.getByText('Cancel'));
  });
});

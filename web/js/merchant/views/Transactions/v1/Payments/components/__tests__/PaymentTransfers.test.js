import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import cloneDeep from 'lodash/cloneDeep';

import store from 'merchant/store';
import PaymentTransfers from 'merchant/views/Transactions/v1/Payments/components/PaymentTransfers';
import { render, screen, userEvent } from 'test-utils';

describe('PaymentTransfers', () => {
  const storeData = store.getState();
  const getStateSpy = jest.spyOn(store, 'getState');
  getStateSpy.mockImplementation(() => {
    const clonedStore = cloneDeep(storeData);
    clonedStore.session.user = {
      ...clonedStore.session.user,
      isAllowedEdit: jest.fn(() => true),
    };
    return clonedStore;
  });
  const payment = {
    id: 'payment_id_1',
    status: 'captured',
    amount: 20000,
    amount_transferred: 10000,
  };
  const defaultProps = {
    payment,
    transfers: {
      items: [
        { id: '1', amount: 2000, createdAt: 612345578 },
        { id: '2', amount: 200, createdAt: 612345578 },
      ],
    },
  };

  const renderApp = ({ props } = {}) => {
    return render(<PaymentTransfers {...defaultProps} {...props} />);
  };

  test('should render payment transfers when transfers are present', async () => {
    renderApp();
    expect(screen.getByText('2 transfers')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Transfer Details'));
    expect(screen.getByRole('table')).toBeInTheDocument();
  });

  test('should render a single payment transfer when a transfer is present', () => {
    renderApp({
      props: {
        transfers: {
          items: [{}],
        },
      },
    });
    expect(screen.getByText('1 transfer')).toBeInTheDocument();
  });

  test('should render payment transfers when transfers are loading', () => {
    renderApp({
      props: {
        transfers: { items: [], loading: true },
      },
    });
    expect(screen.queryByText('2 transfers')).not.toBeInTheDocument();
  });

  test('should not render payment transfers when no transfers are present and payment status is not captured', () => {
    renderApp({
      props: {
        payment: {},
        transfers: { items: [] },
      },
    });
    expect(screen.getByText('No transfers created')).toBeInTheDocument();
  });

  test('should not render payment transfers when no transfers are present and payment status is captured', () => {
    renderApp({
      props: {
        payment: { status: 'captured' },
        transfers: { items: [] },
      },
    });
    expect(screen.getByText('No transfers created yet')).toBeInTheDocument();
  });

  test('should not render payment transfers when payment status is either created/authorized/failed', () => {
    ['created', 'authorized', 'failed'].forEach((status) => {
      const { unmount } = renderApp({
        props: {
          payment: {
            status,
          },
        },
      });
      expect(screen.getByText('Not Applicable')).toBeInTheDocument();
      expect(screen.getByText('Only captured payments can be transferred.')).toBeInTheDocument();
      unmount();
    });
  });

  test('should not render create transfer button when blocktransfer is true', () => {
    renderApp({
      props: {
        blockTransfer: true,
      },
    });
    expect(screen.queryByText('Create Transfer')).not.toBeInTheDocument();
  });
});

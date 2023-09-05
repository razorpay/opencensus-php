import React from 'react';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModal';
import { render } from 'test-utils';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import * as showWhenUtils from 'merchant/components/ShowWhen';

export const showWhenUtilSpy = jest.spyOn(showWhenUtils, 'showWhenUtil');

export const payment = {
  current_balance: {
    data: {},
  },
  transfers: {
    items: [
      { id: '1', amount: 2000, createdAt: 612345578 },
      { id: '2', amount: 200, createdAt: 612345578 },
    ],
  },
  payment: {
    id: 'payment_id_1',
    fetchTransfers: jest.fn(),
    // timeout added to mimic api call
    refund: jest.fn(
      () =>
        new Promise((resolve) => {
          setTimeout(resolve, 150);
        }),
    ),
    refundOfflinePayment: jest.fn(() =>
      Promise.resolve({
        data: {
          success: true,
        },
      }),
    ),
    voidPayment: jest.fn(() =>
      Promise.resolve({
        data: {
          success: true,
        },
      }),
    ),
    notes: { txn_id: 'test_txn_id_123', external_ref_id1: 'test_external_ref_id1_123' },
    gateway_refund_support: false,
    status: 'captured',
    amount_transferred: 10000,
    currency: 'INR',
    amount: 20000,
    amount_refunded: 0,
    optimizer_provider: 'paytm',
    direct_settlement_refund: true,
    disputes: {
      items: [
        {
          id: 'qw1efe3dwf',
          status: 'open',
        },
        {
          id: 'er2efe3dwf',
          status: 'closed',
        },
      ],
    },
  },
};

export const session = {
  user: {
    isMarketplaceEnabled: true,
    merchants: {},
    isSingleReconEnabled: true,
    isOptimizerEnabled: true,
    isRefundCreditSelfServeEnabled: true,
    isRefundSourceFallbackEnabled: true,
  },
  org: {},
};

export const defaultProps = {
  fetchMerchantBalance: jest.fn(),
  onRefund: jest.fn(),
  fetchRefundFee: jest.fn(() =>
    Promise.resolve({
      data: {
        fee: 100,
        tax: 18,
      },
    }),
  ),
  afterRefund: jest.fn(),
};

export const renderApp = ({ props, initialState } = {}) => {
  return render(
    <ConfirmModalProvider>
      <RefundModal {...defaultProps} {...props} />
    </ConfirmModalProvider>,
    {
      initialState: {
        payment,
        session,
        ...initialState,
      },
    },
  );
};

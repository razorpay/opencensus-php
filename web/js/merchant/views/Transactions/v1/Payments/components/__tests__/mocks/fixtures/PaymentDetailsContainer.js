import React from 'react';
import PaymentDetailsContainer from 'merchant/views/Transactions/v1/Payments/Details';
import { render } from 'test-utils';
import store from 'merchant/store';
import 'jest-location-mock';
import * as settlementUtils from 'merchant/views/Settlements/v2/util';
import * as reducers from 'merchant/reducers/collection';

jest.spyOn(reducers, 'updateItemInPayments');

jest.mock('common/utils/selfServeAnalytics', () => ({
  ...jest.requireActual('common/utils/selfServeAnalytics'),
  selfServeTrackSuccess: jest.fn(),
}));

jest.mock('merchant/views/Marketplace/Transfers/New', () => ({ onClose, onCreate }) => (
  <div>
    <button type="button" onClick={onCreate}>
      Create New Transfer
    </button>
    <button type="button" onClick={onClose}>
      Close Secondary View
    </button>
  </div>
));

jest.mock('merchant/views/Transactions/v1/Disputes/Details', () => ({ onCloseSecView }) => (
  <div>
    Dispute Details{' '}
    <button type="button" onClick={onCloseSecView}>
      Close Secondary View
    </button>
  </div>
));

jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentDetails',
  () =>
    ({
      confirmCapture,
      goToLink,
      openRefundModal,
      onRefundDetailsToggleClick,
      onUpdateReferenceId,
      viewSettlementOverview,
      showCustomSettlDetails,
      loading,
      payment,
    }) =>
      loading ? null : (
        <div>
          Payment Details {showCustomSettlDetails && <div>Settlement Details</div>}
          <button type="button" onClick={() => confirmCapture(payment)}>
            Confirm Capture
          </button>
          <button type="button" onClick={() => goToLink('details')}>
            Go To Link
          </button>
          <button type="button" onClick={() => openRefundModal(payment, {})}>
            Open Refund Modal
          </button>
          <button type="button" onClick={onRefundDetailsToggleClick}>
            Refund Details Toggle Click
          </button>
          <button type="button" onClick={onUpdateReferenceId}>
            Update Reference Id
          </button>
          <button type="button" onClick={viewSettlementOverview}>
            View Settlement Overview
          </button>
        </div>
      ),
);

jest.mock(
  'merchant/views/Transactions/v1/Payments/components/RefundModal',
  () =>
    ({ onRefund }) =>
      (
        <div>
          Refund Modal{' '}
          <button type="button" onClick={onRefund}>
            Payment Refund
          </button>
        </div>
      ),
);

jest.spyOn(settlementUtils, 'customSettlementEnabled');

export const defaultProps = {
  fetchMerchantManualAction: jest.fn(),
  entity_name: 'disputes',
};

export const defaultStore = {};

export const storeData = store.getState();

export const getStateSpy = jest.spyOn(store, 'getState');

export const renderApp = ({ props = {} } = {}) => {
  return render(<PaymentDetailsContainer {...defaultProps} {...props} />, {
    showModal: true,
    initialState: {
      session: {
        user: {
          isSingleReconEnabled: true,
          isOptimizerEnabled: true,
          isFeatureEnabled: () => true,
          isAllowedView: () => true,
          isAllowedEdit: () => true,
        },
      },
    },
  });
};

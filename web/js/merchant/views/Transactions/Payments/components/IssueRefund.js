import React, { useEffect } from 'react';
import { useQuery } from 'react-query';
import {
  FETCH_EZETAP_KEY_NAME,
  REFUND_STATUSES,
} from 'merchant/views/Transactions/Payments/constants';

const IssueRefund = ({
  refundStatus,
  payment,
  collectEzetapKeys,
  onRefundStatusClick,
  gatewayRefundNotSupported,
  fetchEzetapKeys,
}) => {
  const paymentByCardOffline = payment.method === 'card' && payment.receiver_type === 'pos';

  const { refetch: fetchEzetapKey, data: ezetapData } = useQuery(
    FETCH_EZETAP_KEY_NAME,
    async () => {
      const dataPromise = await fetchEzetapKeys();
      return dataPromise?.data || {};
    },
    {
      enabled: false,
      refetchOnWindowFocus: false,
      staleTime: Infinity,
    },
  );

  useEffect(() => {
    // fetch ezetap credentials if transaction is done by cards via ezetap devices
    if (paymentByCardOffline) {
      fetchEzetapKey();
    }
  }, [paymentByCardOffline]);

  if (gatewayRefundNotSupported) return null;

  const hasOpenNonFraudDisputes =
    payment?.disputes?.items?.filter(
      ({ status, phase }) => ['open', 'under_review'].indexOf(status) > -1 && phase !== 'fraud',
    )?.length ?? 0;
  const refundInProgress =
    payment?.status !== 'refunded' && payment?.notes?.refund_status === REFUND_STATUSES.PROCESSING;

  const triggerRefund = () => {
    if (paymentByCardOffline && !ezetapData?.appKey) {
      collectEzetapKeys();
    } else {
      onRefundStatusClick();
    }
  };

  return (
    <>
      <button
        type="button"
        className="btn btn-default"
        onClick={triggerRefund}
        disabled={hasOpenNonFraudDisputes && refundInProgress}
      >
        {refundStatus === 'partial' ? 'Issue another Refund' : 'Issue Refund'}
      </button>
      {Boolean(hasOpenNonFraudDisputes) && (
        <p className="text-danger">
          Refunds are disabled as there {hasOpenNonFraudDisputes > 1 ? 'are ' : 'is an '} open&nbsp;
          {hasOpenNonFraudDisputes > 1 ? 'disputes' : 'dispute'} on this payment
        </p>
      )}
    </>
  );
};

export default IssueRefund;

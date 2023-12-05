import { useMutation } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { useState } from 'react';
import { useApp } from 'common/context/App';
import { getMode } from 'common/services/mode';

export default function usePaymentVolume(): any {
  const [lastUpdated, setLastUpdated] = useState<number>(0);
  const [transactionAmount, setTransactionAmount] = useState<number>(0);
  const { user } = useApp();
  const payload = {
    filters: {
      default: [
        {
          created_at: { gte: user.created_at, lte: new Date().getTime() },
          authorized_at: { gt: 0 },
        },
      ],
    },
    aggregations: {
      transactionVolume: {
        agg_type: 'sum',
        details: { index: 'payments', column: 'base_amount', mode: getMode(user.current) },
      },
    },
  };
  const fetchPaymentVolume = async () => {
    const response = await fetch<any>({
      url: 'merchant/analytics',
      mode: 'live',
      method: 'POST',
      data: payload,
    });
    return response;
  };
  const { mutate: fetchPayment } = useMutation({
    mutationFn: fetchPaymentVolume,
    onSuccess: (res) => {
      if (res?.transactionVolume?.last_updated_at) {
        setLastUpdated(res.transactionVolume.last_updated_at);
      }
      if (res?.transactionVolume?.result.length) {
        setTransactionAmount(res.transactionVolume.result[0].value);
      }
    },
  });

  return { lastUpdated, fetchPayment, transactionAmount };
}

import { merchantFetch } from 'merchant/utils/ajax';

export const fetchPaymentIdDetails = (id: string): Promise<any> => {
  return merchantFetch({
    url: `payments/${id}`,
    method: 'GET',
    data: {
      expand: ['card', 'emi_plan', 'disputes', 'transaction', 'transaction.settlement'],
      dashboard_flag: ['refund_create_data'],
    },
  });
};

export const fetchPaymentIdRefundDetails = (id: string): Promise<any> => {
  return merchantFetch(`payments/${id}/refunds`);
};

export const fetchRefundIdDetails = (id: string): Promise<any> => {
  return merchantFetch({
    url: `refunds/${id}`,
    method: 'GET',
  });
};

export const fetchInstantRefundFeeFn = (id, amount): Promise<any> => {
  const method = 'get';
  const url = `refunds/fee/`;
  const data = {
    payment_id: id,
    amount,
  };

  return merchantFetch({ url, data, method });
};

export const fetchTransfersFn = (paymentId) => {
  return (): Promise<any> => {
    return merchantFetch({
      url: `payments/${paymentId}/transfers`,
    });
  };
};

export const refundPaymentFn = (paymentId) => {
  return (params): Promise<any> => {
    const method = 'post';
    const url = `payments/${paymentId}/refund`;
    const data = {
      amount: params.amount,
      reverse_all: params.reverse_all,
      notes: {
        comment: params.comment,
      },
      speed: params.speed,
    };

    return merchantFetch({ method, data, url });
  };
};

export const fetchBankTransfer = (id): Promise<any> => {
  return merchantFetch(`payments/${id}/bank_transfer`);
};

export const fetchPaymentIdTimelineData = (id): Promise<any> => {
  return merchantFetch(`merchant/payment/${id}/timeline`);
};

export const fetchTransactionTimelineDataFn = (id: string): Promise<any> => {
  return merchantFetch(`settlements/transaction/timeline?id=${id}`);
};

export const fetchAppDetails = (id): Promise<any> => {
  return merchantFetch(`partner/subm_payment/app_details?payment_id=${id}`);
};

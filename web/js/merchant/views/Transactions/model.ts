import { CommonApiResponse } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

import { createPayloadForSavePreferences } from './utils';
import { ApplicationDetails } from './v2/Payments/components/PaymentsDetails/types';

export const fetchPaymentIdDetails = (id: string, dashboardFlag?: string): Promise<any> => {
  const dashboardFlags = ['refund_create_data'];
  if (dashboardFlag) {
    dashboardFlags.push(dashboardFlag);
  }
  return merchantFetch({
    url: `payments/${id}`,
    method: 'GET',
    data: {
      expand: ['card', 'emi_plan', 'disputes', 'transaction', 'transaction.settlement'],
      dashboard_flag: dashboardFlags,
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

export const fetchAppDetails = (
  id: string,
): Promise<CommonApiResponse<{ application: ApplicationDetails | null }>> => {
  return merchantFetch(`partner/subm_payment/app_details?payment_id=${id}`);
};

export const saveMerchantColumnPreferences = (selectedColumnsListData): Promise<any> => {
  const payload = { data: createPayloadForSavePreferences(selectedColumnsListData) };
  return merchantFetch({
    method: 'post',
    url: `merchants/payments/saved_columns`,
    data: payload,
  });
};

export const fetchMerchantColumnPreferences = (): Promise<any> => {
  return merchantFetch({
    method: 'get',
    url: `merchants/payments/saved_columns`,
  });
};

export const fetchPaymentNotesKeys = (): Promise<any> => {
  return merchantFetch({
    method: 'get',
    url: `payments/transaction_tab/notes_keys`,
  });
};

export const fetchEncodedPaymentReceipt = async (payment_id) => {
  const strippedPaymentId = payment_id.replace('pay_', '');
  const { data } = await merchantFetch({
    absUrl: `/ezetap/receipt/${strippedPaymentId}`,
    appendModeInURL: false,
    method: 'get',
  });

  return data;
};

import { merchantFetch } from 'merchant/utils/ajax';

export interface BounceMemoPropsT {
  closeModal: () => void;
}

export const fetchBouncememo = async (params) => {
  const strippedPaymentId = params.paymentID.replace('pay_', '');
  const data = {
    payment_id: strippedPaymentId, // get capturable amount
    merchant_id: params.merchantId, // send merchantID
  };
  const resp = await merchantFetch({
    absUrl: `/v1/report/bounce-memo`,
    method: 'post',
    data,
  });
  return resp;
};

export const fetchBounceMemoBulk = async (dateParams) => {
  const data = {
    start_time: dateParams.from,
    end_time: dateParams.to,
    payment_methods: dateParams.paymentMethods,
    merchant_id: dateParams.paymentID,
  };
  const resp = await merchantFetch({
    absUrl: `/v1/report/bounce-memo`,
    method: 'post',
    data,
  });
  return resp;
};

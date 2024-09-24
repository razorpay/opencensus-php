import { merchantFetch } from 'merchant/utils/ajax';

export interface BounceMemoPropsT {
  closeModal: () => void;
}

export const fetchBouncememo = async (paymentID) => {
  const strippedPaymentId = paymentID.replace('pay_', '');
  const data = {
    payment_id: strippedPaymentId, // get capturable amount
  };
  const resp = await merchantFetch({
    absUrl: `/v1/report/bounce-memo`,
    method: 'post',
    data,
  });
  return resp;
};

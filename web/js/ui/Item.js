import { methods } from 'common/data';

const methodKeys = Object.keys(methods);

export const merchantId = item => <div class="link">{item.merchant_id}</div>;
export const paymentMethod = item => methods[item.payment_method];
export const bool = value => (
  <i class={`${value ? 'i-yes text-success' : 'i-no text-danger'}`} />
);

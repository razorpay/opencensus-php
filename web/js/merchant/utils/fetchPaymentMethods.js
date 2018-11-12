import { merchantFetch } from './ajax';

export default function fetchPaymentMethods() {
  return merchantFetch('merchant/methods').then(response => {
    if (response.data) {
      return response.data;
    }
  });
}

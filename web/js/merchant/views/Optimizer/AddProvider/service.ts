import { merchantFetch } from 'merchant/utils/ajax';

export function addProviderV3({ payload }) {
  return merchantFetch({
    url: 'v3/terminals/proxy/optimizer/mid/provider',
    method: 'post',
    data: payload,
  });
}

export function fetchRazorpayMethodCoverage() {
  return merchantFetch({
    url: 'v3/terminals/proxy/optimizer/mid/razorpay_enabled_methods',
    method: 'get',
  });
}

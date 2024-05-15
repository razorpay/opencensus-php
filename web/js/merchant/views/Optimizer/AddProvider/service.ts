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

export function fetchPayments({ count = 5, status, notes, terminalId }) {
  let url = 'payments?skip=0';
  if (count) {
    url = `${url}&count=${count}`;
  }
  if (status) {
    url = `${url}&status=${status}`;
  }
  if (notes) {
    url = `${url}&notes=${notes}`;
  }
  if (terminalId) {
    url = `${url}&terminal_id=${terminalId}`;
  }
  return merchantFetch({
    url,
    method: 'GET',
  });
}

export function createRefund({ id, amount }) {
  return merchantFetch({
    url: `payments/${id}/refund`,
    method: 'POST',
    data: {
      amount,
      reverse_all: '0',
      notes: {
        comment: '',
      },
      speed: 'normal',
    },
  });
}

export function fetchRefundDetails(id) {
  return merchantFetch({
    url: `refunds/${id}`,
    method: 'GET',
  });
}

export function fetchRefund(paymentId) {
  return merchantFetch({
    url: `payments/${paymentId}/refunds`,
    method: 'GET',
  });
}

export function updateProvider({ providerId, payload }) {
  return merchantFetch({
    url: `v3/terminals/proxy/optimizer/merchant/provider/${providerId}`,
    method: 'patch',
    data: payload,
  });
}

export function fetchGatewayEnabledMethods(providerId) {
  return merchantFetch({
    url: `v3/terminals/proxy/optimizer/merchant/provider/${providerId}/enabled_methods`,
    method: 'get',
  });
}

export function refreshGatewayEnabledMethods(providerId) {
  return merchantFetch({
    url: `v3/terminals/proxy/optimizer/merchant/provider/${providerId}/refresh_enabled_methods`,
    method: 'put',
  });
}

import { merchantFetch } from 'merchant/utils/ajax';

const COLLECTIONS_BASE_URL = 'capital_collections/service/v1';

const request = (url, data, method = 'get') => {
  return merchantFetch({
    url,
    mode: 'live',
    method,
    ...data,
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export default {
  getPrimaryBalance() {
    return request('primary_balance');
  },
  getPlans() {
    return request(`${COLLECTIONS_BASE_URL}/plans`, {
      params: {
        product_type: 'PRODUCT_TYPE_LOANS',
      },
    });
  },
  getInstallment(planId) {
    return request(
      `${COLLECTIONS_BASE_URL}/installments`,
      {
        params: {
          plan_ids: planId,
        },
      },
      'get',
    );
  },
  getRepayments({ count, skip, product_entity_reference_id }) {
    return request(
      `${COLLECTIONS_BASE_URL}/repayments`,
      {
        params: {
          count,
          skip,
          product_entity_reference_id,
          product_entity_type: 'PRODUCT_ENTITY_TYPE_DISBURSEMENT',
        },
      },
      'get',
    );
  },
  getRepaymentDetails({ id }) {
    return request(`${COLLECTIONS_BASE_URL}/repayments/${id}`);
  },
  getUpcomingPayments(installmentId) {
    return request(
      `${COLLECTIONS_BASE_URL}/installments/${installmentId}/auto-collection-schedule`,
    );
  },
  createRepaymentOrder(data) {
    return request(`${COLLECTIONS_BASE_URL}/repayments`, { data }, 'post');
  },
  updateRepayment(data) {
    return request(`${COLLECTIONS_BASE_URL}/repayments/order-callback`, { data }, 'put');
  },
};

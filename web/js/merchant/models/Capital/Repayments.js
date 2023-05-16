import { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from 'merchant/models/GenericEntity';

export default class RepaymentEntity extends GenericEntity {
  // eslint-disable-next-line no-useless-constructor
  constructor() {
    super();
  }

  request = (url, data, progressTracker, { method = 'post', mode = 'live' } = {}) => {
    return merchantFetch({
      url,
      mode,
      method,
      data,
      headers: {
        'Content-Type': 'application/json',
      },
      onUploadProgress: progressTracker,
    });
  };

  resourceUrlPrefix = (endpoint, version = 'v1') =>
    `capital_collections/service/${version}/${endpoint}`;

  fetchRepayments(data) {
    return this.request(`${this.resourceUrlPrefix('repayments')}`, data, null, {
      method: 'get',
    }).catch(() => {
      return {
        data: {
          repayments: [],
        },
      };
    });
  }

  fetchBalances(data) {
    return this.request(`${this.resourceUrlPrefix('plans_balances')}`, data, null, {
      method: 'get',
    });
  }

  fetchCollectedAmount(plan_id) {
    return this.request(
      `${this.resourceUrlPrefix('plans')}/collected_amount?plan_ids=${plan_id}`,
      null,
      null,
      {
        method: 'get',
      },
    );
  }

  createRepayment(data) {
    return this.request(`${this.resourceUrlPrefix('repayments')}`, data);
  }

  fetchRepayment(id) {
    return this.request(`${this.resourceUrlPrefix('repayments')}/${id}`, null, null, {
      method: 'get',
    });
  }

  updateRepayment(data) {
    return this.request(`${this.resourceUrlPrefix('repayments')}/order-callback`, data, null, {
      method: 'put',
    });
  }
}

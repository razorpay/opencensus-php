import { merchantFetch } from '../../utils/ajax';
import ajax from 'common/utils/ajax';
import GenericEntity from '../GenericEntity';

export default class RepaymentEntity extends GenericEntity {
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
    }).catch((e) => {
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

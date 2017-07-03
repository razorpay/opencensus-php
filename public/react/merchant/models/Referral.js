import Entity from './Entity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils';

export default class Referral extends Entity {
  resourceUrl = '/referrals';

  fetchAll(params = {}) {
    const Klass = this.constructor;
    let {
      id,
      appendModeInURL = false,
      appendModeInQueryParam,
      ...data
    } = params;

    return ajax(this.resourceUrl, {
      appendModeInURL,
      appendModeInQueryParam,
      data,
    }).then(response => {
      /* `fetchAll` for referrals send `response.data` instead of
       * `response.data.items`
       */
      response.data = response.data.map(item => new Klass().deserialize(item));
      return response;
    });
  }

  switchMerchant(merchantId) {
    /* TODO: remove reload once ported entirely to React */

    return ajax({
      url: `/settings/merchants/switch/${merchantId}`,
      appendModeInURL: false,
    }).then(response => window.location.reload());
  }

  createLogin(params = {}) {
    const Klass = this.constructor;
    let data = params;
    return ajax({
      url: '/subusers',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then(response => new Klass().deserialize(response.data));
  }

  createMerchant() {
    const Klass = this.constructor;
    let data = {
      name: this.name,
      email: this.email,
    };
    return ajax({
      url: '/submerchants',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then(response => new Klass().deserialize(response.data));
  }
}

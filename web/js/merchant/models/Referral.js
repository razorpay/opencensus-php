import Entity from './GenericEntity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils';

export default class Referral extends Entity {
  resourceUrl = 'referrals';
  fetchAll(data = {}) {
    return this.makeGenericAjaxCall({
      data: {
        ...data,
        mode: 'live',
      },
    }).then(response => {
      /* `fetchAll` for referrals send `response.data` instead of
      * `response.data.items`
      */
      response.data = response.data.map(item => new Referral(item));
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

  createLogin(merchantId) {
    return ajax(
      {
        url: `/submerchant/user/${merchantId}`,
        method: 'POST',
        data: { mode: 'live' },
      },
      {},
      '/merchant/api'
    ).then(response => new Referral(response.data));
  }

  createMerchant() {
    let data = {
      name: this.name,
    };
    this.email ? (data.email = this.email) : null;

    return ajax({
      url: '/submerchants',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then(response => new Referral(response.data));
  }
}

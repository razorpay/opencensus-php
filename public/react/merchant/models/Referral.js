import Entity from './Entity';
import ajax from 'merchant/utils/ajax';
import { getFixedINRAmount, isBlank } from 'rzp/utils/rzp-utils';

export default class Referral extends Entity {
  fetchAll(params = {}) {
    let {
      id,
      appendModeInURL = false,
      appendModeInQueryParam,
      ...data
    } = params;
    data.route_name = 'merchant_fetch_referrals';

    return ajax('/user/generic', {
      appendModeInURL,
      appendModeInQueryParam,
      data,
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

  createLogin(params = {}) {
    let data = {
      email: params.email,
      password: params.password,
      password_confirmation: params.password_confirmation,
    };

    return ajax({
      url: '/user/generic',
      method: 'POST',
      appendModeInURL: false,
      data: {
        route_name: 'create_submerchant_user',
        url_params: JSON.stringify({
          '{id}': params.id,
        }),
        body: data,
      },
    }).then(response => new Referral(response.data));
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

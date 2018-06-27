import ajax from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

export default class Submerchant extends GenericEntity {
  resourceUrl = 'referrals';

  fetchAll() {
    const data = { mode: 'live' };
    return this.makeGenericAjaxCall({ data }).then(response => {
      // since /referrals api call do not return data in data.items,
      // rather in data itself
      response.data.items = response.data;
      return response;
    });
  }

  create(data) {
    return ajax({
      url: '/submerchants',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then(response => response.data);
  }
}

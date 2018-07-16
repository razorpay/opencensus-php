import ajax from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

export default class Submerchant extends GenericEntity {
  resourceUrl = 'submerchants';

  create(data) {
    return ajax({
      url: '/submerchants',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then(response => response.data);
  }

  invite(submerchantId, data) {
    return ajax(
      {
        url: `/submerchant/user/${submerchantId}`,
        method: 'POST',
        data: {
          ...data,
          mode: 'live',
        },
      },
      {},
      '/merchant/api'
    ).then(response => response.data);
  }
}

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
    }).then(response => ({
      ...response.data,
      // will remove these two lines once api fixes it
      id: 'acc_' + response.data.id,
      dashboard_access: true,
    }));
  }

  invite(submerchantId, data) {
    return ajax(
      {
        // replacing `acc_` since api doesn't support it
        url: `/submerchant/user/${submerchantId.replace('acc_', '')}`,
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

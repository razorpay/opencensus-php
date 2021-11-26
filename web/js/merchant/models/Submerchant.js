import ajax, { merchantFetch } from 'merchant/utils/ajax';
import GenericEntity from './GenericEntity';

export default class Submerchant extends GenericEntity {
  resourceUrl = 'submerchants';

  create({ isInsertTable, ...data }) {
    return ajax({
      url: '/submerchants',
      method: 'POST',
      appendModeInURL: false,
      data,
    }).then((response) => {
      return {
        ...response.data,
        isInsertTable,
      };
    });
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
      '/merchant/api',
    ).then((response) => response.data);
  }

  resendInvite(submerchantId) {
    return merchantFetch({
      url: `submerchants/${submerchantId.replace('acc_', '')}/reset_password`,
      method: 'post',
    }).then((response) => response.data);
  }

  fetchAll(params = {}) {
    const data = { ...params };

    // API need to be fixed to remove this
    if (data.id) {
      data.id = data.id.replace('acc_', '');
    }

    return this.makeGenericAjaxCall({ data }).then((response) => {
      return response;
    });
  }
}

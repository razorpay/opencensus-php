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
}

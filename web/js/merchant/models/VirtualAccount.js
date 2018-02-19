import GenericEntity from './GenericEntity';
import Payment from './Payment';
import { merchantFetch } from 'rzp/utils/ajax';

const fields = [
  'id',
  'name',
  'description',
  'descriptor',
  'customer_id',
  'status',
  'receivers',
];

export default class VirtualAccount extends GenericEntity {
  resourceUrl = 'virtual_accounts';

  resourceFields() {
    let resourceFields = fields.slice();
    return resourceFields;
  }

  getRouteName() {
    return this.isNew ? 'virtual_account_create' : 'virtual_account_update';
  }

  fetchPayments() {
    const url = `${this.resourceUrl}/${this.id}/payments`;
    return this.makeGenericAjaxCall({ url }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Payment(item).deserialize()
      );
      return response;
    });
  }

  createTestPayment(data) {
    return merchantFetch({
      url: 'ecollect/validate/test',
      method: 'post',
      data,
    });
  }
}

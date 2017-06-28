import GenericEntity from './GenericEntity';
import Payment from './Payment';

const fields = [
  'id',
  'name',
  'description',
  'descriptor',
  'customer_id',
  'status',
];

export default class VirtualAccount extends GenericEntity {
  listRouteName = 'virtual_account_fetch_multiple';
  detailsRouteName = 'virtual_account_fetch';
  deleteRouteName = 'virtual_account_delete';

  receiver_types = ['bank_account'];

  resourceFields() {
    let resourceFields = fields.slice();
    if (this.isNew) {
      resourceFields.push('receiver_types');
    }
    return resourceFields;
  }

  getRouteName() {
    return this.isNew ? 'virtual_account_create' : 'virtual_account_update';
  }

  fetchPayments() {
    let data = {
      route_name: 'virtual_account_fetch_payments',
    };
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item => new Payment(item));
      return response;
    });
  }

  createTestPayment(body) {
    let data = {
      body,
      route_name: 'bank_transfer_process',
    };
    return this.makeGenericAjaxCall({
      method: 'post',
      data,
    });
  }
}

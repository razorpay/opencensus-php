import GenericEntity from './GenericEntity';
import Payment from './Payment';

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

  createTestPayment(body) {
    let data = {
      body,
      route_name: 'bank_transfer_process',
    };
    return this.makeGenericAjaxCall({
      url: '/user/generic',
      method: 'post',
      data,
    });
  }
}

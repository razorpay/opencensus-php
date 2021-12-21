import GenericEntity from './GenericEntity';
import Payment from './Payment';
import { merchantFetch } from 'merchant/utils/ajax';

const fields = [
  'id',
  'name',
  'description',
  'descriptor',
  'customer_id',
  'status',
  'receivers',
  'notes',
  'close_by',
  'allowed_payers',
];

export default class VirtualAccount extends GenericEntity {
  resourceUrl = 'virtual_accounts';

  resourceFields() {
    const resourceFields = fields.slice();
    return resourceFields;
  }

  getRouteName() {
    return this.isNew ? 'virtual_account_create' : 'virtual_account_update';
  }

  fetchPayments() {
    const url = `${this.resourceUrl}/${this.id}/payments`;
    return this.makeGenericAjaxCall({ url }).then((response) => {
      response.data.items = response.data.items.map((item) => new Payment(item).deserialize());
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

  close() {
    const url = `${this.resourceUrl}/${this.id}/close`;
    return this.makeGenericAjaxCall({ method: 'POST', url }).then((response) => {
      return new VirtualAccount(response.data).deserialize();
    });
  }

  updateAccountDetails(id, data) {
    const url = `${this.resourceUrl}/${id}/receivers`;
    return this.makeGenericAjaxCall({ method: 'POST', url, data }).then((response) => {
      return new VirtualAccount(response.data).deserialize();
    });
  }

  updateCloseBy(id, data) {
    const url = `merchant/${this.resourceUrl}/${id}`;
    return this.makeGenericAjaxCall({ method: 'patch', url, data }).then((response) => {
      return new VirtualAccount(response.data).deserialize();
    });
  }
}

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

export default class QRCode extends GenericEntity {
  // TODO: update the resourceUrl to qr_codes!
  resourceUrl = 'payments/qr_codes';

  resourceFields() {
    let resourceFields = fields.slice();
    return resourceFields;
  }

  fetchPayments() {
    const url = `${this.resourceUrl}/${this.id}/payments?skip=0&count=10`;
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
}

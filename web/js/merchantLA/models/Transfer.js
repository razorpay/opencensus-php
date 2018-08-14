import GenericEntity from './GenericEntity';

export default class Transfer extends GenericEntity {
  resourceUrl = 'la-transfers';

  resourceFields = [
    'account',
    'amount',
    'currency',
    'on_hold',
    'on_hold_until',
  ];

  getRouteName() {
    return 'payment_transfer';
  }

  reverse(data) {
    let params = { '{id}': this.id };

    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/reversals`,
      method: 'post',
      data,
    });
  }

  update(data) {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}`,
      method: 'patch',
      data,
    });
  }

  fetchReversals() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/reversals`,
    });
  }
}

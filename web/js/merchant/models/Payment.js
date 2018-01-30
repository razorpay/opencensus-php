import GenericEntity from './GenericEntity';
import Refund from './Refund';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import ajax from 'merchant/utils/ajax';

export default class Payment extends GenericEntity {
  // listRouteName = 'payment_fetch_multiple';
  // detailsRouteName = 'payment_fetch_by_id';
  resourceUrl = 'payments';

  fetchRefunds() {
    const url = `${this.resourceUrl}/${this.id}/refunds`;
    return this.makeGenericAjaxCall({ url }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Refund(item).deserialize()
      );
      return response;
    });
  }

  capture() {
    const method = 'post';
    const Klass = this.constructor;
    const url = `${this.resourceUrl}/${this.id}/capture`;
    const data = {
      amount: this.capturableAmount,
      currency: this.currency,
    };

    return this.makeGenericAjaxCall({ method, data, url }).then(response => {
      return new Klass(response.data);
    });
  }

  refund(params) {
    const method = 'post';
    const url = `${this.resourceUrl}/${this.id}/refund`;
    const data = {
      amount: params.amount,
      reverse_all: params.reverse_all,
      notes: {
        comment: params.comment,
      },
    };

    return this.makeGenericAjaxCall({ method, data, url });
  }

  fetchCardDetails() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/card`,
    });
  }

  fetchTransfers() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/transfers`,
    });
  }

  fetchBankTransfer() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/bank_transfer`,
    });
  }

  didDeserialize() {
    let session = this.getSession();
    this.capturableAmount = this.amount;

    if (session.user.tags.indexOf('Feebearer') > -1) {
      this.capturableAmount = this.amount - this.fee;
    }
  }
}

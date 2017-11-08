import GenericEntity from './GenericEntity';
import Refund from './Refund';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import ajax from 'merchant/utils/ajax';

export default class Payment extends GenericEntity {
  listRouteName = 'payment_fetch_multiple';
  detailsRouteName = 'payment_fetch_by_id';

  fetchRefunds() {
    let data = {};
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });
    data.route_name = 'payment_fetch_refunds';
    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Refund(item).deserialize()
      );
      return response;
    });
  }

  capture() {
    let data = {};
    const method = 'post';
    const Klass = this.constructor;

    data.body = {
      amount: this.capturableAmount,
      currency: this.currency,
    };

    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    data.route_name = 'payment_capture';
    return this.makeGenericAjaxCall({ method, data }).then(response => {
      return new Klass(response.data);
    });
  }

  refund(params) {
    let data = {};
    const method = 'post';

    data.body = {
      amount: params.amount,
      reverse_all: params.reverse_all,
      notes: {
        comment: params.comment,
      },
    };

    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    data.route_name = 'payment_refund';
    return this.makeGenericAjaxCall({ method, data });
  }

  fetchCardDetails() {
    let data = {};
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });
    data.route_name = 'payment_fetch_card_details';
    return this.makeGenericAjaxCall({ data });
  }

  fetchTransfers() {
    const data = {};
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    data.route_name = 'payment_fetch_transfers';
    return this.makeGenericAjaxCall({ data });
  }

  fetchBankTransfer() {
    const data = {};

    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    data.route_name = 'payment_bank_transfer_fetch';

    return this.makeGenericAjaxCall({ data });
  }

  didDeserialize() {
    let session = this.getSession();
    this.capturableAmount = this.amount;

    if (session.user.tags.indexOf('Feebearer') > -1) {
      this.capturableAmount = this.amount - this.fee;
    }
  }
}

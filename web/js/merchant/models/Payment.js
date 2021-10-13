import GenericEntity from './GenericEntity';
import Refund from './Refund';
import moment from 'moment';

export default class Payment extends GenericEntity {
  // listRouteName = 'payment_fetch_multiple';
  // detailsRouteName = 'payment_fetch_by_id';
  resourceUrl = 'payments';

  fetchRefunds() {
    const url = `${this.resourceUrl}/${this.id}/refunds`;
    return this.makeGenericAjaxCall({ url }).then((response) => {
      response.data.items = response.data.items.map((item) => new Refund(item).deserialize());
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

    return this.makeGenericAjaxCall({ method, data, url }).then((response) => {
      return new Klass(response.data);
    });
  }

  analyticsPayload() {
    const payment = this;
    return {
      paymentId: payment.id,
      paymentStatus: payment.status,
      paymentMethod: payment.method,
      createdAt: moment.unix(payment.created_at),
      description: payment.description,
      totalFee: payment.fee,
      orderId: payment.order_id,
      amount: payment.amount,
    };
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
      speed: params.speed,
    };

    return this.makeGenericAjaxCall({ method, data, url });
  }

  transfer(data) {
    const method = 'post';
    const url = `${this.resourceUrl}/${this.id}/transfers`;
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

  fetchUPITransfer() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/upi_transfer`,
    });
  }

  didDeserialize() {
    this.capturableAmount = this.amount;
    if (this.fee_bearer == 'customer') {
      this.capturableAmount = this.amount - this.fee;
    }
  }

  fetchInstantRefundFee(id, amount) {
    const method = 'get';
    const url = `/refunds/fee/`;
    const data = {
      payment_id: id,
      amount,
    };

    return this.makeGenericAjaxCall({ url, data, method });
  }
}

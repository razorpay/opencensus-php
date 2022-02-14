import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'common/utils/rzp-utils';
import Payment from './Payment';

export default class Order extends GenericEntity {
  // listRouteName = 'order_fetch';
  // detailsRouteName = 'order_fetch_by_id';
  resourceUrl = 'orders';

  fetchMagicCheckoutOrder(id) {
    const Klass = this.constructor;
    return this.makeGenericAjaxCall({
      url: `1cc/${this.resourceUrl}/${id}`,
    }).then((response) => {
      return new Klass(response.data).deserialize();
    });
  }

  fetchPayments() {
    return this.makeGenericAjaxCall({
      url: `${this.resourceUrl}/${this.id}/payments`,
    }).then((response) => {
      response.data.items = response.data.items.map((item) => new Payment(item).deserialize());
      return response;
    });
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value);
    }
    return super.deserializeProperty(prop, value);
  }
}

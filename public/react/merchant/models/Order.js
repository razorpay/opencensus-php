import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import Payment from './Payment';
import ajax from 'merchant/utils/ajax';

export default class Order extends GenericEntity {
  listRouteName = 'order_fetch';
  detailsRouteName = 'order_fetch_by_id';

  fetchPayments() {
    let data = {
      route_name: 'order_payments',
    };
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Payment().deserialize(item)
      );
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

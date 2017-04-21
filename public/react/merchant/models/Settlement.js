import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';
import ajax from 'merchant/utils/ajax';

export default class Settlement extends GenericEntity {
  listRouteName = 'setl_fetch_multiple';
  detailsRouteName = 'setl_fetch_by_id';

  fetchBreakupDetails() {
    let Klass = this.constructor;
    let data = {
      route_name: 'setl_get_details',
    };
    data.url_params = JSON.stringify({
      '{id}': this.id,
    });

    return this.makeGenericAjaxCall({ data }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass().deserialize(item)
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

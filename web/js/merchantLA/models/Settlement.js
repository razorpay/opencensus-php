import GenericEntity from './GenericEntity';
import { getFixedINRAmount } from 'rzp/utils/rzp-utils';

export default class Settlement extends GenericEntity {
  resourceUrl = 'settlements';

  fetchBreakupDetails() {
    let Klass = this.constructor;

    const url = `${this.resourceUrl}/${this.id}/details`;
    return this.makeGenericAjaxCall({ url }).then(response => {
      response.data.items = response.data.items.map(item =>
        new Klass(item).deserialize()
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
